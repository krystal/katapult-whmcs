<?php

declare(strict_types=1);

namespace Krystal\KatapultTest\Katapult\API;

use Krystal\Katapult\KatapultAPI\Client;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\Katapult\Katapult\API\APIFactory;
use WHMCS\Module\Server\Katapult\Katapult\API\APIKey;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\ArrayKeyValueStore;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class APIFactoryTest extends TestCase
{
    #[Test]
    public function katapult_returns_Katapult_API_client(): void
    {
        putenv('KATAPULT_API_HOST=api.example.org');
        $apiHost = getenv('KATAPULT_API_HOST') ?? null;

        $APIKey = new APIKey(new ArrayKeyValueStore());
        $APIKey->setAPIKey('foobar');
        $katapult = (new APIFactory($APIKey))->getKatapultAPIClient($apiHost);
        $this->assertInstanceOf(Client::class, $katapult);
    }

    #[Test]
    public function katapult_throw_no_exception_when_API_key_not_set(): void
    {
        putenv('KATAPULT_API_HOST=api.example.org');
        $apiHost = getenv('KATAPULT_API_HOST') ?? null;

        $APIKey = new APIKey(new ArrayKeyValueStore());
        $this->expectNotToPerformAssertions();
        (new APIFactory($APIKey))->getKatapultAPIClient($apiHost);
    }
}
