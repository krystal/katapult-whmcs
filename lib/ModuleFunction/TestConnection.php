<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use Http\Client\Common\Exception\ClientErrorException;
use KatapultAPI\Core\Model\OrganizationsGetResponse200;
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
        } catch (ClientErrorException $e) {
            $success = false;

            if ($e->getCode() === 403) {
                $errorMsg = sprintf(
                    '<strong>HTTP %d: %s</strong><br>%s',
                    $e->getResponse()->getStatusCode(),
                    $e->getMessage(),
                    $this->firstTimeCreatingServerErrorMessage()
                );
            } else {
                $errorMsg = $e->getMessage();
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

    private function firstTimeCreatingServerErrorMessage(): string
    {
        return <<<HTML
<p>Are you...</p>
<p>creating a new server for the first time?<br>&nbsp;&nbsp;Ignore this message and proceed to Continue Anyway.</p>
<p>configuring an existing server?<br>&nbsp;&nbsp;You may need to configure <a href="https://docs.katapult.io/docs/dev/whmcs/Configuration/intial-setup#create-the-first-product">Katapult's API token</a>.</p>
HTML;
    }
}
