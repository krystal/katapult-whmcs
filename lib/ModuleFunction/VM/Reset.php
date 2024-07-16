<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction\VM;

use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineResetPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineResetPostResponse200;
use WHMCS\Module\Server\Katapult\ModuleFunction\APIModuleCommand;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

class Reset extends APIModuleCommand
{
    public function run(array $params): array|string
    {
        return $this->runCommandOnVM->runOnVm($params, function (VMServerModuleParams $params) {
            $requestBody = new VirtualMachinesVirtualMachineResetPostBody();
            $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

            $apiResult = $this->katapultAPI->postVirtualMachineReset($requestBody);

            return \Katapult\handleAPIResponse(
                VirtualMachinesVirtualMachineResetPostResponse200::class,
                $apiResult,
                $params->service,
                'VM reset',
                'VM failed to reset',
            );
        });
    }
}
