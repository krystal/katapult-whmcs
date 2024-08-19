<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult\API;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Krystal\Katapult\KatapultAPI\Client as KatapultAPIClient;
use Krystal\Katapult\KatapultAPI\ClientFactory;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;

class APIFactory
{
    private static ?KatapultAPIClient $katapultAPI = null;

    public function __construct(
        private readonly APIKey $APIKey,
    ) {
    }


    public function getKatapultAPIClient(?string $host = null): KatapultAPIClient
    {
        if (!is_null(self::$katapultAPI)) {
            return self::$katapultAPI;
        }

        $apiKey = $this->APIKey->getAPIKey();

        if (!$apiKey) {
            // empty string here not an exception
            // until the api key has been configured requests will just fail
            $apiKey = '';
        }

        $clientFactory = new ClientFactory($apiKey);

        $clientFactory->setHttpClient(new Client([
            'handler' => $this->createApiV1HandlerStack($apiKey),
            'timeout' => 5.0,
        ]));

        if (!is_null($host)) {
            $clientFactory->setHost($host);
        }

        self::$katapultAPI = $clientFactory->create();

        return self::$katapultAPI;
    }

    protected function createApiV1HandlerStack(string $apiKey): HandlerStack
    {
        $handlerStack = HandlerStack::create();

        $handlerStack->push(
            Middleware::log(
                new APILogger($apiKey),
                new MessageFormatter(
                    <<<EOF
{method} {target}

__KATAPULT_REQUEST__
{ts}
{request}

__KATAPULT_RESPONSE__
{response}
EOF
                )
            )
        );

        return $handlerStack;
    }
}
