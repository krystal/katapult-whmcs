<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Adaptation\AdminArea;

use WHMCS\Module\Server\Katapult\Helpers\OverrideHelper;
use WHMCS\Utility\Environment\WebHelper;

class ConfigurationPane extends AdminArea
{
    public function __invoke(array $vars): array
    {
        if (!$this->productIsKatapult($vars['pid'])) {
            return [];
        }

        $baseUrl = htmlentities(WebHelper::getBaseUrl());
        $parentOrganizationEscaped = htmlentities($this->parentOrganization->getIdentifier() ?: '');

        $katapultLogoPath = OverrideHelper::asset('katapult_logo_white_strapline.svg');

        $katapultLogo = <<<HTML
<img src="{$baseUrl}/modules/servers/katapult/{$katapultLogoPath}" alt="" style="max-width: 200px;">
HTML;

        if ($this->apiKeyIsSet()) {
            $apiKeyNoteMessage = 'There is currently an API key set, enter a new one to change it. It will apply to all Katapult services.';
            $syncOptionsInput = <<<HTML
<label><input class="form-check" type="checkbox" name="katapult_sync_config_options"> Re-sync configurable options on save (data centers, disk templates etc)</label>
HTML;
        } else {
            $apiKeyNoteMessage = 'Enter your Katapult API key here to connect to Katapult';
            $syncOptionsInput = '';
        }

        $html = <<<HTML
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

        // return the configuration
        return [
            '' => $html,
        ];
    }

    private function apiKeyIsSet(): bool
    {
        $apiV1Key = $this->APIKey->getAPIKey();

        return $apiV1Key !== null && strlen($apiV1Key) > 0;
    }
}
