<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\WhmcsModuleParams;

use Illuminate\Support\Str;
use Krystal\Katapult\KatapultAPI\Client as KatapultAPIClient;
use WHMCS\Module\Server\Katapult\Katapult\ConfigurationOptions;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use WHMCS\Module\Server\Katapult\WHMCS\User\Client;

/**
 * @property-read string $package
 * @property-read string $dataCenter
 * @property-read string $diskTemplate
 */
class VMServerModuleParams
{
    public VirtualMachine $service;
    public Client $client;
    protected array $configuration;
    protected array $rawParams;
    protected KatapultAPIClient $katapultAPIClient;
    protected KeyValueStoreInterface $keyValueStore;

    public function __construct(
        array $params,
        KatapultAPIClient $katapultAPIClient,
        KeyValueStoreInterface $keyValueStore,
    ) {
        $this->rawParams = $params;
        $this->katapultAPIClient = $katapultAPIClient;
        $this->keyValueStore = $keyValueStore;

        $this->configuration = [];

        $configurationOptions = new ConfigurationOptions($this->katapultAPIClient);

        foreach ($configurationOptions->getConfigurationOptions() as $option) {
            $this->configuration[$option['camelName']] = $option;
        }

        $this->service = VirtualMachine::findOrFail($this->rawParams['serviceid']);
        $this->client = Client::findOrFail($this->rawParams['userid']);
    }

    public function __get(string $propertyName)
    {
        return match ($propertyName) {
            'service', 'client', 'product' => $this->{$propertyName},
            'dataCenter' => $this->getBasicConfigOptionValueForService(
                $this->keyValueStore->read(KatapultWHMCS::DS_VM_CONFIG_OPTION_DATACENTER_ID)
            ),
            'diskTemplate' => $this->getBasicConfigOptionValueForService(
                $this->keyValueStore->read(KatapultWHMCS::DS_VM_CONFIG_OPTION_DISK_TEMPLATE_ID)
            ),
            default => $this->defaultGetter($propertyName),
        };
    }

    public function getHostname(): ?string
    {
        $hostname = null;

        if ($this->service->domain) {
            // Make it KP friendly...
            $hostname = str_replace('.', '-', $this->service->domain);
            $hostname = substr($hostname, 0, 18);

            // Remove trailing dashes from the hostname
            while (Str::endsWith($hostname, '-')) {
                $hostname = substr($hostname, 0, -1);
            }

            if (!$hostname) {
                $hostname = null;
            }
        }

        return $hostname;
    }

    protected function getBasicConfigOptionValueForService(int $optionId): ?string
    {
        $value = $this->service->configurableOptionValues()->where('configid', $optionId)->first();

        if (!$value) {
            return null;
        }

        return explode('|', $value->value, 2)[0] ?? null;
    }

    /**
     * @return mixed|null
     */
    protected function defaultGetter(string $propertyName): mixed
    {
        if (isset($this->configuration[$propertyName])) {
            return $this->rawParams['configoption' . $this->configuration[$propertyName]['optionIndex']];
        }

        return $this->rawParams[$propertyName] ?? null;
    }
}
