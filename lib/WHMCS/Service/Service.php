<?php

namespace WHMCS\Module\Server\Katapult\WHMCS\Service;

use Krystal\Katapult\Resources\Organization\VirtualMachine;
use WHMCS\Module\Server\Katapult\Concerns\HasDataStoreValues;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

class Service extends \Grizzlyware\Salmon\WHMCS\Service\Service
{
	use HasDataStoreValues;

	const DS_VM_BUILD_ID = 'vm_build_id';
	const DS_VM_ID = 'vm_id';

	const HOOK_BUILD_REQUESTED = 'KatapultVirtualMachineBuildRequested';
	const HOOK_VM_BUILT = 'KatapultVirtualMachineBuilt';

	protected function dataStoreRelType(): string
	{
		return 'service';
	}

	public function client()
	{
		return $this->belongsTo(Client::class, 'userid');
	}

	/**
	 * @param bool $duringCreate
	 * @return bool
	 * @throws \Exception Returns true if it successfully finished provisioning a service, or false if there is no pending build and nothing to do.
	 * @todo invoke this as frequently as possible
	 */
	public function checkForExistingBuildAttempt(bool $duringCreate = false): bool
	{
		// Existing VM ID?
		if ($this->dataStoreRead(self::DS_VM_ID)) {
			if ($duringCreate) {
				throw new \Exception('There is a VM ID set for this service already, build previously completed');
			}

			return false;
		}

		// Existing build ID?
		$buildId = $this->dataStoreRead(self::DS_VM_BUILD_ID);

		// No build?
		if (!$buildId) {
			return false;
		}

		// Fetch the build state
		$virtualMachineBuild = katapult()->resource(VirtualMachine\VirtualMachineBuild::class)->get($buildId);

		// Do we have a VM?
		if(!$virtualMachineBuild->virtual_machine) {
			if ($duringCreate) {
				throw new \Exception('The VM is still building');
			}

			return false;
		}

		// Get the VM
		/** @var VirtualMachine $vm */
		$vm = katapult()->resource(VirtualMachine::class)->get($virtualMachineBuild->virtual_machine->id);

		// Do we have a root pw?
		if(!$vm->initial_root_password) {
			if ($duringCreate) {
				throw new \Exception('The VM is awaiting a root password');
			}

			return false;
		}

		// Does it have IPs?
		if(count($vm->ip_addresses) < 1) {
			if ($duringCreate) {
				throw new \Exception('The VM is awaiting IP address assignment');
			}

			return false;
		}

		// Go..
		$this->populateServiceWithVm($vm);

		// Fire a hook!
		$this->triggerHook(self::HOOK_VM_BUILT);

		return true;
	}

	protected function populateServiceWithVm(VirtualMachine $virtualMachine): void
	{
		$this->username = 'root';
		$this->password = \encrypt($virtualMachine->initial_root_password);
		$this->domain = $virtualMachine->fqdn;

		// Extract the IPs
		$ipAddresses = array_map(function ($ipAddressObj) {
			return $ipAddressObj->address;
		}, $virtualMachine->ip_addresses);

		// Assign the IPs to the service
		$this->dedicatedip = $ipAddresses[0];
		unset($ipAddresses[0]);
		$this->assignedips = implode(PHP_EOL, $ipAddresses);

		// Save the details
		$this->save();
		$this->dataStoreWrite(self::DS_VM_ID, $virtualMachine->id, $virtualMachine->id);
	}

	public function triggerHook(string $hook): void
	{
		try {
			\run_hook($hook, [
				'service' => $this,
			]);
		} catch (\Throwable $e) {
			$this->log("Failed to run hook: {$hook}: {$e->getMessage()}");
		}
	}
}


