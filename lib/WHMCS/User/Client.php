<?php

namespace WHMCS\Module\Server\Katapult\WHMCS\User;

use Krystal\Katapult\Resources\Organization;
use Krystal\Katapult\Resources\Organization\ManagedOrganization;
use WHMCS\Module\Server\Katapult\Concerns\HasDataStoreValues;

/**
 * Class Client
 * @package WHMCS\Module\Server\Katapult\WHMCS\User
 *
 * @property-read ManagedOrganization $managed_organization
 */
class Client extends \Grizzlyware\Salmon\WHMCS\User\Client
{
	use HasDataStoreValues;

	const DS_MANAGED_ORG_ID = 'managed_org_id';

	protected function dataStoreRelType(): string
	{
		return 'client';
	}

	protected function getManagedOrganizationAttribute(): Organization
	{
		$existingOrgId = $this->dataStoreRead(self::DS_MANAGED_ORG_ID);

		if ($existingOrgId) {
			return Organization::instantiateFromSpec((object)[
				'id' => $existingOrgId
			]);
		}

		/** @var ManagedOrganization $managedOrg */
		$managedOrg = katapult()->resource(ManagedOrganization::class, katapultOrg())->create([
			'name' => $this->label,
			'sub_domain' => substr(md5(microtime() . 'NMit6gvf8'), 0, 8)
		]);

		// Store it for next time
		$this->dataStoreWrite(self::DS_MANAGED_ORG_ID, $managedOrg->id);

		// Log it
		$this->log("Created managed organization: {$managedOrg->id}");

		// Send it home
		return Organization::instantiateFromSpec((object)[
			'id' => $managedOrg
		]);
	}

	public function log($message)
	{
		parent::log("[Katapult]: {$message}");
	}
}


