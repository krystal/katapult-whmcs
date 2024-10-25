<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\ModuleFunction;

use KatapultAPI\Core\Model\DiskBackupPoliciesDiskBackupPolicyDeleteBody;
use KatapultAPI\Core\Model\DiskBackupPoliciesDiskBackupPolicyDeleteResponse200;
use KatapultAPI\Core\Model\DiskBackupPolicyLookup;
use KatapultAPI\Core\Model\DisksDiskDiskBackupPoliciesGetResponse200;
use KatapultAPI\Core\Model\VirtualMachinesVirtualMachineDeleteBody;
use KatapultAPI\Core\Model\VirtualMachinesVirtualMachineDeleteResponse200;
use KatapultAPI\Core\Model\VirtualMachinesVirtualMachineDisksGetResponse200;
use WHMCS\Module\Server\Katapult\Katapult\API\APIException;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;
use WHMCS\Module\Server\Katapult\WhmcsModuleParams\VMServerModuleParams;

final class TerminateAccount extends APIModuleCommand
{
    public function run(array $params): array|string
    {
        $this->runCommandOnVM->checkServiceIsActive = false;

        return $this->runCommandOnVM->runOnVm($params, function (VMServerModuleParams $params) {
            $this->deleteDiskBackupPoliciesForVm($params->service);

            $requestBody = new VirtualMachinesVirtualMachineDeleteBody();
            $requestBody->setVirtualMachine($params->service->virtual_machine_lookup);

            $deleteVirtualMachineResult = $this->katapultAPI->deleteVirtualMachine($requestBody);

            return \Katapult\handleAPIResponse(
                VirtualMachinesVirtualMachineDeleteResponse200::class,
                $deleteVirtualMachineResult,
                $params->service,
                'VM deleted and local data store cleared',
                'VM failed to be deleted',
                fn() => $params->service->clearAllDataStoreValues()
            );
        });
    }

    /**
     * @throws APIException
     */
    private function deleteDiskBackupPoliciesForVm(VirtualMachine $virtualMachine): void
    {
        // Delete disk backup policies
        $virtualMachineDisksResponse = $this->katapultAPI->getVirtualMachineDisks([
            'virtual_machine[id]' => $virtualMachine->vm_id,
        ]);

        if (!$virtualMachineDisksResponse instanceof VirtualMachinesVirtualMachineDisksGetResponse200) {
            throw APIException::new(
                $virtualMachineDisksResponse,
                VirtualMachinesVirtualMachineDisksGetResponse200::class,
            );
        }

        $disks = $virtualMachineDisksResponse->getDisks();

        foreach ($disks as $disk) {
            $diskBackupPoliciesResponse = $this->katapultAPI->getDiskDiskBackupPolicies([
                'disk[id]' => $disk->getDisk()->getId(),
            ]);

            if (!$diskBackupPoliciesResponse instanceof DisksDiskDiskBackupPoliciesGetResponse200) {
                throw APIException::new(
                    $diskBackupPoliciesResponse,
                    DisksDiskDiskBackupPoliciesGetResponse200::class,
                );
            }

            \Katapult\handleAPIResponse(
                DisksDiskDiskBackupPoliciesGetResponse200::class,
                $diskBackupPoliciesResponse,
                $virtualMachine,
                null,
                'Error getting disk backup policies',
                function () use ($diskBackupPoliciesResponse, $virtualMachine) {
                    foreach ($diskBackupPoliciesResponse->getDiskBackupPolicies() as $diskBackupPolicy) {
                        $requestBody = new DiskBackupPoliciesDiskBackupPolicyDeleteBody();
                        $diskBackupPolicyLookup = new DiskBackupPolicyLookup();
                        $diskBackupPolicyLookup->setId($diskBackupPolicy->getId());
                        $requestBody->setDiskBackupPolicy($diskBackupPolicyLookup);

                        $deleteResult = $this->katapultAPI->deleteDiskBackupPolicy($requestBody);

                        \Katapult\handleAPIResponse(
                            DiskBackupPoliciesDiskBackupPolicyDeleteResponse200::class,
                            $deleteResult,
                            $virtualMachine,
                            'Deleted disk backup policy ' . $diskBackupPolicy->getId(),
                            'Error deleting disk backup policy ' . $diskBackupPolicy->getId()
                        );
                    }
                }
            );
        }
    }
}
