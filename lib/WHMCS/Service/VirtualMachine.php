<?php

namespace WHMCS\Module\Server\Katapult\WHMCS\Service;

use Krystal\Katapult\Resources\Organization\VirtualMachine as KatapultVirtualMachine;
use WHMCS\Module\Server\Katapult\Concerns\HasDataStoreValues;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

/**
 * Class Service
 * @package WHMCS\Module\Server\Katapult\WHMCS\Service
 *
 * @property-read string|null $vm_id
 * @property-read string|null $vm_build_id
 */
class VirtualMachine extends Service
{
	/**
	 * Build timeout in seconds
	 */
	const BUILD_TIMEOUT = 300;

	const DS_VM_BUILD_ID = 'vm_build_id';
	const DS_VM_ID = 'vm_id';

	const HOOK_BUILD_REQUESTED = 'KatapultVirtualMachineBuildRequested';
	const HOOK_VM_BUILT = 'KatapultVirtualMachineBuilt';

	/**
	 * @param bool $duringCreate
	 * @return bool
	 * @throws \Exception Returns true if it successfully finished provisioning a service, or false if there is no pending build and nothing to do.
	 * @todo invoke this as frequently as possible
	 */
	public function checkForExistingBuildAttempt(bool $duringCreate = false): bool
	{
		// Existing VM ID?
		if ($this->vm_id) {
			if ($duringCreate) {
				throw new \Exception('There is a VM ID set for this service already, build previously completed');
			}

			return false;
		}

		// Existing build ID?
		$buildId = $this->vm_build_id;

		// No build?
		if (!$buildId) {
			return false;
		}

		// Fetch the build state
		$virtualMachineBuild = katapult()->resource(KatapultVirtualMachine\VirtualMachineBuild::class)->get($buildId);

		// Do we have a VM?
		if(!$virtualMachineBuild->virtual_machine) {
			if ($duringCreate) {
				throw new \Exception('The VM is still building');
			}

			return false;
		}

		// Get the VM
		/** @var VirtualMachine $vm */
		$vm = katapult()->resource(KatapultVirtualMachine::class)->get($virtualMachineBuild->virtual_machine->id);

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

	protected function populateServiceWithVm(KatapultVirtualMachine $virtualMachine): void
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
}


