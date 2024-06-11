<?php

namespace WHMCS\Module\Server\Katapult\Adaptation;

use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\Helpers\GeneralHelper;
use WHMCS\Module\Server\Katapult\Helpers\Replay;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;

class System
{
    public static function syncConfigOptions(): void
    {
        GeneralHelper::attempt([KatapultWhmcs::class, 'syncConfigurableOptions'], 'Sync config options');
    }

    public static function syncVmBuilds(): void
    {
        GeneralHelper::attempt([KatapultWhmcs::class, 'syncVmBuilds'], 'Syncing VM builds');
    }

    /*
     * Checks if the request has been replayed
     */
    public static function validateNoReplayTokenForClientArea(): void
    {
        if (Replay::tokenIsValidForClientArea() !== false) {
            return;
        }

        throw new Exception("Replay detected, please click the button again. You may need to refresh the page first.");
    }
}
