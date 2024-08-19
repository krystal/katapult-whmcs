<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction\VM;

use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineStartPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineStartPostResponse200;
use WHMCS\Module\Server\Katapult\ModuleFunction\APIModuleCommand;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

class Start extends APIModuleCommand
{
    public function run(array $params): array|string
    {
        return $this->runCommandOnVM->runOnVm($params, function (VMServerModuleParams $params) {
            $requestBody = new VirtualMachinesVirtualMachineStartPostBody();
            $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

            $apiResult = $this->katapultAPI->postVirtualMachineStart($requestBody);

            return \Katapult\handleAPIResponse(
                VirtualMachinesVirtualMachineStartPostResponse200::class,
                $apiResult,
                $params->service,
                'VM started',
                'VM failed to start',
            );
        });
    }
}
