# Katapult WHMCS Module

You can set the Katapult API key in a products settings under the 'Other' tab, after selecting the product as 'Katapult'. This key will be used for all products.

## Template and asset overrides
The module comes with whitelabel defaults for the client area. You can however override the template, JS and CSS files with your own. The defaults are neither pre-compiled nor minified for ease of editing, they also contain comments to explain what's going on.

To override a default file, simply copy the default into `overrides` and then apply your changes to that file:

```shell
cp views/client/virtual_machines/overview.tpl overrides/views/client/virtual_machines/overview.tpl
```

Your override file will then be used instead of the default. Overrides can be used for any files in `assets` or `views`. An example directory structure is provided by default but is not required.

## Notes

### Logging
Operations on the VM are logged against the service via the client's activity log. This is the case for both admins and clients, for complete traceability.

### Replay protection
The module's client area actions are protected by a no replay token, which is automatically appended to action URLs. This prevents the end user from bookmarking the shutdown URL and shutting their VM down everytime they view it in their client area via their bookmark.

WHMCS does not protect module actions by default, so be aware that a page can't be refreshed to re-run an action on a VM, which may be possible with other modules. For more information see [this issue](https://github.com/krystal/katapult-whmcs/issues/8).

### VM Builds
VMs are built asynchronously on Katapult. This means when a service is created in WHMCS, it will call to Katapult and ask it to build a VM. At this point, there is no VM, but the service is considered active. There is a task which runs on the WHMCS cron (which should be called as often as possible, usually every 5 minutes) which will call back to Katapult to check if the VM is built. When the VM is built, the initial root password, hostname, IP addresses and VM ID will be persisted to the WHMCS database.

The issue raised with this, is WHMCS considers the service active when the build has been requested. It will then send the services welcome email (if configured). That's not useful if you want to include the VM's IP address or hostname, as WHMCS doesn't know it at that point. This problem can be solved by using a hook to send the services welcome email and disabling the default welcome email in WHMCS on the products settings.

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

## Reference

The `\WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine` class ultimately extends from the WHMCS service model, `WHMCS\Service\Service`, which is a [Laravel](https://laravel.com/docs/8.x/eloquent) model, which references `tblhosting` in the database.

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

## Requirements
* PHP >= 7.4
* WHMCS >= 8.0
* A Katapult account with access to managed organizations

## Versioning
This module follows [semantic versioning](https://semver.org/).

## Security
If you discover any security related issues, please email contact@krystal.uk instead of using the issue tracker.

