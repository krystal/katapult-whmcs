<?php

use Illuminate\Support\Str;
use Krystal\Katapult\Resources\Organization\VirtualMachine;
use WHMCS\Module\Server\Katapult\ServerModuleParams;
use WHMCS\Module\Server\Katapult\WHMCS\Service\Service;

if (!defined('WHMCS')) {
	die('This file cannot be accessed directly');
}

function katapult_MetaData(): array
{
	return [
		'DisplayName' => 'Katapult',
	];
}

function katapult_ConfigOptions(): array
{
	return ServerModuleParams::getWhmcsServerConfiguration();
}

function katapult_CreateAccount(array $params): string
{
	try {
		$params = new ServerModuleParams($params);

		// Do we have an existing build running? Is it done?
		if ($params->service->checkForExistingBuildAttempt(true)) {
			return 'success';
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
		$response = katapult()->resource(VirtualMachine::class, $params->client->managed_organization)->build([
			'package' => ['permalink' => $params->package],
			'data_center' => ['permalink' => $params->dataCenter],
			'disk_template' => ['permalink' => $params->diskTemplate],
			'hostname' => $hostname ?? null
		]);

		// Persist the build ID
		$params->service->dataStoreWrite(Service::DS_VM_BUILD_ID, $response->build->id, $response->build->id);

		// Log it
		$params->service->log("Started VM build: {$response->build->id}");

		// Trigger a hook
		$params->service->triggerHook(Service::HOOK_BUILD_REQUESTED);

		return 'success';
	} catch (\Throwable $e) {
		return $e->getMessage();
	}
}

function katapult_AdminServicesTabFields(array $params): array
{
	try {
		$params = new ServerModuleParams($params);

		// Do we have an existing build running? Is it done?
		$params->service->checkForExistingBuildAttempt();

		return [];
	} catch (\Throwable $e) {
		return [
			'Error' => $e->getMessage()
		];
	}
}

/**
 * @param array $params
 * @return array
 *
 * @todo show VM details in the client area
 */
function katapult_ClientArea(array $params): array
{
	try {
		$params = new ServerModuleParams($params);

		// Do we have an existing build running? Is it done?
		$params->service->checkForExistingBuildAttempt();

		return [];
	} catch (\Throwable $e) {
		return [];
	}
}

