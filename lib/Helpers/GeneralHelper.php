<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Helpers;

use WHMCS\Module\Server\Katapult\KatapultWHMCS;

class GeneralHelper
{
    public static function attempt(callable $task, string $taskName): void
    {
        try {
            $task();
            KatapultWHMCS::log($taskName . ' completed');
        } catch (\Throwable $e) {
            KatapultWHMCS::log("Error running task: {$taskName}: {$e->getMessage()}");
            KatapultWHMCS::log(print_r($e,true));
        }
    }
}
