<?php

namespace WHMCS\Module\Server\Katapult;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Grizzlyware\Salmon\WHMCS\Helpers\DataStore;
use Grizzlyware\Salmon\WHMCS\Product\ConfigurableOptions\Group as ConfigOptionGroup;
use Grizzlyware\Salmon\WHMCS\Product\Product;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Krystal\Katapult\KatapultAPI\Client as KatapultApiClient;
use Krystal\Katapult\KatapultAPI\ClientFactory;
use Krystal\Katapult\KatapultAPI\Model\DiskBackupPoliciesDiskBackupPolicyDeleteBody;
use Krystal\Katapult\KatapultAPI\Model\DiskBackupPoliciesDiskBackupPolicyDeleteResponse200;
use Krystal\Katapult\KatapultAPI\Model\DiskBackupPolicyLookup;
use Krystal\Katapult\KatapultAPI\Model\DisksDiskDiskBackupPoliciesGetResponse200;
use Krystal\Katapult\KatapultAPI\Model\OrganizationLookup;
use WHMCS\Module\Server\Katapult\Adaptation\System as SystemAdaptation;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\Helpers\WhmcsHelper;
use WHMCS\Module\Server\Katapult\Katapult\ApiV1Logger;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VmServerModuleParams;

class KatapultWhmcs
{
    private static ?KatapultApiClient $katapult = null;

    public const SERVER_MODULE = 'katapult';

    /**
     * Module return types
     * WHMCS expects inconsistent return types from module functions.
     * These are used by KatapultWhmcs::runModuleCommandOnVm() to allow
     * it to return the correct response in the event of an error.
     */
    public const MRT_STRING = 'string';
    public const MRT_SSO = 'sso';

    public const DS_API_V1_KEY = 'api_v1_key';
    public const DS_PARENT_ORGANIZATION = 'parent_organization';

    public const DS_VM_CONFIG_OPTION_GROUP_ID = 'vm_config_option_group_id';
    public const DS_VM_CONFIG_OPTION_DATACENTER_ID = 'vm_config_option_datacenter_id';
    public const DS_VM_CONFIG_OPTION_DISK_TEMPLATE_ID = 'vm_config_option_disk_template_id';

    protected static function createApiV1HandlerStack(): HandlerStack
    {
        $handlerStack = HandlerStack::create();

        $handlerStack->push(
            Middleware::log(
                new ApiV1Logger(),
                new MessageFormatter(
                    <<<EOF
{method} {target}

__KATAPULT_REQUEST__
{ts}
{request}

__KATAPULT_RESPONSE__
{response}
EOF
                )
            )
        );

        return $handlerStack;
    }

    public static function getKatapult(): KatapultApiClient
    {
        if (self::$katapult === null) {
            if (!KatapultWhmcs::getApiV1Key()) {
                throw new Exception('No API key set');
            }

            $clientFactory = new ClientFactory(KatapultWhmcs::getApiV1Key());
            $clientFactory->setHttpClient(new Client([
                'handler' => self::createApiV1HandlerStack(),
                'timeout' => 5.0,
            ]));
            self::$katapult = $clientFactory->create();
        }

        return self::$katapult;
    }

    public static function convertToQueryParameters(OrganizationLookup $organizationLookup): array
    {
        if ($organizationLookup->isInitialized('id')) {
            return [
                'organization[id]' => $organizationLookup->getId(),
            ];
        } else {
            return [
                'organization[sub_domain]' => $organizationLookup->getSubDomain(),
            ];
        }
    }

    public static function dataStoreRead(string $key)
    {
        return DataStore::get('module_setting', 'katapult', $key);
    }

    public static function dataStoreWrite(string $key, $value)
    {
        DataStore::set('module_setting', 'katapult', $key, $value);
    }

    public static function getApiV1Key(): ?string
    {
        $value = KatapultWhmcs::dataStoreRead(self::DS_API_V1_KEY);

        if (!$value) {
            return null;
        }

        return \decrypt($value);
    }

    public static function setApiV1Key(string $apiKey): void
    {
        KatapultWhmcs::dataStoreWrite(self::DS_API_V1_KEY, \encrypt($apiKey));

        self::log("Updated API V1 key");
    }

    public static function setParentOrganization(string $organization, bool $force = false): void
    {
        if (!$force && self::getParentOrganization()->getId() === $organization) {
            return;
        }

        KatapultWhmcs::dataStoreWrite(self::DS_PARENT_ORGANIZATION, $organization);

        if (self::getParentOrganization()->getId() !== $organization) {
            self::log("Updated parent organization to: {$organization}");
        }
    }

    public static function getParentOrganizationIdentifier(): ?string
    {
        $parentOrg = KatapultWhmcs::dataStoreRead(self::DS_PARENT_ORGANIZATION);

        if ($parentOrg) {
            return $parentOrg;
        }

        // Try and fetch it from the API and store it for next time
        try {
            if ($parentOrgObj = \katapult()->getOrganizations()->getOrganizations()[0]) {
                self::setParentOrganization($parentOrgObj->getId(), true);
                return $parentOrgObj->getId();
            }
        } catch (\Throwable $e) {
            // Nothing we can do..
        }

        return null;
    }

    public static function getParentOrganization(): ?OrganizationLookup
    {
        $orgIdentifier = self::getParentOrganizationIdentifier();

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

    public static function log(string $message): void
    {
        \logActivity("[Katapult]: {$message}");
    }

    public static function moduleLog(string $action, $request, $response): void
    {
        \logModuleCall(KatapultWhmcs::SERVER_MODULE, $action, $request, $response, '', [self::getApiV1Key()]);
    }

    public static function deleteDiskBackupPolciesForVm(VirtualMachine $virtualMachine): void
    {
        // Delete disk backup policies
        $disks = katapult()->getVirtualMachineDisks([
            'virtual_machine[id]' => $virtualMachine->vm_id,
        ])->getDisks();

        foreach ($disks as $disk) {
            $diskBackupPolicies = katapult()->getDiskDiskBackupPolicies(['disk[id]' => $disk->getDisk()->getId()]);

            katapultHandleApiResponse(
                DisksDiskDiskBackupPoliciesGetResponse200::class,
                $diskBackupPolicies,
                $virtualMachine,
                null,
                'Error getting disk backup policies',
                function () use ($diskBackupPolicies, $virtualMachine) {
                    foreach ($diskBackupPolicies->getDiskBackupPolicies() as $diskBackupPolicy) {
                        $requestBody = new DiskBackupPoliciesDiskBackupPolicyDeleteBody();
                        $diskBackupPolicyLookup = new DiskBackupPolicyLookup();
                        $diskBackupPolicyLookup->setId($diskBackupPolicy->getId());
                        $requestBody->setDiskBackupPolicy($diskBackupPolicyLookup);

                        $deleteResult = katapult()->deleteDiskBackupPolicy($requestBody);

                        katapultHandleApiResponse(
                            DiskBackupPoliciesDiskBackupPolicyDeleteResponse200::class,
                            $deleteResult,
                            $virtualMachine,
                            'Deleted disk backup policy ' . $diskBackupPolicy->getId(),
                            'Error deleting disk backup policy ' . $diskBackupPolicy->getId()
                        );
                    }
                }
            );
        }
    }

    /**
     * Creates a config option group called Katapult, and assigns it to the Katapult products.
     *
     * @throws Exception
     */
    public static function syncConfigurableOptions(): void
    {
        // Have we already created a config option group for Katapult?
        $configOptionGroup = ConfigOptionGroup::find(
            KatapultWhmcs::dataStoreRead(self::DS_VM_CONFIG_OPTION_GROUP_ID)
        );

        // Nope? Create it..
        if (!$configOptionGroup) {
            $configOptionGroup = new ConfigOptionGroup();
            $configOptionGroup->name = 'Katapult Virtual Machines';

            if (!$configOptionGroup->save()) {
                throw new Exception('Could not save config option group');
            }

            KatapultWhmcs::dataStoreWrite(self::DS_VM_CONFIG_OPTION_GROUP_ID, $configOptionGroup->id);

            // Assign it to all of the Katapult products
            $configOptionGroup->products()->attach(
                Product::where('servertype', KatapultWhmcs::SERVER_MODULE)->pluck('id')->toArray()
            );
        }

        // This will fetch DCs, disk templates from Katapult and sync them with WHMCS configurable options.
        // If there is no existing config option, it will create all elements as visible, else it will add
        // new ones as hidden for an admin to un-hide as required.

        /**
         * Data center option
         */

        // Fetch the DC option
        $dataCenterOption = WhmcsHelper::getOrCreateConfigOption(
            $configOptionGroup,
            'Data Center',
            1,
            self::DS_VM_CONFIG_OPTION_DATACENTER_ID
        );

        // Create options for the DCs
        foreach (\katapult()->getDataCenters()->getDataCenters() as $dataCenter) {
            // Already got it, skip
            if (
                $dataCenterOption->subOptions()->where('optionname', 'LIKE', "{$dataCenter->getPermalink()}|%")->count(
                ) > 0
            ) {
                continue;
            }

            // Create the option
            $currentOption = new ConfigOptionGroup\Option\SubOption();
            $currentOption->optionname = "{$dataCenter->getPermalink()}|{$dataCenter->getName()}";
            $currentOption->hidden = $dataCenterOption->wasRecentlyCreated ? 0 : 1;

            // Persist the option
            if (!$dataCenterOption->subOptions()->save($currentOption)) {
                throw new Exception('Could not save data center: ' . $dataCenter->getName());
            }

            // Create free pricing for the new option
            WhmcsHelper::createFreePricingForObject('configoptions', $currentOption->id);
        }

        /**
         * Disk template option
         */

        // Fetch the DC option
        $diskTemplateOption = WhmcsHelper::getOrCreateConfigOption(
            $configOptionGroup,
            'Disk Template',
            1,
            self::DS_VM_CONFIG_OPTION_DISK_TEMPLATE_ID
        );

        // Create options for the templates
        $queryParameters = self::convertToQueryParameters(katapultOrg());
        // include universal templates provided by Katapult alongside the org's custom ones
        $queryParameters['include_universal'] = true;
        $diskTemplates = \katapult()->getOrganizationDiskTemplates($queryParameters);

        foreach ($diskTemplates->getDiskTemplates() as $diskTemplate) {
            // Already got it, skip
            if (
                $diskTemplateOption->subOptions()->where('optionname', 'LIKE', "{$diskTemplate->getPermalink()}|%")->count(
                ) > 0
            ) {
                continue;
            }

            // Create the option
            $currentOption = new ConfigOptionGroup\Option\SubOption();
            $currentOption->optionname = "{$diskTemplate->getPermalink()}|{$diskTemplate->getName()}";
            $currentOption->hidden = $diskTemplateOption->wasRecentlyCreated ? 0 : 1;

            // Persist the option
            if (!$diskTemplateOption->subOptions()->save($currentOption)) {
                throw new Exception('Could not save disk template: ' . $diskTemplate->getName());
            }

            // Create free pricing for the new option
            WhmcsHelper::createFreePricingForObject('configoptions', $currentOption->id);
        }
    }

    public static function syncVmBuilds(): void
    {
        $log = function ($message, VirtualMachine $service = null) {
            $message = "[SyncVmBuilds]: {$message}";

            if ($service) {
                $service->log($message);
            } else {
                self::log($message);
            }
        };

        // Find Katapult services with a build ID but not a VM ID, and sync them.
        $sql = <<<SQL
SELECT DISTINCT tblhosting.id as id
FROM tblhosting
         INNER JOIN tblproducts ON tblproducts.id = tblhosting.packageid

WHERE domainstatus = 'Active'
  AND tblproducts.servertype = 'katapult'
  AND EXISTS(SELECT id
             FROM mod_salmon_data_store_items
             WHERE rel_id = tblhosting.id
               AND rel_type = 'service'
               AND `key` = 'vm_build_id'
               AND value_index IS NOT NULL)

  AND NOT EXISTS(SELECT id
                 FROM mod_salmon_data_store_items
                 WHERE rel_id = tblhosting.id
                   AND rel_type = 'service'
                   AND `key` = 'vm_id'
                   AND value_index IS NOT NULL);
SQL;

        $sql = WhmcsHelper::getPdo()->query($sql);

        // Nothing to do..
        if ($sql->rowCount() < 1) {
            return;
        }

        $log(sprintf("Found %d VM builds", $sql->rowCount()));

        while ($row = $sql->fetch(\PDO::FETCH_ASSOC)) {
            try {
                /** @var VirtualMachine $service */
                $service = VirtualMachine::findOrFail($row['id']);
                $log("Starting", $service);
                $service->checkForExistingBuildAttempt();
            } catch (\Throwable $e) {
                if (isset($service) && $service) {
                    $log("Error: {$e->getMessage()}", $service);
                } else {
                    $log("Error: Service ID: " . $row['id'] . ": {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * @param array $params
     * @param callable $command
     * @param string $returnType
     * @param bool $checkServiceIsActive
     *
     * @return string|array
     */
    public static function runModuleCommandOnVm(
        array $params,
        callable $command,
        string $returnType = self::MRT_STRING,
        bool $checkServiceIsActive = true
    ) {
        $formatErrorResponse = function (string $error) use ($returnType) {
            switch ($returnType) {
                case self::MRT_STRING:
                    return $error;

                case self::MRT_SSO:
                    return [
                        'success' => false,
                        'errorMsg' => $error,
                    ];
            }
        };

        try {
            SystemAdaptation::validateNoReplayTokenForClientArea();

            $params = new VmServerModuleParams($params);

            // Check the service is active
            if ($checkServiceIsActive && $params->service->domainstatus != 'Active') {
                throw new Exception('This service is not currently active');
            }

            // In case it's just been built!
            $params->service->silentlyCheckForExistingBuildAttempt();

            // Check there is a VM..
            if (!$params->service->vm_id) {
                throw new Exception('There is no VM ID set for this service');
            }

            $return = $command($params);

            if ($return) {
                return $return;
            }

            return 'success';
        } catch (\Throwable $e) {
            return katapultFormatError('Module command', $e);
        }
    }
}
