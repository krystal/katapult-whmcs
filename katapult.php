<?php

/**
 * https://github.com/krystal/katapult-whmcs
 */

use Illuminate\Support\Str;
use Krystal\Katapult\KatapultAPI\Model\DataCenterLookup;
use Krystal\Katapult\KatapultAPI\Model\DiskTemplateLookup;
use Krystal\Katapult\KatapultAPI\Model\OrganizationsOrganizationVirtualMachinesBuildPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinePackageLookup;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineConsoleSessionsPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineDeleteBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineDeleteResponse200;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachinePackagePutBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachinePackagePutResponse200;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineResetPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineResetPostResponse200;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineShutdownPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineShutdownPostResponse200;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineStartPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineStartPostResponse200;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineStopPostBody;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinesVirtualMachineStopPostResponse200;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuildNotFound;
use WHMCS\Module\Server\Katapult\Helpers\KatapultApiV1Helper;
use WHMCS\Module\Server\Katapult\Helpers\OverrideHelper;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VmServerModuleParams;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use Carbon\Carbon;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function katapult_MetaData(): array
{
    return [
        'DisplayName' => 'Katapult',
        'ServiceSingleSignOnLabel' => 'Open Console',
        'RequiresServer' => true, // Sigh. https://github.com/krystal/katapult-whmcs/issues/18
    ];
}

function katapult_ConfigOptions(): array
{
    return VmServerModuleParams::getWhmcsServerConfiguration();
}

function katapult_ServiceSingleSignOn(array $params): array
{
    return KatapultWhmcs::runModuleCommandOnVm($params, function (VmServerModuleParams $params) {
        $requestBody = new VirtualMachinesVirtualMachineConsoleSessionsPostBody();

        $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);
        $consoleSession = katapult()->postVirtualMachineConsoleSessions($requestBody)->getConsoleSession();

        $params->service->log('Created console session for VM');

        return [
            'success' => true,
            'redirectTo' => $consoleSession->getUrl(),
        ];
    }, KatapultWhmcs::MRT_SSO);
}

function katapult_TerminateAccount(array $params): string
{
    return KatapultWhmcs::runModuleCommandOnVm($params, function (VmServerModuleParams $params) {
        KatapultWhmcs::deleteDiskBackupPolciesForVm($params->service);

        $requestBody = new VirtualMachinesVirtualMachineDeleteBody();
        $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

        $deleteVirtualMachineResult = katapult()->deleteVirtualMachine($requestBody);

        \katapultHandleApiResponse(
            VirtualMachinesVirtualMachineDeleteResponse200::class,
            $deleteVirtualMachineResult,
            $params->service,
            'VM deleted and local data store cleared',
            'VM failed to be deleted',
            fn() => $params->service->clearAllDataStoreValues()
        );
    }, KatapultWhmcs::MRT_STRING, false);
}

function katapult_ChangePackage(array $params): string
{
    return KatapultWhmcs::runModuleCommandOnVm($params, function (VmServerModuleParams $params) {
        $requestBody = new VirtualMachinesVirtualMachinePackagePutBody();
        $virtualMachinePackageLookup = new VirtualMachinePackageLookup();
        $virtualMachinePackageLookup->setPermalink($params->package);

        $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);
        $requestBody->setVirtualMachinePackage($virtualMachinePackageLookup);

        $apiResult = katapult()->putVirtualMachinePackage($requestBody);

        \katapultHandleApiResponse(
            VirtualMachinesVirtualMachinePackagePutResponse200::class,
            $apiResult,
            $params->service,
            'VM package changed to ' . $params->package,
            'VM failed to have its package changed',
        );
    });
}

function katapult_SuspendAccount(array $params): string
{
    return katapult_ShutdownVm($params);
}

function katapult_UnsuspendAccount(array $params): string
{
    return katapult_StartVm($params);
}

function katapult_StopVm(array $params): string
{
    return KatapultWhmcs::runModuleCommandOnVm($params, function (VmServerModuleParams $params) {
        $requestBody = new VirtualMachinesVirtualMachineStopPostBody();
        $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

        $apiResult = katapult()->postVirtualMachineStop($requestBody);

        \katapultHandleApiResponse(
            VirtualMachinesVirtualMachineStopPostResponse200::class,
            $apiResult,
            $params->service,
            'VM stopped',
            'VM failed to stop',
        );
    });
}

function katapult_ResetVm(array $params): string
{
    return KatapultWhmcs::runModuleCommandOnVm($params, function (VmServerModuleParams $params) {
        $requestBody = new VirtualMachinesVirtualMachineResetPostBody();
        $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

        $apiResult = katapult()->postVirtualMachineReset($requestBody);

        \katapultHandleApiResponse(
            VirtualMachinesVirtualMachineResetPostResponse200::class,
            $apiResult,
            $params->service,
            'VM reset',
            'VM failed to reset',
        );
    });
}

function katapult_StartVm(array $params): string
{
    return KatapultWhmcs::runModuleCommandOnVm($params, function (VmServerModuleParams $params) {
        $requestBody = new VirtualMachinesVirtualMachineStartPostBody();
        $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

        $apiResult = katapult()->postVirtualMachineStart($requestBody);

        \katapultHandleApiResponse(
            VirtualMachinesVirtualMachineStartPostResponse200::class,
            $apiResult,
            $params->service,
            'VM started',
            'VM failed to start',
        );
    });
}

function katapult_ShutdownVm(array $params): string
{
    return KatapultWhmcs::runModuleCommandOnVm($params, function (VmServerModuleParams $params) {
        $requestBody = new VirtualMachinesVirtualMachineShutdownPostBody();
        $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

        $apiResult = katapult()->postVirtualMachineShutdown($requestBody);

        \katapultHandleApiResponse(
            VirtualMachinesVirtualMachineShutdownPostResponse200::class,
            $apiResult,
            $params->service,
            'VM shutdown',
            'VM failed to shutdown',
        );
    });
}

function katapult_CreateAccount(array $params): string
{
    try {
        $params = new VmServerModuleParams($params);

        // Do we have an existing build running? Is it done?
        try {
            $params->service->checkForExistingBuildAttempt();

            // Great, it's done!
            return 'success';
        } catch (VirtualMachineBuildNotFound $e) {
            // This is fine, and normal behaviour.
        }

        // Hostname?
        if ($params->service->domain) {
            // Make it KP friendly..
            $hostname = str_replace('.', '-', $params->service->domain);
            $hostname = substr($hostname, 0, 18);

            // Remove trailing dashes from the hostname
            while (Str::endsWith($hostname, '-')) {
                $hostname = substr($hostname, 0, -1);
            }

            if (!$hostname) {
                $hostname = null;
            }
        }

        $vmBuildRequest = new OrganizationsOrganizationVirtualMachinesBuildPostBody();
        $vmBuildRequest->setOrganization($params->client->managed_organization);
        $vmBuildRequest->setPackage((new VirtualMachinePackageLookup())->setPermalink($params->package));
        $vmBuildRequest->setDataCenter((new DataCenterLookup())->setPermalink($params->dataCenter));
        $vmBuildRequest->setDiskTemplate((new DiskTemplateLookup())->setPermalink($params->diskTemplate));

        if (!is_null($hostname)) {
            $vmBuildRequest->setHostname($hostname);
        }

        $apiResult = katapult()->postOrganizationVirtualMachinesBuild($vmBuildRequest);

        if ($apiResult->getStatusCode() !== 200) {
            $params->service->log(
                sprintf(
                    '%s. Status: "%d". Response: "%s"',
                    'Could not build VM',
                    $apiResult->getStatusCode(),
                    $apiResult->getBody()->getContents()
                )
            );

            $errorResponseContents = $apiResult->getBody()->getContents();
            $errorResult = json_decode($errorResponseContents, true);

            // the error should be a json object with a description and a code
            // return the human-readable description but if it's not there return the raw result
            if (isset($errorResult['description'])) {
                return $errorResult['description'];
            } else {
                return $errorResponseContents;
            }
        } else {
            // Persist the build ID
            $params->service->dataStoreWrite(
                VirtualMachine::DS_VM_BUILD_ID,
                $apiResult->getBuild()->getId(),
                $apiResult->getBuild()->getId()
            );
            $params->service->dataStoreWrite(VirtualMachine::DS_VM_BUILD_STARTED_AT, Carbon::now());

            // Log it
            $params->service->log("Started VM build: {$apiResult->getBuild()->getId()}");

            // Trigger a hook
            $params->service->triggerHook(VirtualMachine::HOOK_BUILD_REQUESTED);

            return 'success';
        }
    } catch (\Throwable $e) {
        return katapultFormatError('Create Account', $e);
    }
}

function katapult_AdminCustomButtonArray(): array
{
    return [
        'Start VM' => 'StartVm',
        'Shutdown VM' => 'ShutdownVm',
        'Stop VM' => 'StopVm',
        'Reset VM' => 'ResetVm',
    ];
}

function katapult_ClientAreaCustomButtonArray(): array
{
    return [
        'Start VM' => 'StartVm',
        'Shutdown VM' => 'ShutdownVm',
        'Stop VM' => 'StopVm',
        'Reset VM' => 'ResetVm',
    ];
}

function katapult_AdminServicesTabFields(array $params): array
{
    try {
        $params = new VmServerModuleParams($params);

        // Do we have an existing build running? Is it done?
        $params->service->silentlyCheckForExistingBuildAttempt();

        // Generate the public VM JSON
        $publicServiceJson = json_encode(
            $params->service->toPublicArray()
        );

        // State with spaces
        $humanState = htmlentities(
            str_replace('_', ' ', $params->service->vm_state)
        );

        // State escaped. This is unnecessary, until it's not.
        $vmStateHtml = htmlentities(
            $params->service->vm_state
        );

        return [
            'Virtual Machine State' => <<<HTML
<script>
let katapultVmService = {$publicServiceJson};
</script>

<span class="katapult-vm-state state--{$vmStateHtml}">{$humanState}</span>
HTML
        ,];
    } catch (\Throwable $e) {
        return [
            'Error' => katapultFormatError('Admin Services Tab Fields', $e),
        ];
    }
}

/**
 * @param array $params
 * @return array
 */
function katapult_ClientArea(array $params): array
{
    try {
        $params = new VmServerModuleParams($params);

        // Do we have an existing build running? Is it done?
        $params->service->silentlyCheckForExistingBuildAttempt();

        return [
            'templatefile' => OverrideHelper::view('client/virtual_machines/overview.tpl'),
            'vars' => [
                'katapultVmService' => $params->service->toPublicArray(),
            ],
        ];
    } catch (\Throwable $e) {
        return [];
    }
}
