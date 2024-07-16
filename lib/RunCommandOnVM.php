<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult;

use Krystal\Katapult\KatapultAPI\Client as KatapultAPIClient;
use WHMCS\Module\Server\Katapult\Adaptation\System as SystemAdaptation;
use WHMCS\Module\Server\Katapult\Exceptions\Exception;
use WHMCS\Module\Server\Katapult\Helpers\Replay;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

class RunCommandOnVM
{
    public bool $checkServiceIsActive = true;

    public function __construct(
        private readonly KatapultAPIClient $katapultAPI,
        private readonly KeyValueStoreInterface $keyValueStore,
    ) {
    }

    /**
     * @param array    $params
     * @param callable $command
     *
     * @return string|array
     */
    public function runOnVm(array $params, callable $command): array|string
    {
        try {
            $this->ensureRequestHasNotBeenReplayed();

            $params = new VMServerModuleParams(
                $params,
                $this->katapultAPI,
                $this->keyValueStore,
            );

            $this->ensureCommandCanBeRun($params);

            $return = $command($params);

            if ($return) {
                return $return;
            }

            return 'success';
        } catch (\Throwable $e) {
            return \Katapult\formatError('Module command', $e);
        }
    }

    /**
     * Checks if the request has been replayed
     * @throws Exception
     */
    private function ensureRequestHasNotBeenReplayed(): void
    {
        if (Replay::tokenIsValidForClientArea() !== false) {
            return;
        }

        throw new Exception("Replay detected, please click the button again. You may need to refresh the page first.");
    }

    /**
     * @throws Exception
     */
    private function ensureCommandCanBeRun(VMServerModuleParams $params): void
    {
        // Check the service is active
        if ($this->checkServiceIsActive && $params->service->domainstatus !== 'Active') {
            throw new Exception('This service is not currently active');
        }

        // In case it's just been built!
        // This'll result in vm_id being populated when it tries to read it in the next step
        $params->service->silentlyCheckForExistingBuildAttempt($this->katapultAPI);

        // Check there is a VM...
        if (!$params->service->vm_id) {
            throw new Exception('There is no VM ID set for this service');
        }
    }
}
