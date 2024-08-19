<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult\KeyValueStore;

class ArrayKeyValueStore implements KeyValueStoreInterface
{
    public array $data = [];

    public function read(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function write(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}
