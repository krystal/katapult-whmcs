<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult;

use KatapultAPI\Core\Client as KatapultAPIClient;
use KatapultAPI\Core\Model\OrganizationLookup;
use KatapultAPI\Core\Model\OrganizationsGetResponse200;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;

class ParentOrganization
{
    public function __construct(
        private readonly KatapultAPIClient $katapultAPI,
        private readonly KeyValueStoreInterface $keyValueStore,
    ) {
    }

    /**
     * @throws Exception
     */
    public function setParentOrganization(string $organization, bool $force = false): void
    {
        if (!$force && $this->getParentOrganization()->getId() === $organization) {
            return;
        }

        $this->keyValueStore->write(KatapultWHMCS::DS_PARENT_ORGANIZATION, $organization);

        if ($this->getParentOrganization()->getId() !== $organization) {
            KatapultWHMCS::log("Updated parent organization to: {$organization}");
        }
    }

    public function getIdentifier(): ?string
    {
        $parentOrg = $this->keyValueStore->read(KatapultWHMCS::DS_PARENT_ORGANIZATION);

        if ($parentOrg) {
            return $parentOrg;
        }

        // Try and fetch it from the API and store it for next time
        try {
            $organizationsResponse = $this->katapultAPI->getOrganizations();

            if (!$organizationsResponse instanceof OrganizationsGetResponse200) {
                throw APIException::new(
                    $organizationsResponse,
                    OrganizationsGetResponse200::class,
                );
            }

            if ($parentOrgObj = $organizationsResponse->getOrganizations()[0]) {
                $this->setParentOrganization($parentOrgObj->getId(), true);
                return $parentOrgObj->getId();
            }
        } catch (\Throwable $e) {
            // Nothing we can do...
        }

        return null;
    }

    public function getParentOrganization(): ?OrganizationLookup
    {
        $orgIdentifier = $this->getIdentifier();

        if (!$orgIdentifier) {
            return null;
        }

        $organizationLookup = new OrganizationLookup();

        if (str_starts_with($orgIdentifier, 'org_')) {
            $organizationLookup->setId($orgIdentifier);
        } else {
            $organizationLookup->setSubDomain($orgIdentifier);
        }

        return $organizationLookup;
    }
}
