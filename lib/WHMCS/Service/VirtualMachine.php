<?php

namespace WHMCS\Module\Server\Katapult\WHMCS\Service;

use Krystal\Katapult\KatapultAPI\Model\Enum\VirtualMachineStateEnum;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachineLookup;
use Krystal\Katapult\KatapultAPI\Model\GetVirtualMachine200ResponseVirtualMachine as KatapultVirtualMachine;
use Psr\Http\Message\ResponseInterface;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuilding;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuildNotFound;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuildTimeout;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineExists;
use Carbon\Carbon;

/**
 * Class Service
 * @package WHMCS\Module\Server\Katapult\WHMCS\Service
 *
 * @property-read string|null $vm_id
 * @property-read string|null $vm_build_id
 * @property-read Carbon|null $vm_build_started_at
 * @property-read KatapultVirtualMachine $vm
 * @property-read string $vm_state
 * @property-read VirtualMachineLookup $virtual_machine_lookup
 */
class VirtualMachine extends Service
{
    /**
     * Build timeout in seconds
     */
    public const BUILD_TIMEOUT = 900;

    public const DS_VM_BUILD_ID = 'vm_build_id';
    public const DS_VM_BUILD_STARTED_AT = 'vm_build_started_at';
    public const DS_VM_BUILD_TIMEOUT_REACHED_AT = 'vm_build_timeout_reached_at';
    public const DS_VM_ID = 'vm_id';

    public const HOOK_BUILD_REQUESTED = 'KatapultVirtualMachineBuildRequested';
    public const HOOK_VM_BUILT = 'KatapultVirtualMachineBuilt';
    public const HOOK_BUILD_TIMED_OUT = 'KatapultVirtualMachineBuildTimedOut';

    public const STATE_UNKNOWN = 'unknown';
    public const STATE_BUILDING = 'building';

    protected ?KatapultVirtualMachine $virtualMachine = null;

    public function getVmIdAttribute(): ?string
    {
        return $this->dataStoreRead(self::DS_VM_ID);
    }

    public function getVmBuildIdAttribute(): ?string
    {
        return $this->dataStoreRead(self::DS_VM_BUILD_ID);
    }

    public function getVmBuildStartedAtAttribute(): ?Carbon
    {
        return $this->dataStoreRead(self::DS_VM_BUILD_STARTED_AT);
    }

    public function getVirtualMachineLookupAttribute(): VirtualMachineLookup
    {
        $virtualMachineLookup = new VirtualMachineLookup();
        $virtualMachineLookup->setId($this->vm_id);

        return $virtualMachineLookup;
    }

    public function getVmAttribute(): ?KatapultVirtualMachine
    {
        if ($this->virtualMachine) {
            return $this->virtualMachine;
        }

        if ($this->vm_id) {
            $this->virtualMachine = katapult()
                ->getVirtualMachine(['virtual_machine[id]' => $this->vm_id])
                ->getVirtualMachine();
        }

        return $this->virtualMachine;
    }

    public function getVmStateAttribute(): string
    {
        if ($this->vm) {
            return match ($this->vm->getState()) {
                VirtualMachineStateEnum::Started, VirtualMachineStateEnum::Stopped => $this->vm->getState(),
                default => self::STATE_UNKNOWN,
            };
        }

        if ($this->vm_build_id && !$this->vm_id) {
            return self::STATE_BUILDING;
        }

        return self::STATE_UNKNOWN;
    }

    public function silentlyCheckForExistingBuildAttempt(): void
    {
        try {
            $this->checkForExistingBuildAttempt();
        } catch (\Throwable $e) {
            // We don't care
        }
    }

    /**
     * Will check for an existing VM build and persist it to WHMCS if is has finished building in Katapult
     * @throws VirtualMachineBuildNotFound
     * @throws VirtualMachineBuilding
     * @throws VirtualMachineExists
     * @throws VirtualMachineBuildTimeout
     */
    public function checkForExistingBuildAttempt(): void
    {
        // Existing VM ID?
        if ($this->vm_id) {
            throw new VirtualMachineExists('There is a VM ID set for this service already, build previously completed');
        }

        // Existing build ID?
        $buildId = $this->vm_build_id;

        // No build?
        if (!$buildId) {
            throw new VirtualMachineBuildNotFound('There is no build ID set for this service');
        }

        // Fetch the build state
        $apiResult = katapult()->getVirtualMachinesBuildsVirtualMachineBuild([
            'virtual_machine_build[id]' => $buildId,
        ]);

        try {
            if ($apiResult instanceof ResponseInterface && $apiResult->getStatusCode() === 404) {
                throw new VirtualMachineBuilding('No VM build exists');
            }

            // Do we have a VM?
            if (!$apiResult->getVirtualMachineBuild()->getVirtualMachine()->getId()) {
                throw new VirtualMachineBuilding('The VM build is queued with Katapult');
            }

            // Get the VM
            $vm = katapult()->getVirtualMachine([
                'virtual_machine[id]' => $apiResult->getVirtualMachineBuild()->getVirtualMachine()->getId(),
            ])->getVirtualMachine();

            // Do we have a root pw?
            if (!$vm->getInitialRootPassword()) {
                throw new VirtualMachineBuilding('The VM is awaiting a root password');
            }

            // Does it have IPs?
            if (count($vm->getIpAddresses()) < 1) {
                throw new VirtualMachineBuilding('The VM is awaiting IP address assignment');
            }
        } catch (VirtualMachineBuilding $e) {
            // Has it timed out?
            if ($this->vm_build_started_at->diffInSeconds(Carbon::now()) > self::BUILD_TIMEOUT) {
                // Fire the hook, just once..
                if (!$this->dataStoreRead(self::DS_VM_BUILD_TIMEOUT_REACHED_AT)) {
                    $this->triggerHook(self::HOOK_BUILD_TIMED_OUT);
                }

                // So we don't trigger it again.
                $this->dataStoreWrite(self::DS_VM_BUILD_TIMEOUT_REACHED_AT, Carbon::now());

                // Throw it back out
                throw new VirtualMachineBuildTimeout("Virtual machine build has timed out");
            }

            // No? Rethrow
            throw $e;
        }

        // Go..
        $this->populateServiceWithVm($vm);

        // Fire a hook!
        $this->triggerHook(self::HOOK_VM_BUILT);
    }

    protected function populateServiceWithVm(KatapultVirtualMachine $virtualMachine): void
    {
        $this->username = 'root';
        $this->password = \encrypt($virtualMachine->getInitialRootPassword());
        $this->domain = $virtualMachine->getFqdn();

        // Extract the IPs
        $ipAddresses = array_map(function ($ipAddressObj) {
            return $ipAddressObj->getAddress();
        }, $virtualMachine->getIpAddresses());

        // Assign the IPs to the service
        $this->dedicatedip = $ipAddresses[0];
        unset($ipAddresses[0]);
        $this->assignedips = implode(PHP_EOL, $ipAddresses);

        // Save the details
        $this->save();
        $this->dataStoreWrite(self::DS_VM_ID, $virtualMachine->getId(), $virtualMachine->getId());

        // Log
        $this->log("Successfully provisioned");
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'vm_id' => $this->vm_id,
            'vm' => [
                'state' => $this->vm_state,
            ],
        ];
    }
}
