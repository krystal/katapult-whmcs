<?php

namespace WHMCS\Module\Server\Katapult;

use Grizzlyware\Salmon\WHMCS\Billing\Currency;
use Grizzlyware\Salmon\WHMCS\Helpers\DataStore;
use Grizzlyware\Salmon\WHMCS\Product\ConfigurableOptions\Group as ConfigOptionGroup;
use Grizzlyware\Salmon\WHMCS\Product\Product;
use Krystal\Katapult\Katapult;
use Krystal\Katapult\API\RestfulKatapultApiV1 as KatapultApi;
use Krystal\Katapult\Resources\DataCenter;
use WHMCS\Module\Server\Katapult\Helpers\WhmcsHelper;
use WHMCS\Database\Capsule;

class KatapultWhmcs
{
	const SERVER_MODULE = 'katapult';

	const DS_API_V1_KEY = 'api_v1_key';
	const DS_CONFIG_OPTION_GROUP_ID = 'config_option_group_id';
	const DS_CONFIG_OPTION_DATACENTER_ID = 'config_option_datacenter_id';

	public static function getKatapult(): Katapult
	{
		return Katapult::make(new KatapultApi(
			KatapultWhmcs::getApiV1Key()
		));
	}

	public static function dataStoreRead(string $key)
	{
		$value = DataStore::get('module_setting', 'katapult', $key);

		if($value === DataStore::EMPTY_VALUE_INDEX) {
			return null;
		}

		return $value;
	}

	public static function dataStoreWrite(string $key, $value)
	{
		DataStore::set('module_setting', 'katapult', $key, $value);
	}

	public static function getApiV1Key(): ? string
	{
		if (defined('KATAPULT_API_V1_KEY') && KATAPULT_API_V1_KEY) {
			return KATAPULT_API_V1_KEY;
		}

		$value = KatapultWhmcs::dataStoreRead(self::DS_API_V1_KEY);

		if (!$value) {
			return null;
		}

		return \decrypt($value);
	}

	public static function setApiV1Key(string $apiKey)
	{
		KatapultWhmcs::dataStoreWrite(self::DS_API_V1_KEY, \encrypt($apiKey));
	}

	public static function log(string $message)
	{
		return \logActivity("[Katapult]: {$message}");
	}

	public static function moduleLog(string $action, $request, $response)
	{
		\logModuleCall(KatapultWhmcs::SERVER_MODULE, $action, $request, $response);
	}

	/**
	 * Creates a config option group called Katapult, and assigns it to the Katapult products.
	 * @throws \Exception
	 */
	public static function syncConfigurableOptions(): void
	{
		// Have we already created a config option group for Katapult?
		$configOptionGroup = ConfigOptionGroup::find(
			KatapultWhmcs::dataStoreRead(self::DS_CONFIG_OPTION_GROUP_ID)
		);

		// Nope? Create it..
		if (!$configOptionGroup) {
			$configOptionGroup = new ConfigOptionGroup();
			$configOptionGroup->name = 'Katapult';

			if (!$configOptionGroup->save()) {
				throw new \Exception('Could not save config option group');
			}

			KatapultWhmcs::dataStoreWrite(self::DS_CONFIG_OPTION_GROUP_ID, $configOptionGroup->id);

			// Assign it to all of the Katapult products
			$configOptionGroup->products()->attach(
				Product::where('servertype', KatapultWhmcs::SERVER_MODULE)->pluck('id')->toArray()
			);
		}

		// This will fetch DCs from Katapult and sync them with WHMCS configurable options.
		// If there is no existing config option, it will create all DCs as visible, else it will add new ones as hidden for an admin to un-hide as required.

		// Fetch the DC option
		$dataCenterOption = WhmcsHelper::getOrCreateConfigOption($configOptionGroup, 'Data Center', 1, self::DS_CONFIG_OPTION_DATACENTER_ID);

		// Create options for the DCs
		/** @var DataCenter $dataCenter */
		foreach(\katapult()->resource(DataCenter::class)->all() as $dataCenter) {

			// Already got it, skip
			if ($dataCenterOption->subOptions()->where('optionname', 'LIKE', "{$dataCenter->permalink}|%")->count() > 0) {
				continue;
			}

			// Create the option
			$currentDcOption = new ConfigOptionGroup\Option\SubOption();
			$currentDcOption->optionname = "{$dataCenter->permalink}|{$dataCenter->name}";
			$currentDcOption->hidden = $dataCenterOption->wasRecentlyCreated ? 0 : 1;

			// Persist the option
			if (!$dataCenterOption->subOptions()->save($currentDcOption)) {
				throw new \Exception('Could not save data center: ' . $dataCenter->name);
			}

			// Set free pricing for it
			Capsule::table('tblpricing')->insert(
				Currency::all()->map(function(Currency $currency) use($currentDcOption) {
					return [
						'type' => 'configoptions',
						'relid' => $currentDcOption->id,
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
	}
}




