<?php

use Http\Client\Common\Exception\ClientErrorException;
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
    string $successClass,
    mixed $response,
    VirtualMachine $virtualMachine,
    ?string $successMessage,
    string $errorMessage,
    ?callable $onSuccess = null
): void {
    if ($response instanceof ResponseInterface && $response->getStatusCode() !== 200) {
        $virtualMachine->log(
            sprintf(
                '%s. Status: "%d". Response: "%s"',
                $errorMessage,
                $response->getStatusCode(),
                $response->getBody()->getContents()
            )
        );
    } elseif ($response instanceof $successClass) {
        if (is_callable($onSuccess)) {
            $onSuccess();
        }

        if (!is_null($successMessage)) {
            $virtualMachine->log($successMessage);
        }
    } else {
        // openapi returns a ResponseInterface on failure and a specific class for successes
        // it varies by endpoint, so we pass through the expected type
        // in this way we can be certain that a response was in fact successful
        throw new \InvalidArgumentException(
            sprintf(
                'Could not determine response. Expected %s to be ResponseInterface or %s',
                get_class($response),
                $successClass
            )
        );
    }
}

function katapultFormatError(string $prefix, Throwable $e): string {
    if ($e instanceof ClientErrorException) {
        $responseBody = $e->getResponse()->getBody();

        if ($responseBody->isSeekable()) {
            $responseBody->seek(0);
        }

        $response = $responseBody->getContents();

        $json = json_decode($response, true);

        if ($json && isset($json['error']['description']) && isset($json['error']['code'])) {
            $response = $json['error']['code'] . ': ' . $json['error']['description'];

            if (!empty($json['error']['detail']['details'])) {
                $response .= ' - ' . $json['error']['detail']['details'];
            }
        }

        // example of the resultant activity log message is as follows:
        // Module Create Failed - Service ID: 1 - Error: Create Account [http: 422 POST /core/v1/organizations/:organization/managed]: Unprocessable Entity (organization_limit_reached: The maxmium number of organizations that can be created has been reached)
        // This is about as close to verbose enough to narrow it down quite specifically without being overly long-winded
        // It is still _somewhat_ long-winded but not horrendously so

        return sprintf(
            '%s [http: %d %s %s]: %s (%s)',
            $prefix,
            $e->getResponse()->getStatusCode(),
            $e->getRequest()->getMethod(),
            $e->getRequest()->getUri()->getPath(),
            $e->getMessage(),
            $response
        );
    }

    return sprintf('%s [%d]: %s', $prefix, $e->getCode(), $e->getMessage());
}
