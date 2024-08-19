<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Adaptation;

use WHMCS\Module\Server\Katapult\Helpers\GeneralHelper;
use WHMCS\Module\Server\Katapult\Katapult\Sync\ConfigurableOptions;
use WHMCS\Module\Server\Katapult\Katapult\Sync\VMBuilds;

class System
{
    public static function syncConfigOptions(): void
    {
        GeneralHelper::attempt(function () {
            $configurableOptions = new ConfigurableOptions(
                \Katapult\keyValueStore(),
                \Katapult\APIClient(),
            );

            $configurableOptions->sync();
        }, 'Sync config options');
    }

    public static function syncVmBuilds(): void
    {
        GeneralHelper::attempt(function () {
            $vmBuilds = new VMBuilds(
                \Katapult\APIClient(),
            );

            $vmBuilds->sync();
        }, 'Syncing VM builds');
    }
}
