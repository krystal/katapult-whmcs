<?php

use WHMCS\Module\Server\Katapult\Adaptation\AdminArea;
use WHMCS\Module\Server\Katapult\Adaptation\ClientArea;
use WHMCS\Module\Server\Katapult\Adaptation\System as SystemAdaptation;
use WHMCS\Module\Server\Katapult\Katapult\ParentOrganization;
use WHMCS\Module\Server\Katapult\Katapult\ValidateConfiguration;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require(__DIR__ . '/vendor/autoload.php');

// System
\add_hook('DailyCronJob', 0, [SystemAdaptation::class, 'syncConfigOptions']);
\add_hook('AfterCronJob', 0, [SystemAdaptation::class, 'syncVmBuilds']);

// Client checkout
\add_hook('ShoppingCartValidateProductUpdate', 0, function () {
    $validator = new ValidateConfiguration(
        \Katapult\APIClient(),
        \Katapult\keyValueStore(),
    );

    return $validator->validateCartProducts($_SESSION['cart']['products']);
});

// Admin area
\add_hook('AdminProductConfigFields', 0, function ($vars) {
    $configurationPane = new AdminArea\ConfigurationPane(
        new ParentOrganization(
            \Katapult\APIClient(),
            \Katapult\keyValueStore(),
        ),
        \Katapult\APIKey()
    );

    return $configurationPane($vars);
});

\add_hook('AdminProductConfigFieldsSave', 0, function ($vars) {
    $updateConfiguration = new AdminArea\UpdateConfiguration(
        new ParentOrganization(
            \Katapult\APIClient(),
            \Katapult\keyValueStore(),
        ),
        \Katapult\APIKey()
    );

    $updateConfiguration($vars);
});

// JS and CSS assets
\add_hook('AdminAreaHeadOutput', 0, [AdminArea\Assets::class, 'addAssetsToHead']);
\add_hook('ClientAreaHeadOutput', 0, [ClientArea\Assets::class, 'addAssetsToHead']);
