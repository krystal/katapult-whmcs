<?php

namespace WHMCS\Module\Server\Katapult\WhmcsModuleParams;

use Krystal\Katapult\Resources\VirtualMachinePackage;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Module\Server\Katapult\WHMCS\Product\Product;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;
use Illuminate\Support\Str;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;

/**
 * Class ServerModuleParams
 * @package WHMCS\Module\Server\Katapult
 *
 * @property-read Client $client
 * @property-read Product $product
 * @property-read VirtualMachine $service
 *
 * @property-read string $package
 * @property-read string $dataCenter
 * @property-read string $diskTemplate
 */
class VmServerModuleParams extends ServerModuleParams
{
	protected VirtualMachine $service;
	protected Client $client;
	protected Product $product;

	public function boot(): void
	{
		$this->service = VirtualMachine::findOrFail($this->rawParams['serviceid']);
		$this->client = Client::findOrFail($this->rawParams['userid']);
		$this->product = Product::findOrFail($this->rawParams['packageid']);
	}

	public static function getWhmcsServerConfiguration(): array
	{
		$attemptToAccessKatapult = function(callable $task)
		{
			try {
				return $task();
			} catch(\Throwable $e) {
				KatapultWhmcs::log("Error: {$e->getMessage()}");
				throw new Exception('Error connecting to Katapult. Have you configured your API key? Set the module to Katapult, save the product and then set your key on the \'Other\' tab.');
			}
		};

		$options = [
			'Package' => [
				'Type' => 'text',
				'Size' => '25',
				'Default' => '',
				'Description' => '',
				'SimpleMode' => true,
				'Loader' => function() use ($attemptToAccessKatapult) {
					return $attemptToAccessKatapult(function() {
						return collect(katapult()->resource(VirtualMachinePackage::class)->all())->mapWithKeys(function(VirtualMachinePackage $package) {
							return [$package->permalink => $package->name];
						})->all();
					});
				}
			],
		];

		$optionIndex = 1;

		foreach ($options as $optionKey => $option) {
			$options[$optionKey]['optionIndex'] = $optionIndex;
			$options[$optionKey]['camelName'] = Str::camel(Str::lower($optionKey));
			$optionIndex++;
		}

		return $options;
	}

	public function __get(string $propertyName)
	{
		switch ($propertyName) {
			case 'service':
			case 'client':
			case 'product':
				return $this->{$propertyName};

			case 'dataCenter':
				return $this->getBasicConfigOptionValueForService(
					KatapultWhmcs::dataStoreRead(KatapultWhmcs::DS_VM_CONFIG_OPTION_DATACENTER_ID)
				);

			case 'diskTemplate':
				return $this->getBasicConfigOptionValueForService(
					KatapultWhmcs::dataStoreRead(KatapultWhmcs::DS_VM_CONFIG_OPTION_DISK_TEMPLATE_ID)
				);
		}

		return $this->defaultGetter($propertyName);
	}
}



