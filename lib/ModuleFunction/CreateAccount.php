<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use Krystal\Katapult\KatapultAPI\Model\DataCenterLookup;
use Krystal\Katapult\KatapultAPI\Model\DiskTemplateLookup;
use Krystal\Katapult\KatapultAPI\Model\OrganizationsOrganizationVirtualMachinesBuildPostBody;
use Krystal\Katapult\KatapultAPI\Model\OrganizationsOrganizationVirtualMachinesBuildPostResponse201;
use Krystal\Katapult\KatapultAPI\Model\VirtualMachinePackageLookup;
use WHMCS\Module\Server\Katapult\Exceptions\VirtualMachines\VirtualMachineBuildNotFound;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;
use WHMCS\Module\Server\Katapult\Katapult\ManagedOrganization;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;
use Carbon\Carbon;

final class CreateAccount extends APIModuleCommand
{
    public function run(array $params): string
    {
        try {
            $params = new VMServerModuleParams($params, $this->katapultAPI, $this->keyValueStore);

            // Do we have an existing build running? Is it done?
            try {
                $params->service->checkForExistingBuildAttempt($this->katapultAPI);

                // Great, it's done!
                return 'success';
            } catch (VirtualMachineBuildNotFound $e) {
                // This is fine, and normal behaviour.
            }

            $vmBuildRequest = new OrganizationsOrganizationVirtualMachinesBuildPostBody();

            // Organization
            $managedOrganization = new ManagedOrganization($this->katapultAPI, $this->keyValueStore);
            $vmBuildRequest->setOrganization($managedOrganization->getForClient($params->client));

            // Package
            $virtualMachinePackageLookup = new VirtualMachinePackageLookup();
            $virtualMachinePackageLookup->setPermalink($params->package);
            $vmBuildRequest->setPackage($virtualMachinePackageLookup);

            // Data Center
            $dataCenterLookup = new DataCenterLookup();
            $dataCenterLookup->setPermalink($params->dataCenter);
            $vmBuildRequest->setDataCenter($dataCenterLookup);

            // Disk Template
            $diskTemplateLookup = new DiskTemplateLookup();
            $diskTemplateLookup->setPermalink($params->diskTemplate);
            $vmBuildRequest->setDiskTemplate($diskTemplateLookup);

            // Hostname
            $hostname = $params->getHostname();
            if (!is_null($hostname)) {
                $vmBuildRequest->setHostname($hostname);
            }

            $apiResult = $this->katapultAPI->postOrganizationVirtualMachinesBuild($vmBuildRequest);

            if (!$apiResult instanceof OrganizationsOrganizationVirtualMachinesBuildPostResponse201) {
                throw APIException::new(
                    $apiResult,
                    OrganizationsOrganizationVirtualMachinesBuildPostResponse201::class,
                );
            }

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
        } catch (\Throwable $e) {
            return \Katapult\formatError('Create Account', $e);
        }
    }
}
