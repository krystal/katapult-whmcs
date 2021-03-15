<?php

namespace WHMCS\Module\Server\Katapult;

use Grizzlyware\Salmon\WHMCS\Product\Product;

class KatapultHelper
{
	public static function productIdIsKatapult(int $productId): bool
	{
		return Product::where('id', $productId)->where('servertype', KatapultWhmcs::SERVER_MODULE)->count() > 0;
	}
}


