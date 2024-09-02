<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use KatapultAPI\Core\Model\VirtualMachinePackageLookup;
use KatapultAPI\Core\Model\VirtualMachinesVirtualMachinePackagePutBody;
use KatapultAPI\Core\Model\VirtualMachinesVirtualMachinePackagePutResponse200;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

final class ChangePackage extends APIModuleCommand
{
    public function run(array $params): array|string
    {
        return $this->runCommandOnVM->runOnVm($params, function (VMServerModuleParams $params) {
            $requestBody = new VirtualMachinesVirtualMachinePackagePutBody();
            $virtualMachinePackageLookup = new VirtualMachinePackageLookup();
            $virtualMachinePackageLookup->setPermalink($params->package);

            $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);
            $requestBody->setVirtualMachinePackage($virtualMachinePackageLookup);

            $apiResult = $this->katapultAPI->putVirtualMachinePackage($requestBody);

            return \Katapult\handleAPIResponse(
                VirtualMachinesVirtualMachinePackagePutResponse200::class,
                $apiResult,
                $params->service,
                'VM package changed to ' . $params->package,
                'VM failed to have its package changed',
            );
        });
    }
}
