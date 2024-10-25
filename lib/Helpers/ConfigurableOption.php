<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Helpers;

use Grizzlyware\Salmon\WHMCS\Product\ConfigurableOptions\Group\Option;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;
use WHMCS\Module\Server\Katapult\WHMCS\ChosenConfigurableOptionValue;

class ConfigurableOption
{
    public function __construct(
        private readonly KeyValueStoreInterface $keyValueStore,
    ) {
    }

    public function getChosenValue(int $configurableOptionId, int $optionIdOrQuantity): ChosenConfigurableOptionValue
    {
        $optionType = Option::findOrFail($configurableOptionId)->type;

        // If it's not a quantity type, attempt to read the underlying value from the Option
        // If there's a | char in there, it's a friendly display name (see below)
        if ($optionType !== Option::TYPE_QUANTITY) {
            $optionValue = Capsule::table('tblproductconfigoptionssub')
                ->where('id', $optionIdOrQuantity)
                ->value('optionname');

            if ($this->valueHasFriendlyDisplayName($optionValue)) {
                [$rawValue, $valueName] = explode('|', $optionValue, 2);
            } else {
                $rawValue = $optionValue;
                $valueName = null;
            }

            return new ChosenConfigurableOptionValue($rawValue, $valueName);
        }

        return new ChosenConfigurableOptionValue($optionIdOrQuantity);
    }

    public function diskTemplateConfigurableOptionId(): int
    {
        return $this->keyValueStore->read(KatapultWHMCS::DS_VM_CONFIG_OPTION_DISK_TEMPLATE_ID);
    }

    public function customDiskSizeConfigurableOptionId(): int
    {
        return $this->keyValueStore->read(KatapultWHMCS::DS_VM_CONFIG_OPTION_CUSTOM_DISK_SIZE_ID);
    }

    /**
     * Pipe separated configurable option values are in the format "raw_value|User-Facing Name"
     * @see https://docs.whmcs.com/products/configuration-options/configurable-options/#friendly-display-names
     */
    private function valueHasFriendlyDisplayName(string $optionValue): bool
    {
        return str_contains($optionValue, '|');
    }
}
