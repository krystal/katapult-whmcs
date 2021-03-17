<?php

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;
use Krystal\Katapult\Resources\Organization\VirtualMachine as KatapultVirtualMachine;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuildNotFound;
use WHMCS\Module\Server\Katapult\Helpers\KatapultApiV1Helper;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VmServerModuleParams;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use Carbon\Carbon;

if (!defined('WHMCS')) {
	die('This file cannot be accessed directly');
}

function katapult_MetaData(): array
{
	return [
		'DisplayName' => 'Katapult',
		'ServiceSingleSignOnLabel' => 'Open Console',
		'RequiresServer' => false,
	];
}

function katapult_ConfigOptions(): array
{
	return VmServerModuleParams::getWhmcsServerConfiguration();
}

function katapult_ServiceSingleSignOn(array $params): array
{
	return KatapultWhmcs::runModuleCommandOnVm($params, function(VmServerModuleParams $params)
	{
		// Create a console session
		return [
			'success' => true,
			'redirectTo' => $params->service->vm->createConsoleSession()->url
		];
	}, KatapultWhmcs::MRT_SSO);
}

function katapult_TerminateAccount(array $params): string
{
	return KatapultWhmcs::runModuleCommandOnVm($params, function(VmServerModuleParams $params)
	{
		// Delete the VM
		$params->service->vm->delete();

		// Wipe all data store values for this service
		$params->service->clearAllDataStoreValues();
	});
}

function katapult_ChangePackage(array $params): string
{
	return KatapultWhmcs::runModuleCommandOnVm($params, function(VmServerModuleParams $params)
	{
		// Change the VM package
		$params->service->vm->changePackage([
			'permalink' => $params->package
		]);
	});
}

function katapult_StopVm(array $params): string
{
	return KatapultWhmcs::runModuleCommandOnVm($params, function(VmServerModuleParams $params)
	{
		$params->service->vm->stop();
	});
}

function katapult_ResetVm(array $params): string
{
	return KatapultWhmcs::runModuleCommandOnVm($params, function(VmServerModuleParams $params)
	{
		$params->service->vm->reset();
	});
}

function katapult_StartVm(array $params): string
{
	return KatapultWhmcs::runModuleCommandOnVm($params, function(VmServerModuleParams $params)
	{
		$params->service->vm->start();
	});
}

function katapult_ShutdownVm(array $params): string
{
	return KatapultWhmcs::runModuleCommandOnVm($params, function(VmServerModuleParams $params)
	{
		$params->service->vm->shutdown();
	});
}

function katapult_CreateAccount(array $params): string
{
	try {
		$params = new VmServerModuleParams($params);

		// Do we have an existing build running? Is it done?
		try {
			$params->service->checkForExistingBuildAttempt();

			// Great, it's done!
			return 'success';
		} catch (VirtualMachineBuildNotFound $e) {
			// This is fine, and normal behaviour.
		}

		// Hostname?
		if ($params->service->domain) {
			// Make it KP friendly..
			$hostname = str_replace('.', '-', $params->service->domain);
			$hostname = substr($hostname, 0, 18);

			// Remove trailing dashes from the hostname
			while(Str::endsWith($hostname, '-')) {
				$hostname = substr($hostname, 0, -1);
			}

			if(!$hostname) {
				$hostname = null;
			}
		}

		// Build a VM
		$response = katapult()->resource(KatapultVirtualMachine::class, $params->client->managed_organization)->build([
			'package' => ['permalink' => $params->package],
			'data_center' => ['permalink' => $params->dataCenter],
			'disk_template' => ['permalink' => $params->diskTemplate],
			'hostname' => $hostname ?? null
		]);

		// Persist the build ID
		$params->service->dataStoreWrite(VirtualMachine::DS_VM_BUILD_ID, $response->build->id, $response->build->id);
		$params->service->dataStoreWrite(VirtualMachine::DS_VM_BUILD_STARTED_AT, Carbon::now());

		// Log it
		$params->service->log("Started VM build: {$response->build->id}");

		// Trigger a hook
		$params->service->triggerHook(VirtualMachine::HOOK_BUILD_REQUESTED);

		return 'success';
	} catch (ClientException $e) {
		return implode(', ', KatapultApiV1Helper::humaniseHttpError($e));
	} catch (\Throwable $e) {
		return $e->getMessage();
	}
}

function katapult_AdminCustomButtonArray(): array
{
	return [
		'Start VM' => 'StartVm',
		'Shutdown VM' => 'ShutdownVm',
		'Stop VM' => 'StopVm',
		'Reset VM' => 'ResetVm',
	];
}

function katapult_ClientAreaCustomButtonArray(): array
{
	return [
		'Start VM' => 'StartVm',
		'Shutdown VM' => 'ShutdownVm',
		'Stop VM' => 'StopVm',
		'Reset VM' => 'ResetVm',
	];
}

function katapult_AdminServicesTabFields(array $params): array
{
	try {
		$params = new VmServerModuleParams($params);

		// Do we have an existing build running? Is it done?
		$params->service->silentlyCheckForExistingBuildAttempt();

		// Generate the public VM JSON
		$publicServiceJson = \GuzzleHttp\Utils::jsonEncode(
			$params->service->toPublicArray()
		);

		// State with spaces
		$humanState = htmlentities(
			str_replace('_', ' ', $params->service->vm_state)
		);

		// State escaped. This is unnecessary, until it's not.
		$vmStateHtml = htmlentities(
			$params->service->vm_state
		);

		return [
			'Virtual Machine State' => <<<HTML
<script>
let katapultVmService = {$publicServiceJson};
</script>

<span class="katapult-vm-state state--{$vmStateHtml}">{$humanState}</span>
HTML
		];
	} catch (\Throwable $e) {
		return [
			'Error' => $e->getMessage()
		];
	}
}

/**
 * @param array $params
 * @return array
 */
function katapult_ClientArea(array $params): array
{
	try {
		$params = new VmServerModuleParams($params);

		// Do we have an existing build running? Is it done?
		$params->service->silentlyCheckForExistingBuildAttempt();

		return [
			'templatefile' => \WHMCS\Module\Server\Katapult\Helpers\OverrideHelper::view('client/virtual_machines/overview.tpl'),
			'vars' => [
				'katapultVmService' => $params->service->toPublicArray()
			]
		];
	} catch (\Throwable $e) {
		return [];
	}
}

