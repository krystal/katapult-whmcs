<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Helpers;

class Database
{
    public static function getPdo(): \PDO
    {
        return \Illuminate\Database\Capsule\Manager::connection()->getPdo();
    }
}
