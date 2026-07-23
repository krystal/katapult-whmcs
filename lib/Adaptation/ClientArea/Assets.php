<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Adaptation\ClientArea;

use WHMCS\Module\Server\Katapult\Helpers\OverrideHelper;
use WHMCS\Module\Server\Katapult\Helpers\Replay;
use WHMCS\Utility\Environment\WebHelper;

class Assets
{
    public static function addAssetsToHead(): string
    {
        $baseUrl = htmlentities(WebHelper::getBaseUrl());
        $replayToken = htmlentities(Replay::getToken());

        $cssPath = OverrideHelper::asset('dist/css/client.css');
        $jsPath = OverrideHelper::asset('dist/js/client.js');
        $cssVersion = OverrideHelper::version($cssPath);
        $jsVersion = OverrideHelper::version($jsPath);

        return <<<HTML
<link href="{$baseUrl}/modules/servers/katapult/{$cssPath}?{$cssVersion}" rel="stylesheet" type="text/css" />
<script type="text/javascript" defer src="{$baseUrl}/modules/servers/katapult/{$jsPath}?{$jsVersion}"></script>
<script type="text/javascript">const knrpToken = "{$replayToken}";</script>
HTML;
    }
}
