<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Adaptation\AdminArea;

use WHMCS\Module\Server\Katapult\Helpers\OverrideHelper;
use WHMCS\Utility\Environment\WebHelper;

class Assets
{
    public static function addAssetsToHead(): string
    {
        $baseUrl = htmlentities(WebHelper::getBaseUrl());

        $cssPath = OverrideHelper::asset('dist/css/admin.css');
        $jsPath = OverrideHelper::asset('dist/js/admin.js');

        return <<<HTML
<link href="{$baseUrl}/modules/servers/katapult/{$cssPath}" rel="stylesheet" type="text/css" />
<script type="text/javascript" defer src="{$baseUrl}/modules/servers/katapult/{$jsPath}"></script>
HTML;
    }
}
