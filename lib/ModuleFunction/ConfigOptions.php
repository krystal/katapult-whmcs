<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use WHMCS\Module\Server\Katapult\Katapult\ConfigurationOptions;

final class ConfigOptions extends APIModuleCommand
{
    public function run(): array
    {
        $configurationOptions = new ConfigurationOptions($this->katapultAPI);

        return $configurationOptions->getConfigurationOptions();
    }
}
