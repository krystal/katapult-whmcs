<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult;

use Illuminate\Support\Str;
use KatapultAPI\Core\Client as KatapultAPIClient;
use KatapultAPI\Core\Model\GetVirtualMachinePackages200ResponseVirtualMachinePackages as VirtualMachinePackages;
use KatapultAPI\Core\Model\VirtualMachinePackagesGetResponse200;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;

class ConfigurationOptions
{
    public function __construct(
        protected KatapultAPIClient $katapultAPI
    ) {
    }

    public function getConfigurationOptions(): array
    {
        $options = [
            'Package' => [
                'Type' => 'text',
                'Size' => '25',
                'Default' => '',
                'Description' => '',
                'SimpleMode' => true,
                'Loader' => [$this, 'getPackageMap'],
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

    public function getPackageMap(): array
    {
        try {
            $virtualMachinePackagesResponse = $this->katapultAPI->getVirtualMachinePackages();

            if (!$virtualMachinePackagesResponse instanceof VirtualMachinePackagesGetResponse200) {
                throw APIException::new(
                    $virtualMachinePackagesResponse,
                    VirtualMachinePackagesGetResponse200::class,
                );
            }

            $packages = $virtualMachinePackagesResponse->getVirtualMachinePackages();

            $remap = function (VirtualMachinePackages $package) {
                return [
                    $package->getPermalink() => $package->getName(),
                ];
            };

            return collect($packages)->mapWithKeys($remap)->all();
        } catch (\Throwable $e) {
            KatapultWHMCS::log("Error: {$e->getMessage()}");

            throw new Exception(
                'Error connecting to Katapult. Have you configured your API key? ' .
                'Set the module to Katapult, save the product and then set your key on the \'Other\' tab.'
            );
        }
    }
}
