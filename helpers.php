<?php

use Krystal\Katapult\KatapultAPI\Client as KatapultApiClient;
use Krystal\Katapult\KatapultAPI\Model\OrganizationLookup;
use Psr\Http\Message\ResponseInterface;
use WHMCS\Module\Server\Katapult\Exceptions\Exception as KatapultException;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

function katapult(): KatapultApiClient
{
    return KatapultWhmcs::getKatapult();
}

/**
 * @param Client|null $client
 *
 * @return OrganizationLookup|null
 * @throws KatapultException
 */
function katapultOrg(Client $client = null): ?OrganizationLookup
{
    if ($client) {
        return $client->managed_organization;
    }

    return KatapultWhmcs::getParentOrganization();
}

function katapultHandleApiResponse(
    ResponseInterface $response,
    VirtualMachine $virtualMachine,
    ?string $successMessage,
    string $errorMessage,
    ?callable $onSuccess = null
): void {
    if ($response->getStatusCode() !== 200) {
        $virtualMachine->log(
            sprintf(
                '%s. Status: "%d". Response: "%s"',
                $errorMessage,
                $response->getStatusCode(),
                $response->getBody()->getContents()
            )
        );
    } else {
        if (is_callable($onSuccess)) {
            $onSuccess();
        }

        if (!is_null($successMessage)) {
            $virtualMachine->log($successMessage);
        }
    }
}
