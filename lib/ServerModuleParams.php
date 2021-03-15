<?php

namespace WHMCS\Module\Server\Katapult;

use Krystal\Katapult\Resources\VirtualMachinePackage;
use WHMCS\Module\Server\Katapult\WHMCS\Product\Product;
use WHMCS\Module\Server\Katapult\WHMCS\Service\Service;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

/**
 * Class ServerModuleParams
 * @package WHMCS\Module\Server\Katapult
 *
 * @property-read Client $client
 * @property-read Product $product
 * @property-read Service $service
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

		foreach (self::getWhmcsServerConfiguration() as $option) {
			$this->configuration[$option['camelName']] = $option;
		}

		$this->service = Service::findOrFail($params['serviceid']);
		$this->client = Client::findOrFail($params['userid']);
		$this->product = Product::findOrFail($params['packageid']);
	}

	public static function getWhmcsServerConfiguration(): array
	{
		$attemptToAccessKatapult = function(callable $task) {
			try {
				return $task();
			} catch (\Throwable $e) {
				KatapultWhmcs::log("Error: {$e->getMessage()}");
				throw new \Exception('Error connecting to Katapult. Have you configured your API key? Set the module to Katapult, save the product and then set your key on the \'Other\' tab.');
			}
		};

		return [
			'Package' => [
				'Type' => 'text',
				'Size' => '25',
				'Default' => '',
				'Description' => '',
				'SimpleMode' => true,
				'Loader' => function() use($attemptToAccessKatapult) {
					return $attemptToAccessKatapult(function() {
						return collect(katapult()->resource(VirtualMachinePackage::class)->all())->mapWithKeys(function(VirtualMachinePackage $package) {
							return [$package->permalink => $package->name];
						})->all();
					});
				}
			],
		];
	}

	public function __get($name)
	{
		switch ($name) {
			case 'service':
			case 'client':
			case 'product':
				return $this->{$name};
		}

		if (isset($this->configuration[$name])) {
			return $this->rawParams['configoption' . $this->configuration[$name]['optionIndex']];
		}

		return isset($this->rawParams[$name]) ? $this->rawParams[$name] : null;
	}
}



