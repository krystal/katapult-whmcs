<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult\Sync;

use Krystal\Katapult\KatapultAPI\Client as KatapultAPIClient;
use WHMCS\Module\Server\Katapult\Helpers\Database;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;
use WHMCS\Module\Server\Katapult\WHMCS\Service\VirtualMachine;

class VMBuilds
{
    public function __construct(
        private readonly KatapultAPIClient $katapultAPI,
    ) {
    }

    public function sync(): void
    {
        $log = function ($message, VirtualMachine $service = null) {
            $message = "[SyncVmBuilds]: {$message}";

            if ($service) {
                $service->log($message);
            } else {
                KatapultWHMCS::log($message);
            }
        };

        // Find Katapult services with a build ID but not a VM ID, and sync them.
        $sql = <<<SQL
SELECT DISTINCT tblhosting.id as id
FROM tblhosting
         INNER JOIN tblproducts ON tblproducts.id = tblhosting.packageid

WHERE domainstatus = 'Active'
  AND tblproducts.servertype = 'katapult'
  AND EXISTS(SELECT id
             FROM mod_salmon_data_store_items
             WHERE rel_id = tblhosting.id
               AND rel_type = 'service'
               AND `key` = 'vm_build_id'
               AND value_index IS NOT NULL)

  AND NOT EXISTS(SELECT id
                 FROM mod_salmon_data_store_items
                 WHERE rel_id = tblhosting.id
                   AND rel_type = 'service'
                   AND `key` = 'vm_id'
                   AND value_index IS NOT NULL);
SQL;

        $stmt = Database::getPdo()->query($sql);

        // Nothing to do...
        if ($stmt->rowCount() < 1) {
            return;
        }

        $log(sprintf("Found %d VM builds", $stmt->rowCount()));

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            try {
                /** @var VirtualMachine $service */
                $service = VirtualMachine::findOrFail($row['id']);
                $log("Starting", $service);
                $service->checkForExistingBuildAttempt($this->katapultAPI);
            } catch (\Throwable $e) {
                if (isset($service) && $service) {
                    $log("Error: {$e->getMessage()}", $service);
                } else {
                    $log("Error: Service ID: " . $row['id'] . ": {$e->getMessage()}");
                }
            }
        }
    }
}
