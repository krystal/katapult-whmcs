<?php

namespace WHMCS\Module\Server\Katapult\WHMCS\Service;

use WHMCS\Module\Server\Katapult\Concerns\HasDataStoreValues;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

class Service extends \Grizzlyware\Salmon\WHMCS\Service\Service
{
	use HasDataStoreValues;

	const DS_VM_BUILD_ID = 'vm_build_id';

	public function client()
	{
		return $this->belongsTo(Client::class, 'userid');
	}
}


