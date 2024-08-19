<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction\VM;

use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineStopPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineStopPostResponse200;
use WHMCS\Module\Server\Katapult\ModuleFunction\APIModuleCommand;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

class Stop extends APIModuleCommand
{
    public function run(array $params): array|string
    {
        return $this->runCommandOnVM->runOnVm($params, function (VMServerModuleParams $params) {
            $requestBody = new VirtualMachinesVirtualMachineStopPostBody();
            $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

            $apiResult = $this->katapultAPI->postVirtualMachineStop($requestBody);

            return \Katapult\handleAPIResponse(
                VirtualMachinesVirtualMachineStopPostResponse200::class,
                $apiResult,
                $params->service,
                'VM stopped',
                'VM failed to stop',
            );
        });
    }
}
