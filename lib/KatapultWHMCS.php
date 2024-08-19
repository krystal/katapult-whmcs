<?php

namespace WHMCS\Module\Server\Katapult;

class KatapultWHMCS
{
    public const SERVER_MODULE = 'katapult';
    public const DS_API_V1_KEY = 'api_v1_key';
    public const DS_PARENT_ORGANIZATION = 'parent_organization';
    public const DS_VM_CONFIG_OPTION_GROUP_ID = 'vm_config_option_group_id';
    public const DS_VM_CONFIG_OPTION_DATACENTER_ID = 'vm_config_option_datacenter_id';
    public const DS_VM_CONFIG_OPTION_DISK_TEMPLATE_ID = 'vm_config_option_disk_template_id';

    public static function log(string $message, int $clientId = 0): void
    {
        \logActivity("[Katapult]: $message", $clientId);
    }

    public static function moduleLog(string $action, $request, $response, string $apiKey): void
    {
        // the API Key is passed into replaceVars which results in it not being
        // exposed when viewing the system module debug log
        \logModuleCall(
            KatapultWHMCS::SERVER_MODULE,
            $action,
            $request,
            $response,
            '',
            [$apiKey],
        );
    }
}
