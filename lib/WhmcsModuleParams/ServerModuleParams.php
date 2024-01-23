<?php

namespace WHMCS\Module\Server\Katapult\WhmcsModuleParams;

abstract class ServerModuleParams
{
    protected array $rawParams;
    protected array $configuration;

    abstract public function boot(): void;
    abstract public static function getWhmcsServerConfiguration(): array;
    abstract public function __get(string $propertyName);

    public function __construct(array $params)
    {
        $this->rawParams = $params;

        $this->configuration = [];

        foreach (static::getWhmcsServerConfiguration() as $option) {
            $this->configuration[$option['camelName']] = $option;
        }

        $this->boot();
    }

    protected function getBasicConfigOptionValueForService(int $optionId): ?string
    {
        $value = $this->service->configurableOptionValues()->where('configid', $optionId)->first();

        if (!$value) {
            return null;
        }

        return explode('|', $value->value, 2)[0] ?? null;
    }

    protected function defaultGetter(string $propertyName)
    {
        if (isset($this->configuration[$propertyName])) {
            return $this->rawParams['configoption' . $this->configuration[$propertyName]['optionIndex']];
        }

        return isset($this->rawParams[$propertyName]) ? $this->rawParams[$propertyName] : null;
    }
}
