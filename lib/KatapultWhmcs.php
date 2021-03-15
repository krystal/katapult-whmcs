<?php

namespace WHMCS\Module\Server\Katapult;

use Grizzlyware\Salmon\WHMCS\Helpers\DataStore;
use Grizzlyware\Salmon\WHMCS\Product\ConfigurableOptions\Group as ConfigOptionGroup;
use Grizzlyware\Salmon\WHMCS\Product\Product;
use Krystal\Katapult\Katapult;
use Krystal\Katapult\API\RestfulKatapultApiV1 as KatapultApi;
use Krystal\Katapult\Resources\DataCenter;
use WHMCS\Module\Server\Katapult\Helpers\WhmcsHelper;

class KatapultWhmcs
{
	private static ? Katapult $katapult = null;

	const SERVER_MODULE = 'katapult';

	const DS_API_V1_KEY = 'api_v1_key';
	const DS_PARENT_ORGANIZATION = 'parent_organization';
	const DS_CONFIG_OPTION_GROUP_ID = 'config_option_group_id';
	const DS_CONFIG_OPTION_DATACENTER_ID = 'config_option_datacenter_id';

	public static function getKatapult(): Katapult
	{
		if (self::$katapult === null) {
			self::$katapult = Katapult::make(new KatapultApi(
				KatapultWhmcs::getApiV1Key()
			));
		}

		return self::$katapult;
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

	public static function setApiV1Key(string $apiKey): void
	{
		KatapultWhmcs::dataStoreWrite(self::DS_API_V1_KEY, \encrypt($apiKey));

		self::log("Updated API V1 key");
	}

	public static function setParentOrganization(string $organization): void
	{
		if (self::getParentOrganization() === $organization) {
			return;
		}

		KatapultWhmcs::dataStoreWrite(self::DS_PARENT_ORGANIZATION, $organization);

		self::log("Updated parent organization to: {$organization}");
	}

	public static function getParentOrganization(): ? string
	{
		return KatapultWhmcs::dataStoreRead(self::DS_PARENT_ORGANIZATION);
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

		// This will fetch DCs, disk templates from Katapult and sync them with WHMCS configurable options.
		// If there is no existing config option, it will create all elements as visible, else it will add new ones as hidden for an admin to un-hide as required.

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

			// Create free pricing for the new option
			WhmcsHelper::createFreePricingForObject('configoptions', $dataCenterOption->id);
		}
	}
}




