<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult\API;

use Psr\Http\Message\ResponseInterface;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;

class APIException extends Exception
{
    protected string $expectedAPIResponseClass = '';

    protected int $httpStatusCode = 0;
    protected ResponseInterface|null $httpResponse = null;

    /**
     * @param \ArrayObject<string, mixed>|\Psr\Http\Message\ResponseInterface|null $apiResponse the type is from the openapi generated client
     * @param string $expectedAPIResponseClass the \ArrayObject above is actually going to be an instance of this type if it were successful
     */
    public static function new(
        mixed $apiResponse,
        string $expectedAPIResponseClass = '',
    ): self {
        $e = new self();

        $e->expectedAPIResponseClass = $expectedAPIResponseClass;

        if ($apiResponse instanceof ResponseInterface) {
            $e->httpStatusCode = $apiResponse->getStatusCode();
            $e->httpResponse = $apiResponse;
            $type = ResponseInterface::class;

            $messageSuffix = sprintf(
                ' HTTP response [%d]: %s',
                $e->httpStatusCode,
                $e->httpResponse->getBody()->getContents(),
            );
        } else {
            // gettype will just say object if it was a ResponseInterface, so
            // we've set that above instead. We're therefore expecting this to
            // be simply null
            $type = gettype($apiResponse);
            $messageSuffix = '';
        }

        $message = sprintf(
            'Response is type %s but was expected to be %s.%s',
            $type,
            $expectedAPIResponseClass,
            $messageSuffix,
        );

        $e->message = $message;

        return $e;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->httpResponse;
    }
}
