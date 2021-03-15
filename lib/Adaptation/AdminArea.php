<?php

namespace WHMCS\Module\Server\Katapult\Adaptation;

use WHMCS\Module\Server\Katapult\KatapultHelper;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Utility\Environment\WebHelper;

class AdminArea
{
	public static function addConfigurationPaneToProductSettings($vars): array
	{
		if (!KatapultHelper::productIdIsKatapult($vars['pid'])) {
			return [];
		}

		// Do not show this GUI if we're using a constant
		if(defined('KATAPULT_API_V1_KEY') && KATAPULT_API_V1_KEY) {
			return [];
		}

		$baseUrl = WebHelper::getBaseUrl();

		$katapultLogo = <<<HTML
<img src="{$baseUrl}/modules/servers/katapult/assets/katapult_logo_white_strapline.svg" alt="" style="max-width: 200px">
HTML;

		if (KatapultWhmcs::getApiV1Key()) {
			$noteMessage = 'There is currently an API key set, enter a new one to change it. It will apply to all Katapult services.';
		} else {
			$noteMessage = 'Enter your Katapult API key here to connect to Katapult';
		}

		$apiKeyInput = <<<HTML
<div class="row">
	<div class="col-md-12 col-lg-6">
	
		<div style="padding: 2rem; background: #1f003f; border-radius: 4px; color: #fff; ">
		
			{$katapultLogo} <br>
			<input type="password" style="background-color: #170030; padding: 2rem; margin: 1rem 0; color: #fff" name="katapult_api_v1_key" class="form-control" placeholder="Enter your Katapult API token here" autocomplete="off" />
			<small class="text-light"><b>Note:</b> {$noteMessage}</small>
		</div>
	
	</div>
</div>
HTML;

		return [
			'' => $apiKeyInput
		];
	}

	public static function updateKatapultConfiguration($vars): void
	{
		if (!KatapultHelper::productIdIsKatapult($vars['pid'])) {
			return;
		}

		if (!isset($_POST['katapult_api_v1_key']) || !$_POST['katapult_api_v1_key']) {
			return;
		}

		KatapultWhmcs::setApiV1Key($_POST['katapult_api_v1_key']);
	}
}


