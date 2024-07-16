<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use WHMCS\Module\Server\Katapult\Helpers\OverrideHelper;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

final class ClientArea extends APIModuleCommand
{
    public function run(array $params): array
    {
        try {
            $params = new VMServerModuleParams($params, $this->katapultAPI, $this->keyValueStore);

            // Do we have an existing build running? Is it done?
            $params->service->silentlyCheckForExistingBuildAttempt($this->katapultAPI);

            return [
                'templatefile' => OverrideHelper::view('client/virtual_machines/overview.tpl'),
                'vars' => [
                    'katapultVmService' => $params->service->toPublicArray(),
                ],
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
