<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult\API;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;

class APILogger implements LoggerInterface
{
    public function __construct(
        private readonly string $apiKey
    ) {
    }

    public function log($level, $message, array $context = [])
    {
        // Split the message up
        $action = explode('__KATAPULT_REQUEST__', $message, 2);

        // Request and response..
        $reqRes = explode('__KATAPULT_RESPONSE__', $action[1], 2);

        // Log it
        KatapultWHMCS::moduleLog(
            trim($action[0]),
            trim($reqRes[0]),
            trim($reqRes[1]),
            $this->apiKey,
        );
    }

    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
