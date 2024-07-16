<?php

/**
 * @see https://github.com/krystal/katapult-whmcs
 */

use WHMCS\Module\Server\Katapult\ModuleFunction;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see https://developers.whmcs.com/provisioning-modules/meta-data-params/
 *
 * @return array<string, string|bool>
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
    $configOptions = new ModuleFunction\ConfigOptions(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $configOptions->run();
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
 * @return string "success" or an error message
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 */
function katapult_CreateAccount(array $params): string
{
    $createAccount = new ModuleFunction\CreateAccount(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $createAccount->run($params);
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
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
function katapult_ServiceSingleSignOn(array $params): array
{
    $serviceSingleSignOn = new ModuleFunction\ServiceSingleSignOn(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $serviceSingleSignOn->run($params);
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
 * @return string "success" or an error message
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
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
 * @return string "success" or an error message
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
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
 * @return string "success" or an error message
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 */
function katapult_TerminateAccount(array $params): string
{
    $terminateAccount = new ModuleFunction\TerminateAccount(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $terminateAccount->run($params);
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
 * @return string "success" or an error message
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 */
function katapult_ChangePackage(array $params): string
{
    $changePackage = new ModuleFunction\ChangePackage(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $changePackage->run($params);
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
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
function katapult_TestConnection(array $params): array
{
    $testConnection = new ModuleFunction\TestConnection(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $testConnection->run();
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
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see katapult_AdminCustomButtonArray()
 *
 */
function katapult_StopVm(array $params): string
{
    $stopVM = new ModuleFunction\VM\Stop(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $stopVM->run($params);
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
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see katapult_AdminCustomButtonArray()
 *
 */
function katapult_ResetVm(array $params): string
{
    $resetVM = new ModuleFunction\VM\Reset(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $resetVM->run($params);
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
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see katapult_AdminCustomButtonArray()
 *
 */
function katapult_StartVm(array $params): string
{
    $startVM = new ModuleFunction\VM\Start(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $startVM->run($params);
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
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see katapult_AdminCustomButtonArray()
 *
 */
function katapult_ShutdownVm(array $params): string
{
    $shutdownVM = new ModuleFunction\VM\Shutdown(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $shutdownVM->run($params);
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
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
function katapult_AdminServicesTabFields(array $params): array
{
    $adminServicesTabFields = new ModuleFunction\AdminServicesTabFields(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $adminServicesTabFields->run($params);
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
 * @throws \WHMCS\Module\Server\Katapult\Exceptions\Exception
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 */
function katapult_ClientArea(array $params): array
{
    $clientArea = new ModuleFunction\ClientArea(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $clientArea->run($params);
}
