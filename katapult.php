<?php

/**
 * @see https://github.com/krystal/katapult-whmcs
 */

use Illuminate\Support\Str;
use Krystal\Katapult\KatapultAPI\Model\DataCenterLookup;
use Krystal\Katapult\KatapultAPI\Model\DiskTemplateLookup;
use Krystal\Katapult\KatapultAPI\Model\OrganizationsGetResponse200;
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
use WHMCS\Module\Server\Katapult\Helpers\OverrideHelper;
use WHMCS\Module\Server\Katapult\KatapultWhmcs;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VmServerModuleParams;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use Carbon\Carbon;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see https://developers.whmcs.com/provisioning-modules/meta-data-params/
 *
 * @return array<string, string>
 */
function katapult_MetaData(): array
{
    return [
        'DisplayName' => 'Katapult',
        'ServiceSingleSignOnLabel' => 'Open Console',
        /** @see https://github.com/krystal/katapult-whmcs/issues/18 */
        'RequiresServer' => true,
    ];
}

/**
 * Define product configuration options.
 *
 * The values you return here define the configuration options that are
 * presented to a user when configuring a product for use with the module. These
 * values are then made available in all module function calls with the key name
 * configoptionX - with X being the index number of the field from 1 to 24.
 *
 * You can specify up to 24 parameters, with field types:
 * text
 * password
 * yesno
 * dropdown
 * radio
 * textarea
 *
 * @see https://developers.whmcs.com/provisioning-modules/config-options/
 */
function katapult_ConfigOptions(): array
{
    return VmServerModuleParams::getWhmcsServerConfiguration();
}

/**
 * Provision a new instance of a product/service.
 *
 * Attempt to provision a new instance of a given product/service. This is
 * called any time provisioning is requested inside of WHMCS. Depending upon the
 * configuration, this can be any of:
 * * When a new order is placed
 * * When an invoice for a new order is paid
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
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

/**
 * Perform single sign-on for a given instance of a product/service.
 *
 * Called when single sign-on is requested for an instance of a product/service.
 *
 * When successful, returns a URL to which the user should be redirected.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
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
    });
}

/**
 * Suspend an instance of a product/service.
 *
 * Called when a suspension is requested. This is invoked automatically by WHMCS
 * when a product becomes overdue on payment or can be called manually by admin
 * user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function katapult_SuspendAccount(array $params): string
{
    return katapult_ShutdownVm($params);
}

/**
 * Un-suspend instance of a product/service.
 *
 * Called when an un-suspension is requested. This is invoked
 * automatically upon payment of an overdue invoice for a product, or
 * can be called manually by admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function katapult_UnsuspendAccount(array $params): string
{
    return katapult_StartVm($params);
}

/**
 * Terminate instance of a product/service.
 *
 * Called when a termination is requested. This can be invoked automatically for
 * overdue products if enabled, or requested manually by an admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
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
    }, false);
}

/**
 * Upgrade or downgrade an instance of a product/service.
 *
 * Called to apply any change in product assignment or parameters. It
 * is called to provision upgrade or downgrade orders, as well as being
 * able to be invoked manually by an admin user.
 *
 * This same function is called for upgrades and downgrades of both
 * products and configurable options.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
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

/**
 * Test connection with the given server parameters.
 *
 * Allows an admin user to verify that an API connection can be
 * successfully made with the given configuration parameters for a
 * server.
 *
 * When defined in a module, a Test Connection button will appear
 * alongside the Server Type dropdown when adding or editing an
 * existing server.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
function katapult_TestConnection(array $params): array
{
    try {
        $response = katapult()->getOrganizations();

        if ($response instanceof OrganizationsGetResponse200) {
            $success = true;
            $errorMsg = '';
        } else {
            $success = false;
            $errorMsg = $response->getBody()->getContents();
        }
    } catch (\Throwable $e) {
        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}

/**
 * Stop a Virtual Machine.
 *
 * Custom function for performing an additional action.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @return string "success" or an error message
 * @see katapult_AdminCustomButtonArray()
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
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

/**
 * Reset a Virtual Machine.
 *
 * Custom function for performing an additional action.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @return string "success" or an error message
 * @see katapult_AdminCustomButtonArray()
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
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

/**
 * Start a Virtual Machine.
 *
 * Custom function for performing an additional action.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @return string "success" or an error message
 * @see katapult_AdminCustomButtonArray()
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
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

/**
 * Shutdown a Virtual Machine.
 *
 * Custom function for performing an additional action.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @return string "success" or an error message
 * @see katapult_AdminCustomButtonArray()
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
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

/**
 * Additional actions an admin user can invoke.
 *
 * Define additional actions that an admin user can perform for an
 * instance of a product/service.
 *
 * @see katapult_StartVm()
 * @see katapult_ShutdownVm()
 * @see katapult_StopVm()
 * @see katapult_ResetVm()
 */
function katapult_AdminCustomButtonArray(): array
{
    return [
        'Start VM' => 'StartVm',
        'Shutdown VM' => 'ShutdownVm',
        'Stop VM' => 'StopVm',
        'Reset VM' => 'ResetVm',
    ];
}

/**
 * Additional actions a client user can invoke.
 *
 * Define additional actions a client user can perform for an instance of a
 * product/service.
 *
 * Any actions you define here will be automatically displayed in the available
 * list of actions within the client area.
 *
 * @see katapult_StartVm()
 * @see katapult_ShutdownVm()
 * @see katapult_StopVm()
 * @see katapult_ResetVm()
 */
function katapult_ClientAreaCustomButtonArray(): array
{
    return [
        'Start VM' => 'StartVm',
        'Shutdown VM' => 'ShutdownVm',
        'Stop VM' => 'StopVm',
        'Reset VM' => 'ResetVm',
    ];
}

/**
 * Admin services tab additional fields.
 *
 * Define additional rows and fields to be displayed in the admin area service
 * information and management page within the clients profile.
 *
 * Supports an unlimited number of additional field labels and content of any
 * type to output.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
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
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 * The template file you return can be one of two types:
 *
 * * tabOverviewModuleOutputTemplate - The output of the template provided here
 *   will be displayed as part of the default product/service client area
 *   product overview page.
 *
 * * tabOverviewReplacementTemplate - Alternatively using this option allows you
 *   to entirely take control of the product/service overview page within the
 *   client area.
 *
 * Whichever option you choose, extra template variables are defined in the same
 * way. This demonstrates the use of the full replacement.
 *
 * Please Note: Using tabOverviewReplacementTemplate means you should display
 * the standard information such as pricing and billing details in your custom
 * template or they will not be visible to the end user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
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
