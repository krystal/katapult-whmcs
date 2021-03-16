<?php

namespace WHMCS\Module\Server\Katapult;

use Krystal\Katapult\Resources\Organization\ManagedOrganization;
use Krystal\Katapult\Resources\VirtualMachinePackage;
use WHMCS\Module\Server\Katapult\WHMCS\Product\Product;
use WHMCS\Module\Server\Katapult\WHMCS\Service\Service;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;
use Illuminate\Support\Str;

/**
 * Class ServerModuleParams
 * @package WHMCS\Module\Server\Katapult
 *
 * @property-read Client $client
 * @property-read Product $product
 * @property-read Service $service
 *
 * @property-read string $package
 * @property-read string $dataCenter
 * @property-read string $diskTemplate
 */
class ServerModuleParams
{
	protected array $rawParams;
	protected array $configuration;

	protected Service $service;
	protected Client $client;
	protected Product $product;

	public function __construct(array $params)
	{
		$this->rawParams = $params;

		$this->configuration = [];

		foreach(self::getWhmcsServerConfiguration() as $option) {
			$this->configuration[$option['camelName']] = $option;
		}

		$this->service = Service::findOrFail($params['serviceid']);
		$this->client = Client::findOrFail($params['userid']);
		$this->product = Product::findOrFail($params['packageid']);
	}

	public static function getWhmcsServerConfiguration(): array
	{
		$attemptToAccessKatapult = function(callable $task)
		{
			try {
				return $task();
			} catch(\Throwable $e) {
				KatapultWhmcs::log("Error: {$e->getMessage()}");
				throw new \Exception('Error connecting to Katapult. Have you configured your API key? Set the module to Katapult, save the product and then set your key on the \'Other\' tab.');
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

	protected function getBasicConfigOptionValueForService(int $optionId): ? string
	{
		$value = $this->service->configurableOptionValues()->where('configid', $optionId)->first();

		if (!$value) {
			return null;
		}

		return explode('|', $value->value, 2)[0] ?? null;
	}

	public function __get($name)
	{
		switch ($name) {
			case 'service':
			case 'client':
			case 'product':
				return $this->{$name};

			case 'dataCenter':
				return $this->getBasicConfigOptionValueForService(
					KatapultWhmcs::dataStoreRead(KatapultWhmcs::DS_CONFIG_OPTION_DATACENTER_ID)
				);

			case 'diskTemplate':
				return $this->getBasicConfigOptionValueForService(
					KatapultWhmcs::dataStoreRead(KatapultWhmcs::DS_CONFIG_OPTION_DISK_TEMPLATE_ID)
				);
		}

		if (isset($this->configuration[$name])) {
			return $this->rawParams['configoption' . $this->configuration[$name]['optionIndex']];
		}

		return isset($this->rawParams[$name]) ? $this->rawParams[$name] : null;
	}
}



