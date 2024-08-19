<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult\KeyValueStore;

use Grizzlyware\Salmon\WHMCS\Helpers\DataStore;

class SalmonKeyValueStore implements KeyValueStoreInterface
{
    public function read(string $key): mixed
    {
        return DataStore::get('module_setting', 'katapult', $key);
    }

    public function write(string $key, mixed $value): void
    {
        DataStore::set('module_setting', 'katapult', $key, $value);
    }
}
