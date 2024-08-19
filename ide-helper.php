<?php

namespace {
    /**
     * Log module call.
     *
     * @param string       $module        The name of the module
     * @param string       $action        The name of the action being performed
     * @param string|array $requestString The input parameters for the API call
     * @param string|array $responseData  The response data from the API call
     * @param string|array $processedData The resulting data after any post processing (eg. json decode, xml decode, etc...)
     * @param array        $replaceVars   An array of strings for replacement
     */
    function logModuleCall(
        string $module,
        string $action,
        string|array $requestString,
        string|array $responseData,
        string|array $processedData,
        array $replaceVars
    ) {
    }

    /**
     * Log activity.
     *
     * @param string $message  The message to log
     * @param int    $clientId An optional client id to which the log entry relates
     */
    function logActivity(string $message, int $clientId = 0)
    {
    }

    /**
     * Add a hook
     *
     * @param string   $hookName
     * @param int      $priority
     * @param callable $callable
     *
     * @return void
     */
    function add_hook(string $hookName, int $priority, callable $callable)
    {
    }

    function encrypt(string|null $text): string
    {
        return '';
    }

    function decrypt(string $text)
    {
    }

    function run_hook(string $name, array $args, bool $what)
    {
    }

    class Eloquent extends \Illuminate\Database\Eloquent\Model
    {
        /**
         * Find a model by its primary key or throw an exception.
         *
         * @param mixed $id
         * @param array $columns
         *
         * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static|static[]
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
         * @static
         */
        public static function findOrFail($id, $columns = [])
        {
            /** @var \Illuminate\Database\Eloquent\Builder $instance */
            return $instance->findOrFail($id, $columns);
        }

        /**
         * Find a model by its primary key or return null
         *
         * @param mixed $id
         * @param array $columns
         *
         * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null
         * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
         * @static
         */
        public static function find($id, $columns = [])
        {
            /** @var \Illuminate\Database\Eloquent\Builder $instance */
            return $instance->find($id, $columns);
        }
    }
}

namespace WHMCS\Billing {
    use Illuminate\Database\Eloquent\Model;

    /**
     * @mixin \Eloquent
     */
    class Currency extends Model
    {
    }
}

namespace WHMCS\Database {
    use Illuminate\Database\Capsule\Manager;

    class Capsule extends Manager
    {
    }
}

namespace WHMCS\Service {
    use Illuminate\Database\Eloquent\Model;
    /**
     * @see https://classdocs.whmcs.com/8.10/WHMCS/Service/Service.html
     *      Ideally we would have a way to generate this from that.
     *      For now, we just want this class to exist to satisfy Psalm/Stan and
     *      avoid false positive results there.
     *
     * @mixin \Eloquent
     *
     * @property string $domain
     * @property string $domainstatus
     */
    class Service extends Model
    {
    }
}

namespace WHMCS\User {
    use Illuminate\Database\Eloquent\Model;

    /**
     * @mixin \Eloquent
     */
    class Client extends Model
    {
    }
}

namespace WHMCS\Product {

    use Illuminate\Database\Eloquent\Model;

    /**
     * @mixin \Eloquent
     */
    class Product extends Model
    {
    }
}

namespace WHMCS\Utility\Environment {

    /**
     * @method static string getBaseUrl()
     */
    class WebHelper
    {
    }
}
