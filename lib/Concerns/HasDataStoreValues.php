<?php

namespace WHMCS\Module\Server\Katapult\Concerns;

use Grizzlyware\Salmon\WHMCS\Helpers\DataStore;

trait HasDataStoreValues
{
	public function dataStoreWrite(string $key, $value): void
	{
		DataStore::set(get_class($this), $this->id, $key, $value);
	}

	public function dataStoreRead(string $key)
	{
		return DataStore::get(get_class($this), $this->id, $key);
	}
}


