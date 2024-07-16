<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult\API;

use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;

class APIKey
{
    public function __construct(
        private readonly KeyValueStoreInterface $keyValueStore,
    ) {
    }

    public function getAPIKey(): ?string
    {
        $value = $this->keyValueStore->read(KatapultWHMCS::DS_API_V1_KEY);

        if (!$value) {
            return null;
        }

        return \decrypt($value);
    }

    public function setAPIKey(string $apiKey): void
    {
        $this->keyValueStore->write(KatapultWHMCS::DS_API_V1_KEY, \encrypt($apiKey));

        KatapultWHMCS::log("Updated API V1 key");
    }
}
