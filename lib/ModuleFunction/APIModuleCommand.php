<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use KatapultAPI\Core\Client as KatapultAPIClient;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\RunCommandOnVM;

abstract class APIModuleCommand
{
    protected KatapultAPIClient $katapultAPI;

    protected KeyValueStoreInterface $keyValueStore;

    protected RunCommandOnVM $runCommandOnVM;

    public function __construct(
        KatapultAPIClient $katapultAPI,
        KeyValueStoreInterface $keyValueStore,
    ) {
        $this->katapultAPI = $katapultAPI;
        $this->keyValueStore = $keyValueStore;

        $this->runCommandOnVM = new RunCommandOnVM($katapultAPI, $keyValueStore);
    }
}
