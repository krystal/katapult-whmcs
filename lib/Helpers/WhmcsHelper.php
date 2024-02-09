<?php

namespace WHMCS\Module\Server\Katapult\Helpers;

use Grizzlyware\Salmon\WHMCS\Billing\Currency;
use Grizzlyware\Salmon\WHMCS\Product\ConfigurableOptions\Group as ConfigOptionGroup;
use Grizzlyware\Salmon\WHMCS\Product\Product;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;

class WhmcsHelper
{
    public static function productIdIsKatapult(int $productId): bool
    {
        return Product::where('id', $productId)->where('servertype', KatapultWhmcs::SERVER_MODULE)->count() > 0;
    }

    public static function getOrCreateConfigOption(ConfigOptionGroup $group, string $name, $optionType, string $persistedOptionIdKey): ConfigOptionGroup\Option
    {
        // Have we already created a config option group for Katapult?
        $configOption = $group->options()->where(
            'id',
            KatapultWhmcs::dataStoreRead($persistedOptionIdKey)
        )->first();

        // Nope? Create it..
        if (!$configOption) {
            $configOption = new ConfigOptionGroup\Option();
            $configOption->optionname = $name;
            $configOption->optiontype = $optionType;

            if (!$group->options()->save($configOption)) {
                throw new Exception('Could not save config option');
            }

            KatapultWhmcs::dataStoreWrite($persistedOptionIdKey, $configOption->id);
        }

        return $configOption;
    }

    public static function createFreePricingForObject(string $type, int $relId): void
    {
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
                    'triennially' => 0
                ];
            })->toArray()
        );
    }

    public static function getPdo(): \PDO
    {
        return \Illuminate\Database\Capsule\Manager::connection()->getPdo();
    }
}
