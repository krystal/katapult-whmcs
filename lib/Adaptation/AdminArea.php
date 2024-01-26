<?php

namespace WHMCS\Module\Server\Katapult\Adaptation;

use WHMCS\Module\Server\Katapult\Helpers\GeneralHelper;
use WHMCS\Module\Server\Katapult\Helpers\OverrideHelper;
use WHMCS\Module\Server\Katapult\Helpers\Replay;
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

        $baseUrl = htmlentities(WebHelper::getBaseUrl());
        $parentOrganizationEscaped = htmlentities(KatapultWhmcs::getParentOrganizationIdentifier() ?: '');

        $katapultLogoPath = OverrideHelper::asset('katapult_logo_white_strapline.svg');

        $katapultLogo = <<<HTML
<img src="{$baseUrl}/modules/servers/katapult/{$katapultLogoPath}" alt="" style="max-width: 200px;">
HTML;

        if (KatapultWhmcs::getApiV1Key()) {
            $apiKeyNoteMessage = 'There is currently an API key set, enter a new one to change it. It will apply to all Katapult services.';
            $syncOptionsInput = <<<HTML
<label><input class="form-check" type="checkbox" name="katapult_sync_config_options"> Re-sync configurable options on save (data centers, disk templates etc)</label>
HTML;
        } else {
            $apiKeyNoteMessage = 'Enter your Katapult API key here to connect to Katapult';
            $syncOptionsInput = '';
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
			<small class="text-light">Optional, only required with user keys and will be auto detected for organization keys. It can either be your Katapult subdomain or the organization's ID, beginning with <code>org_</code></small>
			<br>

			{$syncOptionsInput}
			
		</div>
	
	</div>
	
</div>
HTML;

        return [
            '' => $configurationGui,
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
            GeneralHelper::attempt(
                [
                    KatapultWhmcs::class,
                    'syncConfigurableOptions',
                ],
                'Sync config options'
            );
        }
    }

    public function addAssetsToHead(): string
    {
        $baseUrl = htmlentities(WebHelper::getBaseUrl());

        $cssPath = OverrideHelper::asset('dist/css/admin.css');
        $jsPath = OverrideHelper::asset('dist/js/admin.js');

        return <<<HTML
<link href="{$baseUrl}/modules/servers/katapult/{$cssPath}" rel="stylesheet" type="text/css" />
<script type="text/javascript" defer src="{$baseUrl}/modules/servers/katapult/{$jsPath}"></script>
HTML;
    }
}
