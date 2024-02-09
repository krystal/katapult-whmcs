<?php

namespace WHMCS\Module\Server\Katapult\Adaptation;

use WHMCS\Module\Server\Katapult\Helpers\OverrideHelper;
use WHMCS\Module\Server\Katapult\Helpers\Replay;
use WHMCS\Utility\Environment\WebHelper;

class ClientArea
{
    public static function addAssetsToHead(): string
    {
        $baseUrl = htmlentities(WebHelper::getBaseUrl());
        $replayToken = htmlentities(Replay::getToken());

        $cssPath = OverrideHelper::asset('dist/css/client.css');
        $jsPath = OverrideHelper::asset('dist/js/client.js');

        return <<<HTML
<link href="{$baseUrl}/modules/servers/katapult/{$cssPath}?1617183192" rel="stylesheet" type="text/css" />
<script type="text/javascript" defer src="{$baseUrl}/modules/servers/katapult/{$jsPath}?1617183192"></script>
<script type="text/javascript">const knrpToken = "{$replayToken}";</script>
HTML;
    }
}
