<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult\Sync;

use Grizzlyware\Salmon\WHMCS\Billing\Currency;
use Grizzlyware\Salmon\WHMCS\Product\Product;
use Grizzlyware\Salmon\WHMCS\Product\ConfigurableOptions\Group as ConfigOptionGroup;
use Krystal\Katapult\KatapultAPI\Client as KatapultAPIClient;
use Krystal\Katapult\KatapultAPI\Model\DataCentersGetResponse200;
use Krystal\Katapult\KatapultAPI\Model\OrganizationsOrganizationDiskTemplatesGetResponse200;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;
use WHMCS\Module\Server\Katapult\Katapult\API\ConvertLookup;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\Katapult\ParentOrganization;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;

class ConfigurableOptions
{
    private ParentOrganization $parentOrganization;

    public function __construct(
        private readonly KeyValueStoreInterface $keyValueStore,
        private readonly KatapultAPIClient $katapultAPI,
    ) {
        $this->parentOrganization = new ParentOrganization($this->katapultAPI, $this->keyValueStore);
    }

    /**
     * Creates a config option group called Katapult, and assigns it to the Katapult products.
     *
     * This will fetch DCs, disk templates from Katapult and sync them with WHMCS configurable options.
     * If there is no existing config option, it will create all elements as visible, else it will add
     * new ones as hidden for an admin to un-hide as required.
     *
     * @throws APIException|Exception
     */
    public function sync(): void
    {
        $configOptionGroup = $this->getOrCreateConfigOptionGroup();

        $this->syncDataCentersToConfigOptions($configOptionGroup);
        $this->syncDiskTemplatesToConfigOptions($configOptionGroup);
        $this->createCustomDiskSizeConfigOption($configOptionGroup);
    }

    /**
     * @throws Exception
     */
    private function getOrCreateConfigOptionGroup(): ConfigOptionGroup
    {
        // Have we already created a config option group for Katapult?
        $configOptionGroup = ConfigOptionGroup::find(
            $this->keyValueStore->read(KatapultWHMCS::DS_VM_CONFIG_OPTION_GROUP_ID)
        );

        // Nope? Create it...
        if (!$configOptionGroup) {
            $configOptionGroup = new ConfigOptionGroup();
            $configOptionGroup->name = 'Katapult Virtual Machines';

            if (!$configOptionGroup->save()) {
                throw new Exception('Could not save config option group');
            }

            $this->keyValueStore->write(KatapultWHMCS::DS_VM_CONFIG_OPTION_GROUP_ID, $configOptionGroup->id);

            // Assign it to all of the Katapult products
            $configOptionGroup->products()->attach(
                Product::where('servertype', KatapultWHMCS::SERVER_MODULE)->pluck('id')->toArray()
            );
        }

        return $configOptionGroup;
    }

    /**
     * @throws Exception
     * @throws APIException
     */
    private function syncDataCentersToConfigOptions(ConfigOptionGroup $configOptionGroup): void
    {
        $dataCenterOption = $this->getOrCreateConfigOption(
            $configOptionGroup,
            'Data Center',
            1,
            KatapultWHMCS::DS_VM_CONFIG_OPTION_DATACENTER_ID
        );

        $dataCentersResponse = $this->katapultAPI->getDataCenters();

        if (!$dataCentersResponse instanceof DataCentersGetResponse200) {
            throw APIException::new(
                $dataCentersResponse,
                DataCentersGetResponse200::class,
            );
        }

        foreach ($dataCentersResponse->getDataCenters() as $dataCenter) {
            if ($this->itemExistsAsSubOption($dataCenterOption, $dataCenter->getPermalink())) {
                continue;
            }

            $currentOption = $this->createNewSubOption(
                $dataCenterOption,
                $dataCenter->getPermalink(),
                $dataCenter->getName(),
            );

            // Persist the option
            if (!$dataCenterOption->subOptions()->save($currentOption)) {
                throw new Exception('Could not save data center: ' . $dataCenter->getName());
            }

            // Create free pricing for the new option
            $this->createFreePricingForObject($currentOption->id);
        }
    }

    /**
     * @throws Exception
     */
    private function syncDiskTemplatesToConfigOptions(ConfigOptionGroup $configOptionGroup): void
    {
        $diskTemplateOption = $this->getOrCreateConfigOption(
            $configOptionGroup,
            'Disk Template',
            1,
            KatapultWHMCS::DS_VM_CONFIG_OPTION_DISK_TEMPLATE_ID
        );

        $organizationLookup = $this->parentOrganization->getParentOrganization();
        $queryParameters = ConvertLookup::organizationLookupToQueryParameters($organizationLookup);

        // include universal templates provided by Katapult alongside the org's custom ones
        $queryParameters['include_universal'] = true;

        $diskTemplatesResponse = $this->katapultAPI->getOrganizationDiskTemplates($queryParameters);

        if (!$diskTemplatesResponse instanceof OrganizationsOrganizationDiskTemplatesGetResponse200) {
            throw APIException::new(
                $diskTemplatesResponse,
                OrganizationsOrganizationDiskTemplatesGetResponse200::class,
            );
        }

        foreach ($diskTemplatesResponse->getDiskTemplates() as $diskTemplate) {
            if ($this->itemExistsAsSubOption($diskTemplateOption, $diskTemplate->getPermalink())) {
                continue;
            }

            $currentOption = $this->createNewSubOption(
                $diskTemplateOption,
                $diskTemplate->getPermalink(),
                $diskTemplate->getName(),
            );

            if (!$diskTemplateOption->subOptions()->save($currentOption)) {
                throw new Exception('Could not save disk template: ' . $diskTemplate->getName());
            }

            // Create free pricing for the new option
            $this->createFreePricingForObject($currentOption->id);
        }
    }

    private function createCustomDiskSizeConfigOption(ConfigOptionGroup $configOptionGroup): void
    {
        $option = $this->getOrCreateConfigOption(
            $configOptionGroup,
            'Custom Disk Size (GB)',
            4, // quantity
            KatapultWHMCS::DS_VM_CONFIG_OPTION_CUSTOM_DISK_SIZE_ID,
        );

        if (!$this->itemExistsAsSubOption($option, 'price-per-gb')) {
            $priceLineOption = $this->createNewSubOption(
                $option,
                'price-per-gb',
                'Price Per GB',
            );
            $option->subOptions()->save($priceLineOption);
        }

        // Setting a minimum and maximum causes WHMCS to render it as a slider.
        // We don't do this by default because the maximum disk size possible
        // can be very large. This means the realistic used range by end users
        // would be a small subsection of the slider and thus clunky UX.
//        $option->qtyminimum = 0;
//        $option->qtymaximum = 1000;
//        $option->save();

        // Create free pricing for the new option
        $this->createFreePricingForObject($option->id);
    }

    private function getOrCreateConfigOption(
        ConfigOptionGroup $group,
        string $name,
        int $optionType,
        string $persistedOptionIdKey
    ): ConfigOptionGroup\Option {
        // Have we already created a config option group for Katapult?
        $configOption = $group->options()->where(
            'id',
            $this->keyValueStore->read($persistedOptionIdKey)
        )->first();

        // Nope? Create it...
        if (!$configOption) {
            $configOption = new ConfigOptionGroup\Option();
            $configOption->optionname = $name;
            $configOption->optiontype = $optionType;

            if (!$group->options()->save($configOption)) {
                throw new Exception('Could not save config option');
            }

            $this->keyValueStore->write($persistedOptionIdKey, $configOption->id);
        }

        return $configOption;
    }

    private function itemExistsAsSubOption(
        ConfigOptionGroup\Option $configOption,
        string $permalink,
    ): bool {
        return $configOption
                ->subOptions()
                ->where('optionname', 'LIKE', "$permalink|%")
                ->count() > 0;
    }

    private function createNewSubOption(
        ConfigOptionGroup\Option $configOption,
        string $permalink,
        string $name,
    ): ConfigOptionGroup\Option\SubOption {
        $currentOption = new ConfigOptionGroup\Option\SubOption();
        $currentOption->optionname = "$permalink|$name";
        $currentOption->hidden = $configOption->wasRecentlyCreated ? 0 : 1;

        return $currentOption;
    }

    private function createFreePricingForObject(int $relId): void
    {
        $type = 'configoptions';

        Capsule::table('tblpricing')->insert(
            Currency::all()->map(function (Currency $currency) use ($type, $relId) {
                return [
                    'type' => $type,
                    'relid' => $relId,
                    'currency' => $currency->id,
                    'msetupfee' => 0,
                    'qsetupfee' => 0,
                    'ssetupfee' => 0,
                    'asetupfee' => 0,
                    'bsetupfee' => 0,
                    'tsetupfee' => 0,
                    'monthly' => 0,
                    'quarterly' => 0,
                    'semiannually' => 0,
                    'annually' => 0,
                    'biennially' => 0,
                    'triennially' => 0,
                ];
            })->toArray()
        );
    }
}
