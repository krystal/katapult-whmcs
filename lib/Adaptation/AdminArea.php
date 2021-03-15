<?php

namespace WHMCS\Module\Server\Katapult\Adaptation;

use WHMCS\Module\Server\Katapult\Helpers\GeneralHelper;
use WHMCS\Module\Server\Katapult\Helpers\WhmcsHelper;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Utility\Environment\WebHelper;

class AdminArea
{
	public static function addConfigurationPaneToProductSettings($vars): array
	{
		if (!WhmcsHelper::productIdIsKatapult($vars['pid'])) {
			return [];
		}

		// Do not show this GUI if we're using a constant
		if(defined('KATAPULT_API_V1_KEY') && KATAPULT_API_V1_KEY) {
			return [];
		}

		$baseUrl = WebHelper::getBaseUrl();
		$parentOrganizationEscaped = htmlentities(KatapultWhmcs::getParentOrganizationIdentifier() ?: '');

		$katapultLogo = <<<HTML
<img src="{$baseUrl}/modules/servers/katapult/assets/katapult_logo_white_strapline.svg" alt="" style="max-width: 200px;">
HTML;

		if (KatapultWhmcs::getApiV1Key()) {
			$apiKeyNoteMessage = 'There is currently an API key set, enter a new one to change it. It will apply to all Katapult services.';
		} else {
			$apiKeyNoteMessage = 'Enter your Katapult API key here to connect to Katapult';
		}

		$configurationGui = <<<HTML

<style>

.katapult_configuration {
	padding: 2rem;
	background: #1f003f; 
	border-radius: 4px; 
	color: #fff;
}

.katapult_configuration input[type="text"], .katapult_configuration input[type="password"] {
	background-color: #170030;
	padding: 2rem; 
	margin: 0.5rem 0; 
	color: #fff;
}

.katapult_configuration input[type="checkbox"] {
	filter: invert(100%) hue-rotate(18deg) brightness(1.7); 
	position: relative;
	top: 3px; 
}

.katapult_configuration label {
	margin-top: 2rem;
}

</style>

<div class="row">
	<div class="col-md-12 col-lg-6">
	
		<div style=" " class="katapult_configuration">
		
			{$katapultLogo}<br>
			<label>API Key</label>
			<input type="password" name="katapult_api_v1_key" class="form-control" placeholder="Enter your Katapult API token here" autocomplete="off" />
			<small class="text-light"><b>Note:</b> {$apiKeyNoteMessage}</small>
			
			<br><label>Parent Organization</label>
			<input type="text" value="{$parentOrganizationEscaped}" name="katapult_parent_organization" class="form-control" placeholder="Enter the organization to use when interacting with Katapult" />
			<small class="text-light">This can either be your Katapult subdomain or the organizations ID, beginning with <code>org_</code></small>
			<br>

			<label><input class="form-check" type="checkbox" name="katapult_sync_config_options"> Sync configurable options on save (data centers)</label>
			
		</div>
	
	</div>
</div>
HTML;

		return [
			'' => $configurationGui
		];
	}

	public static function updateKatapultConfiguration($vars): void
	{
		if (!WhmcsHelper::productIdIsKatapult($vars['pid'])) {
			return;
		}

		$syncConfigOptions = false;

		if (isset($_POST['katapult_api_v1_key']) && $_POST['katapult_api_v1_key']) {
			KatapultWhmcs::setApiV1Key($_POST['katapult_api_v1_key']);
			$syncConfigOptions = true;
		}

		if (isset($_POST['katapult_parent_organization'])) {
			KatapultWhmcs::setParentOrganization($_POST['katapult_parent_organization']);
		}

		if ($syncConfigOptions || isset($_POST['katapult_sync_config_options'])) {
			GeneralHelper::attempt([KatapultWhmcs::class, 'syncConfigurableOptions'], 'Sync config options');
		}
	}
}


