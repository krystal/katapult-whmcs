<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use Krystal\Katapult\KatapultAPI\Model\OrganizationsGetResponse200;
use Psr\Http\Message\ResponseInterface;

final class TestConnection extends APIModuleCommand
{
    public function run(): array
    {
        try {
            $response = $this->katapultAPI->getOrganizations();

            if ($response instanceof OrganizationsGetResponse200) {
                $success = true;
                $errorMsg = '';
            } elseif ($response instanceof ResponseInterface) {
                $success = false;
                $errorMsg = $response->getBody()->getContents();
            } else {
                $success = false;
                $errorMsg = '';
            }
        } catch (\Throwable $e) {
            $success = false;
            $errorMsg = $e->getMessage();
        }

        return [
            'success' => $success,
            'error' => $errorMsg,
        ];
    }
}
