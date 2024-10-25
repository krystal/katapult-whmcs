<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use KatapultAPI\Core\Model\VirtualMachinesVirtualMachineConsoleSessionsPostBody;
use KatapultAPI\Core\Model\VirtualMachinesVirtualMachineConsoleSessionsPostResponse201;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

final class ServiceSingleSignOn extends APIModuleCommand
{
    public function run(array $params): array|string
    {
        return $this->runCommandOnVM->runOnVm($params, function (VMServerModuleParams $params) {
            $requestBody = new VirtualMachinesVirtualMachineConsoleSessionsPostBody();
            $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

            $consoleSessionResponse = $this->katapultAPI->postVirtualMachineConsoleSessions($requestBody);

            if (!$consoleSessionResponse instanceof VirtualMachinesVirtualMachineConsoleSessionsPostResponse201) {
                throw APIException::new(
                    $consoleSessionResponse,
                    VirtualMachinesVirtualMachineConsoleSessionsPostResponse201::class,
                );
            }

            $consoleSession = $consoleSessionResponse->getConsoleSession();

            $params->service->log('Created console session for VM');

            return [
                'success' => true,
                'redirectTo' => $consoleSession->getUrl(),
            ];
        });
    }
}
