<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult;

use KatapultAPI\Core\Client as KatapultAPIClient;
use KatapultAPI\Core\Model\OrganizationLookup;
use KatapultAPI\Core\Model\OrganizationsOrganizationManagedPostBody;
use KatapultAPI\Core\Model\OrganizationsOrganizationManagedPostResponse201;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

class ManagedOrganization
{
    private ParentOrganization $parentOrganization;

    public function __construct(
        private readonly KatapultAPIClient $katapultAPI,
        private readonly KeyValueStoreInterface $keyValueStore,
    ) {
        $this->parentOrganization = new ParentOrganization($this->katapultAPI, $this->keyValueStore);
    }

    /**
     * If the managed organization for the client is already known we can pull
     * it from the data store. Otherwise, we need to retrieve it from the API as
     * a one-off initialisation step.
     *
     * @throws Exception
     */
    public function getForClient(Client $client): OrganizationLookup
    {
        $existingOrgId = $client->dataStoreRead(Client::DS_MANAGED_ORG_ID);

        if ($existingOrgId) {
            return (new OrganizationLookup())->setId($existingOrgId);
        }

        // Get and check there is a parent org to use
        $parentOrganization = $this->parentOrganization->getParentOrganization();
        if (is_null($parentOrganization)) {
            throw new Exception('No parent organization has been set');
        }

        $managedOrganization = new OrganizationsOrganizationManagedPostBody();
        $managedOrganization->setOrganization($parentOrganization);
        $managedOrganization->setName($client->label);
        $managedOrganization->setSubDomain(substr(md5(microtime() . 'NMit6gvf8'), 0, 8));

        $postOrganizationManagedResponse = $this->katapultAPI->postOrganizationManaged($managedOrganization);

        if (!$postOrganizationManagedResponse instanceof OrganizationsOrganizationManagedPostResponse201) {
            throw APIException::new($postOrganizationManagedResponse, OrganizationsOrganizationManagedPostResponse201::class);
        }

        $newManagedOrganization = $postOrganizationManagedResponse->getOrganization();

        // Store it for next time
        $client->dataStoreWrite(Client::DS_MANAGED_ORG_ID, $newManagedOrganization->getId());

        // Log it
        KatapultWHMCS::log("Created managed organization: {$newManagedOrganization->getId()}", $client->id);

        // Send it home
        return (new OrganizationLookup())->setId($newManagedOrganization->getId());
    }
}
