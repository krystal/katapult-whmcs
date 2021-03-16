<?php

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

/**
 * @todo root password, hostname
 * @todo check build hasn't been started before
 * @todo persist VM details to the service once it's built
 */
function katapult_CreateAccount(array $params): string
{
	try {
		$params = new ServerModuleParams($params);

		// Build a VM
		$response = katapult()->resource(VirtualMachine::class, $params->client->managed_organization)->build([
			'package' => ['permalink' => $params->package],
			'data_center' => ['permalink' => $params->dataCenter],
			'disk_template' => ['permalink' => $params->diskTemplate]
		]);

		// Persist the build ID
		$params->service->dataStoreWrite(Service::DS_VM_BUILD_ID, $response->build->id);

		// Log it
		$params->service->log("Started VM build: {$response->build->id}");

		return 'success';
	} catch (\Throwable $e) {
		return $e->getMessage();
	}
}


