<?php

/**
 * It's a little awkward and manual doing things this way, but we wanted to have
 * some dependency injection without being tied to whatever version of the
 * illuminate container WHMCS are using at the time (and if they change anything
 * between versions, to avoid breaking changes). Similar deal with trying to use
 * something such as PHP-DI, which Krystal uses internally for our own WHMCS
 * setup. So here we fallabck to constructing PHP objects manually rather than
 * with any fancy schmancy autowiring. We'd prefer that and may refactor in the
 * future! We've gone with functions for stuff like the key value store to at
 * least give us the option to change the adapter out and stuff like that.
 */

namespace Katapult;

use Http\Client\Common\Exception\ClientErrorException;
use KatapultAPI\Core\Client as KatapultAPIClient;
use Psr\Http\Message\ResponseInterface;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;
use WHMCS\Module\Server\Katapult\Katapult\API\APIFactory;
use WHMCS\Module\Server\Katapult\Katapult\API\APIKey;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\SalmonKeyValueStore;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use Throwable;

function keyValueStore(): KeyValueStoreInterface
{
    return new SalmonKeyValueStore();
}

function APIKey(): APIKey
{
    return new APIKey(keyValueStore());
}

function APIClient(): KatapultAPIClient
{
    $apiHost = getenv('KATAPULT_API_HOST');
    if ($apiHost === false) {
        $apiHost = null;
    }

    return (new APIFactory(APIKey()))->getKatapultAPIClient($apiHost);
}

function handleAPIResponse(
    string $successClass,
    mixed $response,
    VirtualMachine $virtualMachine,
    ?string $successMessage,
    string $errorMessage,
    ?callable $onSuccess = null
): string {
    $successStatus = function (int $statusCode): bool {
        return $statusCode >= 200 && $statusCode <= 299;
    };

    if ($response instanceof ResponseInterface && !$successStatus($response->getStatusCode())) {
        $virtualMachine->log(
            sprintf(
                '%s. Status: "%d". Response: "%s"',
                $errorMessage,
                $response->getStatusCode(),
                $response->getBody()->getContents()
            )
        );

        return $errorMessage;
    } elseif ($response instanceof $successClass) {
        if (is_callable($onSuccess)) {
            $onSuccess();
        }

        if (!is_null($successMessage)) {
            $virtualMachine->log($successMessage);
        }

        return 'success';
    } else {
        // openapi returns a ResponseInterface on failure and a specific class for successes
        // it varies by endpoint, so we pass through the expected type
        // in this way we can be certain that a response was in fact successful
        throw new \InvalidArgumentException(
            sprintf(
                'Could not determine response. Expected %s to be ResponseInterface or %s',
                is_object($response) ? get_class($response) : get_debug_type($response),
                $successClass
            )
        );
    }
}

/**
 * An example of an error response:
 * {
 *   "error": {
 *     "code": "validation_error",
 *     "description": "A validation error occurred with the object that was being created/updated/deleted",
 *     "detail": {
 *       "errors": [
 *         "First system disk must be greater than 50GB (required for Ubuntu 20.04)"
 *       ]
 *     }
 *   }
 * }
 */
function formatError(string $prefix, Throwable $e): string
{
    if ($e instanceof ClientErrorException || $e instanceof APIException) {
        $response = $e->getResponse();

        if (!is_null($response)) {
            $responseBody = $response->getBody();

            if ($responseBody->isSeekable()) {
                $responseBody->seek(0);
            }

            $responseContents = $responseBody->getContents();

            $json = json_decode($responseContents, true);

            $errorMessage = '';
            $didDetermineErrorMessage = false;

            if (isset($json, $json['error']['description'], $json['error']['code'])) {
                $didDetermineErrorMessage = true;
                $errorMessage = $json['error']['code'] . ': ' . $json['error']['description'];

                if (!empty($json['error']['detail']['details'])) {
                    $errorMessage .= ' - ' . $json['error']['detail']['details'];
                }

                if (!empty($json['error']['detail']['errors'])) {
                    if (is_string($json['error']['detail']['errors'])) {
                        $errorMessage .= ' - ' . $json['error']['detail']['errors'];
                    } elseif (is_array($json['error']['detail']['errors'])) {
                        $errorMessage .= ' - ' . implode(', ', $json['error']['detail']['errors']);
                    }
                }
            }

            $httpStatusCode = $e->getResponse()?->getStatusCode() ?? '';

            if ($e instanceof ClientErrorException) {
                $httpDetails = sprintf(
                    '%d %s %s',
                    $httpStatusCode,
                    $e->getRequest()->getMethod(),
                    $e->getRequest()->getUri()->getPath(),
                );
            } else {
                $httpDetails = $httpStatusCode;
            }

            return sprintf(
                '%s [http: %s]: %s',
                $prefix,
                $httpDetails,
                $didDetermineErrorMessage ? $errorMessage : $e->getMessage(),
            );
        } else {
            $errorMessage = '';
            $httpStatusCode = '';
        }

        // example of the resultant activity log message is as follows:
        // Module Create Failed - Service ID: 1 - Error: Create Account [http: 422 POST /core/v1/organizations/:organization/managed]: Unprocessable Entity (organization_limit_reached: The maxmium number of organizations that can be created has been reached)
        // This is about as close to verbose enough to narrow it down quite specifically without being overly long-winded
        // It is still _somewhat_ long-winded but not horrendously so

        $suffix = strlen($errorMessage) > 0 ? ' ' . $errorMessage : '';

        return sprintf(
            '%s [http: %d]: %s%s',
            $prefix,
            $httpStatusCode,
            $e->getMessage(),
            $suffix,
        );
    }

    if ($e->getCode() > 0) {
        return sprintf('%s [%d]: %s', $prefix, $e->getCode(), $e->getMessage());
    } else {
        return sprintf('%s: %s', $prefix, $e->getMessage());
    }
}
