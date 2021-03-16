<?php

namespace WHMCS\Module\Server\Katapult\Concerns;

use Grizzlyware\Salmon\WHMCS\Helpers\DataStore;

trait HasDataStoreValues
{
	abstract protected function dataStoreRelType(): string;

	public function dataStoreWrite(string $key, $value, $indexedValue = null): void
	{
		DataStore::set($this->dataStoreRelType(), $this->id, $key, $value, $indexedValue);
	}

	public function dataStoreRead(string $key)
	{
		return DataStore::get($this->dataStoreRelType(), $this->id, $key);
	}

	public function clearAllDataStoreValues(): void
	{
		DataStore\Item::relType($this->dataStoreRelType())->relId($this->id)->delete();
	}
}


