<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\WHMCS\Service;

use Carbon\Carbon;
use KatapultAPI\Core\Client as KatapultAPIClient;
use KatapultAPI\Core\Model\VirtualMachineLookup;
use KatapultAPI\Core\Model\GetVirtualMachine200ResponseVirtualMachine as KatapultVirtualMachine;
use KatapultAPI\Core\Model\VirtualMachinesBuildsVirtualMachineBuildGetResponse200;
use KatapultAPI\Core\Model\VirtualMachinesVirtualMachineGetResponse200;
use Psr\Http\Message\ResponseInterface;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuildFailed;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuilding;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuildNotFound;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuildTimeout;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineExists;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;

/**
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
        $virtualMachineLookup->setId((string) $this->vm_id);

        return $virtualMachineLookup;
    }

    /**
     * @throws APIException
     */
    public function getVmAttribute(): ?KatapultVirtualMachine
    {
        if ($this->virtualMachine) {
            return $this->virtualMachine;
        }

        if ($this->vm_id) {
            $virtualMachineResponse = \Katapult\APIClient()->getVirtualMachine([
                'virtual_machine[id]' => $this->vm_id,
            ]);

            if (!$virtualMachineResponse instanceof VirtualMachinesVirtualMachineGetResponse200) {
                throw APIException::new($virtualMachineResponse, VirtualMachinesVirtualMachineGetResponse200::class);
            }

            $this->virtualMachine = $virtualMachineResponse->getVirtualMachine();
        }

        return $this->virtualMachine;
    }

    public function getVmStateAttribute(): string
    {
        if ($this->vm) {
            return $this->vm->getState();
        }

        if ($this->vm_build_id && !$this->vm_id) {
            return self::STATE_BUILDING;
        }

        return self::STATE_UNKNOWN;
    }

    public function silentlyCheckForExistingBuildAttempt(KatapultAPIClient $katapultAPI): void
    {
        try {
            $this->checkForExistingBuildAttempt($katapultAPI);
        } catch (\Throwable $e) {
            // We don't care
        }
    }

    /**
     * Will check for an existing VM build and persist it to WHMCS if it has finished building in Katapult
     *
     * @throws VirtualMachineBuildNotFound
     * @throws VirtualMachineBuilding
     * @throws VirtualMachineExists
     * @throws VirtualMachineBuildFailed
     * @throws VirtualMachineBuildTimeout
     * @throws APIException
     */
    public function checkForExistingBuildAttempt(KatapultAPIClient $katapultAPI): void
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
        $apiResult = $katapultAPI->getVirtualMachinesBuildsVirtualMachineBuild([
            'virtual_machine_build[id]' => $buildId,
        ]);

        try {
            if ($apiResult instanceof ResponseInterface && $apiResult->getStatusCode() === 404) {
                throw new VirtualMachineBuilding('No VM build exists');
            }

            // Do we have a VM?
            if (!$apiResult instanceof VirtualMachinesBuildsVirtualMachineBuildGetResponse200) {
                throw APIException::new($apiResult, VirtualMachinesBuildsVirtualMachineBuildGetResponse200::class);
            }

            $vmBuild = $apiResult->getVirtualMachineBuild();

            // We have a VM but no ID
            if (!$vmBuild->getVirtualMachine()?->getId()) {
                throw new VirtualMachineBuilding('The VM build is queued with Katapult');
            }

            // Is the build state complete?
            // Possible states:
            //   "draft"
            //   "failed"
            //   "pending"
            //   "complete"
            //   "building"
            // We're after "complete", but it could have permanently failed as well
            // So check for that first
            if ($vmBuild->getState() === 'failed') {
                throw new VirtualMachineBuildFailed('The VM build has failed');
            }

            // ...and if it didn't fail, see if it's complete yet
            if ($vmBuild->getState() !== 'complete') {
                throw new VirtualMachineBuilding('The VM build is still in progress');
            }

            // Get the VM
            $virtualMachineAPIResponse = $katapultAPI->getVirtualMachine([
                'virtual_machine[id]' => $vmBuild->getVirtualMachine()->getId(),
            ]);

            if (!$virtualMachineAPIResponse instanceof VirtualMachinesVirtualMachineGetResponse200) {
                throw APIException::new(
                    $virtualMachineAPIResponse,
                    VirtualMachinesVirtualMachineGetResponse200::class,
                );
            }

            $vm = $virtualMachineAPIResponse->getVirtualMachine();

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
            if ($this->vm_build_started_at && $this->vm_build_started_at->diffInSeconds(Carbon::now()) > self::BUILD_TIMEOUT) {
                // Fire the hook, just once...
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

        // Go...
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
