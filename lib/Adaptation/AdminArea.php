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
<div class="row">

	<div class="col-md-12 col-lg-6">
	
		<div style=" " class="katapult_configuration">
		
			{$katapultLogo}<br>
			
			<label>API Key</label>
			<input type="password" name="katapult_api_v1_key" class="form-control" placeholder="Enter your Katapult API token here" autocomplete="off" />
			<small class="text-light"><b>Note:</b> {$apiKeyNoteMessage}</small>
			<br>
			
			<label>Parent Organization</label>
			<input type="text" value="{$parentOrganizationEscaped}" name="katapult_parent_organization" class="form-control" placeholder="Enter the organization to use when interacting with Katapult" />
			<small class="text-light">This can either be your Katapult subdomain or the organization's ID, beginning with <code>org_</code></small>
			<br>

			<label><input class="form-check" type="checkbox" name="katapult_sync_config_options"> Sync configurable options on save (data centers, disk templates etc)</label>
			
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

	public function addAssetsToHead(): string
	{
		$baseUrl = WebHelper::getBaseUrl();

		return <<<HTML
<link href="{$baseUrl}/modules/servers/katapult/assets/dist/css/admin.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" defer src="{$baseUrl}/modules/servers/katapult/assets/dist/js/admin.js"></script>
HTML;
	}
}


