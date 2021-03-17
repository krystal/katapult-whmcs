<?php

namespace WHMCS\Module\Server\Katapult\Adaptation;

use WHMCS\Module\Server\Katapult\Helpers\Replay;
use WHMCS\Utility\Environment\WebHelper;

class ClientArea
{
	public function addAssetsToHead(): string
	{
		$baseUrl = WebHelper::getBaseUrl();
		$replayToken = htmlentities(Replay::getToken());

		return <<<HTML
<link href="{$baseUrl}/modules/servers/katapult/assets/dist/css/client.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" defer src="{$baseUrl}/modules/servers/katapult/assets/dist/js/client.js"></script>
<script type="text/javascript">const knrpToken = "{$replayToken}";</script>
HTML;
	}
}

