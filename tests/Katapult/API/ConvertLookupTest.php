<?php

declare(strict_types=1);

namespace Krystal\KatapultTest\Katapult\API;

use Krystal\Katapult\KatapultAPI\Model\OrganizationLookup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\Katapult\Katapult\API\ConvertLookup;

class ConvertLookupTest extends TestCase
{
    #[Test]
    public function it_uses_id_when_id_is_initialised(): void
    {
        $organizationLookup = new OrganizationLookup();
        $organizationLookup->setId('org_42');

        $result = ConvertLookup::organizationLookupToQueryParameters($organizationLookup);

        $expected = [
            'organization[id]' => 'org_42',
        ];

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_uses_subdomain_when_id_is_not_initialised(): void
    {
        $organizationLookup = new OrganizationLookup();
        $organizationLookup->setSubDomain('org42.example.org');

        $result = ConvertLookup::organizationLookupToQueryParameters($organizationLookup);

        $expected = [
            'organization[sub_domain]' => 'org42.example.org',
        ];

        $this->assertEquals($expected, $result);
    }
}
