<?php

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
	return \WHMCS\Module\Server\Katapult\ServerModuleParams::getWhmcsServerConfiguration();
}

