<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

final class AdminServicesTabFields extends APIModuleCommand
{
    public function run(array $params): array
    {
        try {
            $params = new VMServerModuleParams($params, $this->katapultAPI, $this->keyValueStore);

            // Do we have an existing build running? Is it done?
            $params->service->silentlyCheckForExistingBuildAttempt($this->katapultAPI);

            // Generate the public VM JSON
            $publicServiceJson = json_encode($params->service->toPublicArray());

            // State with spaces
            $humanState = htmlentities(str_replace('_', ' ', $params->service->vm_state));

            // State escaped. This is unnecessary, until it's not.
            $vmStateHtml = htmlentities($params->service->vm_state);

            return [
                'Virtual Machine State' => <<<HTML
    <script>
    let katapultVmService = {$publicServiceJson};
    </script>
    
    <span class="katapult-vm-state state--{$vmStateHtml}">{$humanState}</span>
    HTML
            ,];
        } catch (\Throwable $e) {
            return [
                'Error' => \Katapult\formatError('Admin Services Tab Fields', $e),
            ];
        }
    }
}
