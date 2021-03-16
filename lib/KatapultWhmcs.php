<?php

namespace WHMCS\Module\Server\Katapult;

use Grizzlyware\Salmon\WHMCS\Helpers\DataStore;
use Grizzlyware\Salmon\WHMCS\Product\ConfigurableOptions\Group as ConfigOptionGroup;
use Grizzlyware\Salmon\WHMCS\Product\Product;
use Krystal\Katapult\Katapult;
use Krystal\Katapult\API\RestfulKatapultApiV1 as KatapultApi;
use Krystal\Katapult\Resources\DataCenter;
use Krystal\Katapult\Resources\Organization;
use Krystal\Katapult\Resources\Organization\DiskTemplate;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\Helpers\WhmcsHelper;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;

class KatapultWhmcs
{
	private static ? Katapult $katapult = null;

	const SERVER_MODULE = 'katapult';

	const DS_API_V1_KEY = 'api_v1_key';
	const DS_PARENT_ORGANIZATION = 'parent_organization';
	const DS_CONFIG_OPTION_GROUP_ID = 'config_option_group_id';
	const DS_CONFIG_OPTION_DATACENTER_ID = 'config_option_datacenter_id';
	const DS_CONFIG_OPTION_DISK_TEMPLATE_ID = 'config_option_disk_template_id';

	public static function getKatapult(): Katapult
	{
		if (self::$katapult === null) {

			if (!KatapultWhmcs::getApiV1Key()) {
				throw new Exception('No API key set');
			}

			self::$katapult = Katapult::make(new KatapultApi(
				KatapultWhmcs::getApiV1Key()
			));
		}

		return self::$katapult;
	}

	public static function dataStoreRead(string $key)
	{
		return DataStore::get('module_setting', 'katapult', $key);
	}

	public static function dataStoreWrite(string $key, $value)
	{
		DataStore::set('module_setting', 'katapult', $key, $value);
	}

	public static function getApiV1Key(): ? string
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

	public static function setParentOrganization(string $organization): void
	{
		if (self::getParentOrganization() === $organization) {
			return;
		}

		KatapultWhmcs::dataStoreWrite(self::DS_PARENT_ORGANIZATION, $organization);

		self::log("Updated parent organization to: {$organization}");
	}

	public static function getParentOrganizationIdentifier(): ? string
	{
		return KatapultWhmcs::dataStoreRead(self::DS_PARENT_ORGANIZATION);
	}

	public static function getParentOrganization(): ? Organization
	{
		$orgIdentifier = self::getParentOrganizationIdentifier();

		if (!$orgIdentifier) {
			return null;
		}

		$spec = [];

		if(strpos($orgIdentifier, 'org_') === 0) {
			$spec['id'] = $orgIdentifier;
		} else {
			$spec['subdomain'] = $orgIdentifier;
		}

		return Organization::instantiateFromSpec(
			(object)$spec
		);
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

		/**
		 * Data center option
		 */

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
			$currentOption = new ConfigOptionGroup\Option\SubOption();
			$currentOption->optionname = "{$dataCenter->permalink}|{$dataCenter->name}";
			$currentOption->hidden = $dataCenterOption->wasRecentlyCreated ? 0 : 1;

			// Persist the option
			if (!$dataCenterOption->subOptions()->save($currentOption)) {
				throw new \Exception('Could not save data center: ' . $dataCenter->name);
			}

			// Create free pricing for the new option
			WhmcsHelper::createFreePricingForObject('configoptions', $currentOption->id);
		}

		/**
		 * Disk template option
		 */

		// Fetch the DC option
		$diskTemplateOption = WhmcsHelper::getOrCreateConfigOption($configOptionGroup, 'Disk Template', 1, self::DS_CONFIG_OPTION_DISK_TEMPLATE_ID);

		// Create options for the templates
		/** @var DiskTemplate $diskTemplate */
		foreach(\katapult()->resource(DiskTemplate::class, katapultOrg())->all() as $diskTemplate) {

			// Already got it, skip
			if ($diskTemplateOption->subOptions()->where('optionname', 'LIKE', "{$diskTemplate->permalink}|%")->count() > 0) {
				continue;
			}

			// Create the option
			$currentOption = new ConfigOptionGroup\Option\SubOption();
			$currentOption->optionname = "{$diskTemplate->permalink}|{$diskTemplate->name}";
			$currentOption->hidden = $diskTemplateOption->wasRecentlyCreated ? 0 : 1;

			// Persist the option
			if (!$diskTemplateOption->subOptions()->save($currentOption)) {
				throw new \Exception('Could not save disk template: ' . $diskTemplate->name);
			}

			// Create free pricing for the new option
			WhmcsHelper::createFreePricingForObject('configoptions', $currentOption->id);
		}
	}

	public static function syncVmBuilds(): void
	{
		$log = function($message, VirtualMachine $service = null) {
			$message = "[SyncVmBuilds]: {$message}";

			if($service) {
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

		while($row = $sql->fetch(\PDO::FETCH_ASSOC)) {
			try {
				/** @var VirtualMachine $service */
				$service = VirtualMachine::findOrFail($row['id']);
				$log("Starting", $service);
				$service->checkForExistingBuildAttempt();
			} catch (\Throwable $e) {
				if(isset($service) && $service) {
					$log("Error: {$e->getMessage()}", $service);
				} else {
					$log("Error: Service ID: " . $row['id'] . ": {$e->getMessage()}");
				}
			}
		}
	}
}




