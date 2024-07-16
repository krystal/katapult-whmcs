<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction\VM;

use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineShutdownPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineShutdownPostResponse200;
use WHMCS\Module\Server\Katapult\ModuleFunction\APIModuleCommand;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

class Shutdown extends APIModuleCommand
{
    public function run(array $params): array|string
    {
        return $this->runCommandOnVM->runOnVm($params, function (VMServerModuleParams $params) {
            $requestBody = new VirtualMachinesVirtualMachineShutdownPostBody();
            $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

            $apiResult = $this->katapultAPI->postVirtualMachineShutdown($requestBody);

            return \Katapult\handleAPIResponse(
                VirtualMachinesVirtualMachineShutdownPostResponse200::class,
                $apiResult,
                $params->service,
                'VM shutdown',
                'VM failed to shutdown',
            );
        });
    }
}
