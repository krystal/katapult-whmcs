# Katapult WHMCS Module

You can set the Katapult API key in a products settings under the 'Other' tab, after selecting the product as 'Katapult'. This key will be used for all products.

Alternatively, you set your Katapult API key in your WHMCS configuration file, or other secure file which is included at WHMCS runtime:

```php
define('KATAPULT_API_V1_KEY', 'M8X34xE........57Jr7f');
```

When using the constant method, the GUI to update the API key will be hidden from the product's configuration page in WHMCS.

## Notes

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
    \sendMessage('Katapult Server Ready Email', $service->id);
});
```
