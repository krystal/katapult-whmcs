<?php

namespace WHMCS\Module\Server\Katapult\Helpers;

use Grizzlyware\Salmon\WHMCS\Product\ConfigurableOptions\Group as ConfigOptionGroup;
use Grizzlyware\Salmon\WHMCS\Product\Product;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;

class WhmcsHelper
{
	public static function productIdIsKatapult(int $productId): bool
	{
		return Product::where('id', $productId)->where('servertype', KatapultWhmcs::SERVER_MODULE)->count() > 0;
	}

	public static function getOrCreateConfigOption(ConfigOptionGroup $group, string $name, $optionType, string $persistedOptionIdKey): ConfigOptionGroup\Option
	{
		// Have we already created a config option group for Katapult?
		$configOption = $group->options()->where('id',
			KatapultWhmcs::dataStoreRead($persistedOptionIdKey)
		)->first();

		// Nope? Create it..
		if (!$configOption) {
			$configOption = new ConfigOptionGroup\Option();
			$configOption->optionname = $name;
			$configOption->optiontype = $optionType;

			if (!$group->options()->save($configOption)) {
				throw new \Exception('Could not save config option');
			}

			KatapultWhmcs::dataStoreWrite($persistedOptionIdKey, $configOption->id);
		}

		return $configOption;
	}
}
