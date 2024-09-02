<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult\API;

use KatapultAPI\Core\Model\OrganizationLookup;

class ConvertLookup
{
    /**
     * The API supports locating the Organization via its ID or its subdomain.
     * If we know the ID we use it, otherwise we use the subdomain.
     */
    public static function organizationLookupToQueryParameters(OrganizationLookup $organizationLookup): array
    {
        if ($organizationLookup->isInitialized('id')) {
            return [
                'organization[id]' => $organizationLookup->getId(),
            ];
        }

        return [
            'organization[sub_domain]' => $organizationLookup->getSubDomain(),
        ];
    }
}
