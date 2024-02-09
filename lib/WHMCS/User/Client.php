<?php

namespace WHMCS\Module\Server\Katapult\WHMCS\User;

use Krystal\Katapult\KatapultAPI\Model\OrganizationLookup;
use Krystal\Katapult\KatapultAPI\Model\OrganizationsOrganizationManagedPostBody;
use WHMCS\Module\Server\Katapult\Concerns\HasDataStoreValues;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;

/**
 * Class Client
 * @package WHMCS\Module\Server\Katapult\WHMCS\User
 *
 * @property-read OrganizationLookup $managed_organization
 */
class Client extends \Grizzlyware\Salmon\WHMCS\User\Client
{
    use HasDataStoreValues;

    public const DS_MANAGED_ORG_ID = 'managed_org_id';

    protected function dataStoreRelType(): string
    {
        return 'client';
    }

    protected function getManagedOrganizationAttribute(): OrganizationLookup
    {
        $existingOrgId = $this->dataStoreRead(self::DS_MANAGED_ORG_ID);

        if ($existingOrgId) {
            return (new OrganizationLookup())->setId($existingOrgId);
        }

        // Get and check there is a parent org to use
        $parentOrg = katapultOrg();
        if (!$parentOrg) {
            throw new Exception('No parent organization has been set, unable to create managed organization');
        }

        $managedOrganization = new OrganizationsOrganizationManagedPostBody();
        $managedOrganization->setOrganization($parentOrg);
        $managedOrganization->setName($this->label);
        $managedOrganization->setSubDomain(substr(md5(microtime() . 'NMit6gvf8'), 0, 8));

        $newManagedOrganization = katapult()->postOrganizationManaged($managedOrganization)->getOrganization();

        // Store it for next time
        $this->dataStoreWrite(self::DS_MANAGED_ORG_ID, $newManagedOrganization->getId());

        // Log it
        $this->log("Created managed organization: {$newManagedOrganization->getId()}");

        // Send it home
        return (new OrganizationLookup())->setId($newManagedOrganization->getId());
    }

    public function log($message)
    {
        parent::log("[Katapult]: {$message}");
    }
}
