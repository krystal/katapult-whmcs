<p align="center"><img src="./build/katapult-logo.png" alt="Katapult logo" /></p>

# Katapult WHMCS Module

* [Requirements](#requirements)
* [Versioning](#versioning)
* [Security](#security)
* [Installing and upgrading](#installing-and-upgrading)
* [Initial configuration](#initial-configuration)
    * [Create a server](#create-a-server)
    * [Create a product group](#create-a-product-group)
    * [Create the first product](#create-the-first-product)
* [Creating more products](#creating-more-products)
* [Renaming configurable options](#renaming-configurable-options)
* [Configuration](#configuration)
* [Template and asset overrides](#template-and-asset-overrides)
* [Notes](#notes)
    * [Logging](#logging)
    * [Replay protection](#replay-protection)
    * [Virtual Machine Builds](#virtual-machine-builds)
* [Going further](#going-further)
    * [Hooks](#hooks)
        * [KatapultVirtualMachineBuildRequested](#katapultvirtualmachinebuildrequested)
        * [KatapultVirtualMachineBuilt](#katapultvirtualmachinebuilt)
        * [KatapultVirtualMachineBuildTimedOut](#katapultvirtualmachinebuildtimedout)
    * [Reference](#reference)
* [Development](#development)
    * [Building the module for distribution](#building-the-module-for-distribution)
    
## Requirements
* PHP >= 7.4
* WHMCS >= 8.0
* A Katapult account with access to managed organizations
* A Katapult API key
* Your Katapult organization's ID (preferred) or subdomain

## Versioning
This module follows [semantic versioning](https://semver.org/).

## Security
If you discover any security related issues, please email contact@krystal.uk instead of using the issue tracker.

## Installing and upgrading
Download the [latest release ZIP](https://github.com/krystal/katapult-whmcs/releases) and extract it to `/whmcs/modules/servers/katapult`. The resulting file structure should look similar to this:

```
├── /whmcs/modules/servers/katapult
│   ├── assets
│   ├── composer.json
│   ├── composer.lock
│   ├── helpers.php
│   ├── hooks.php
│   ├── katapult.php
│   ├── lib
│   ├── overrides
│   ├── README.md
│   ├── vendor
│   └── views
```

The Katapult module will then be available for you to use inside WHMCS.

## Initial configuration

### Create a server
Due to a [limitation in WHMCS (#18)](https://github.com/krystal/katapult-whmcs/issues/18), a server is required to use the SSO functionality of this module, which is used to open a console session on a VM.

[Create a server in WHMCS](https://docs.whmcs.com/Servers#Adding_a_New_Server) and assign it to the Katapult module. You can use any values in the fields (hostname, password etc) as they're not used anywhere.

**⚠️ Note:** Ensure the server is the default (marked with an asterisk*). All future Katapult services will need to be assigned to it for SSO to function.

### Create a product group
It's a good idea to split the Katapult products into a new [product group](https://docs.whmcs.com/Product_Groups), you should do this before creating a new product.

**Note:** Katapult products do not need to be separated from your other products if you don't want them to be.

### Create the first product
Before you can sell a Katapult service, you need to create a new WHMCS product. [Create a product as normal](https://docs.whmcs.com/Setting_Up_Your_First_Product) and set the module to `Katapult`. It is important that at this stage you click **Save Changes** before proceeding.

You can now click the 'Other' tab on the product's configuration, and you will be presented with a GUI to enter your Katapult API key and parent organization. Fill those in and click **Save Changes**. Please note, this GUI will not show up unless the product is assigned to the Katapult module and then saved, before viewing the 'Other' tab.

When you save your API key, WHMCS will connect to Katapult and sync available datacenters and disk templates for virtual machines and assign them to configurable options for the Katapult products.

Once the API key and parent organization have been saved, you can go back to the 'Module Settings' tab, and select the VM package you wish to assign to this product, and then **Save Changes**.

You are now ready to sell this product as you would any other WHMCS product.

## Creating more products
When creating a new product, the most important things to confirm are the Katapult module is selected, and the configurable option group `Katapult Virtual Machines` is assigned to it.

## Renaming configurable options
The Katapult module keeps its own record of the configurable options it creates to allow it to automatically sync them with Katapult. You can change the name of configurable options and the groups as you need.

Dropdown options such as disk template and data center use [friendly display names](https://docs.whmcs.com/Addons_and_Configurable_Options#Friendly_Display_Names) - you can change everything after the first `|` (pipe) symbol in their names if desired.

If you wish to manually sync options from Katapult, you can do so by ticking the 'Re-sync' checkbox on the 'Other' tab of a Katapult product.

**⚙️ Note:** Data centers and disk templates are automatically synced from Katapult daily, and new ones are hidden by default, you must un-hide them as required. This is to give you the control of selling into new regions and with new disk templates as you see fit.

## Configuration

You can set the Katapult API key and other options in a products settings under the 'Other' tab for any Katapult product. The settings will be used for all products.

## Template and asset overrides
The module comes with whitelabel defaults for the client area. You can however override the template, JS and CSS files with your own. The defaults are neither pre-compiled nor minified for ease of editing, they also contain comments to explain what's going on.

To override a default file, simply copy the default into `overrides` and then apply your changes to that file:

```shell
cp views/client/virtual_machines/overview.tpl overrides/views/client/virtual_machines/overview.tpl
```

Your override file will then be used instead of the default. Overrides can be used for any files in `assets` or `views`. An example directory structure is provided by default but is not required.

## Notes

### Logging
Operations on a VM are logged against the service via the client's activity log. This is the case for both admins and clients, for complete traceability.

For debugging purposes, all HTTP requests to the Katapult API can also be logged in detail by enabling the [WHMCS module debug log](https://docs.whmcs.com/Troubleshooting_Module_Problems).

### Replay protection
The module's client area actions are protected by a no replay token, which is automatically appended to action URLs. This prevents the end user from bookmarking the shutdown URL and shutting their VM down everytime they view it in their client area via their bookmark.

WHMCS does not protect module actions by default, so be aware that a page can't be refreshed to re-run an action on a VM, which may be possible with other modules. For more information see [this issue](https://github.com/krystal/katapult-whmcs/issues/8).

### Virtual Machine Builds
Virtual machines are built asynchronously on Katapult. This means when a service is created in WHMCS, it will call to Katapult and ask it to build a VM. At this point, there is no VM, but the service is considered active. There is a task which runs on the WHMCS cron (which should be called as often as possible, usually every 5 minutes) which will call back to Katapult to check if the VM is built. When the VM is built, the initial root password, hostname, IP addresses and VM ID will be persisted to the WHMCS database.

The issue raised with this, is WHMCS considers the service active when the build has been requested. It will then send the services welcome email (if configured). That's not useful if you want to include the VM's IP address or hostname, as WHMCS doesn't know it at that point. This problem can be solved by using a hook to send the services welcome email and disabling the default welcome email in WHMCS on the products settings.

## Going further

### Hooks
This module exposes some hooks for you to interact with at key lifecycle events of objects.

#### KatapultVirtualMachineBuildRequested
Fires when a VM build has been created with Katapult.

```php
use \WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;

\add_hook('KatapultVirtualMachineBuildRequested', 0, function(VirtualMachine $service) {
    \sendMessage('Katapult Server Building Email', $service->id);
});
```

#### KatapultVirtualMachineBuilt
Fires when a VM has finished building and has been persisted to the WHMCS database.

```php
use \WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;

\add_hook('KatapultVirtualMachineBuilt', 0, function(VirtualMachine $service) {
    \sendMessage('Katapult Server Built Email', $service->id);
});
```

#### KatapultVirtualMachineBuildTimedOut
Fires when a VM build has timed out. The module will continue to check for the build, but this hook will only be fired once. The timeout is set to 15 minutes.

```php
use \WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;

\add_hook('KatapultVirtualMachineBuildTimedOut', 0, function(VirtualMachine $service) {
    // Send a notification to an admin or open a ticket
});
```

### Reference

The `\WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine` class ultimately extends from the WHMCS service model, `WHMCS\Service\Service`, which is a [Laravel](https://laravel.com/docs/8.x/eloquent) model, which references `tblhosting` in the WHMCS database.

This module uses the [Salmon](https://github.com/grizzlyware/salmon-whmcs) datastore to persist data to WHMCS, outside the default tables, using `mod_salmon_data_store_items`. Data such as VM ID, build ID, organization ID and the encrypted API key are stored in this table.

Because of its parentage, you can instantiate a Katapult service like this:

```php
use \WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine as VirtualMachineService;
use Krystal\Katapult\API\RestfulKatapultApiV1\Resources\Organization\VirtualMachine as KatapultVirtualMachine;

/** @var VirtualMachineService $katapultService */
$katapultService = VirtualMachineService::findOrFail(1337); // 1337 being the ID of the service in tblhosting

// You can then access various details about the service and even the virtual machine itself
$katapultService->vm_build_id; // string|null - The build ID Katapult created for the VM
$katapultService->vm_build_started_at; // \Carbon\Carbon|null - When WHMCS requested the VM build
$katapultService->vm_id; // string|null - The VM ID. This is only available once the VM has been built.
$katapultService->vm; // KatapultVirtualMachine|null - the live VM instance from Katapult. It is cached per request lifecycle.
$katapultService->vm_state; // string - note, this will be 'unknown' if the VM does not exist

/** @var KatapultVirtualMachine|null $virtualMachine */
$virtualMachine = $katapultService->vm;

// Assuming we have a VM, we can perform actions against it
$virtualMachine->start();
$virtualMachine->stop();
$virtualMachine->shutdown();
$virtualMachine->reset();
$virtualMachine->createConsoleSession();
```

More details about interacting with the Katapult VM instance can be found in the [Katault PHP library](https://github.com/krystal/katapult-php).

## Development
You can clone this repository directly into your development WHMCS installation, for example using [this Vagrant box](https://github.com/grizzlyware/whmcs-dev/):

```shell
# Assuming you're WHMCS installation is at `/var/www/html/whmcs`
# This would normally be run on your host machine, not in the Vagrant VM
cd /var/www/html/whmcs/modules/servers
git clone git@github.com:krystal/katapult-whmcs.git katapult
cd katapult
composer install
```

### Building the module for distribution
When changes have been made to the module, and a new release is being published, a new ZIP file will need to be created to attach to the release. These ZIP files are used to distribute and install the module into WHMCS.

The module has a few require-dev dependencies which aren't required for use within WHMCS. Guzzle being one which has been known to cause conflicts with WHMCS, and is required by [krystal/katapult-php](https://github.com/krystal/katapult-php). Because WHMCS has Guzzle installed, we don't need to include it the distribution of this module.

Things to consider when packaging the module up:

* Installing the required Composer dependencies
* Removing development and untracked files
* Adding special files such as `.htaccess` in `/vendor` (WHMCS restriction with it being in the docroot)
* Zipping it up consistently between versions.

To build the module automatically:

```shell
$ ./bin/katapult build:server-module
```

This will result in a `katapult.zip` file in your `build` directory, the full path will be outputted by the command.

