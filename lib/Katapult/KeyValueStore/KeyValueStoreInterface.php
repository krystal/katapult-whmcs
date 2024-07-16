<?php

namespace WHMCS\Module\Server\Katapult\Katapult\KeyValueStore;

interface KeyValueStoreInterface
{
    public function read(string $key): mixed;

    public function write(string $key, mixed $value): void;
}
