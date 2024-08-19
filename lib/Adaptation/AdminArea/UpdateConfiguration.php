<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Adaptation\AdminArea;

use WHMCS\Module\Server\Katapult\Adaptation\System;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;

class UpdateConfiguration extends AdminArea
{
    public function __invoke(array $vars): void
    {
        if (!$this->productIsKatapult($vars['pid'])) {
            return;
        }

        $katapultAPIKey = $_POST['katapult_api_v1_key'] ?? null;
        $katapultParentOrganisation = $_POST['katapult_parent_organization'] ?? null;
        $syncConfigOptions = isset($_POST['katapult_sync_config_options']);

        $this->update($katapultAPIKey, $katapultParentOrganisation, $syncConfigOptions);
    }

    private function update(
        ?string $katapultAPIKey,
        ?string $parentOrganization,
        bool $syncConfigOptions,
    ): void {
        if ($katapultAPIKey) {
            $this->APIKey->setAPIKey($_POST['katapult_api_v1_key']);
            $syncConfigOptions = true;
        }

        if ($parentOrganization) {
            $this->parentOrganization->setParentOrganization($_POST['katapult_parent_organization']);
        }

        if ($syncConfigOptions) {
            KatapultWHMCS::log('Attempting to sync config options');

            System::syncConfigOptions();
        }
    }
}
