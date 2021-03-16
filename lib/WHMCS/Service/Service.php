<?php

namespace WHMCS\Module\Server\Katapult\WHMCS\Service;

use WHMCS\Module\Server\Katapult\Concerns\HasDataStoreValues;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

class Service extends \Grizzlyware\Salmon\WHMCS\Service\Service
{
	use HasDataStoreValues;

	protected function dataStoreRelType(): string
	{
		return 'service';
	}

	public function client()
	{
		return $this->belongsTo(Client::class, 'userid');
	}

	public function triggerHook(string $hook): void
	{
		try {
			\run_hook($hook, [
				'service' => $this,
			]);
		} catch (\Throwable $e) {
			$this->log("Failed to run hook: {$hook}: {$e->getMessage()}");
		}
	}
}

