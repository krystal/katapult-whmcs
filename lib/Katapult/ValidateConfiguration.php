<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult;

use KatapultAPI\Core\Client as KatapultAPIClient;
use WHMCS\Module\Server\Katapult\Helpers\ConfigurableOption;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;

class ValidateConfiguration
{
    private ConfigurableOption $configurableOption;

    public function __construct(
        private readonly KatapultAPIClient $katapultAPI,
        private readonly KeyValueStoreInterface $keyValueStore,
    ) {
        $this->configurableOption = new ConfigurableOption($this->keyValueStore);
    }

    public function validateCartProducts(array $cartProducts): ?array
    {
        $errors = [];

        foreach ($cartProducts as $product) {
            $productId = $product['pid'];
            $configOptions = $product['configoptions'];

            $errors = array_merge($errors, $this->validateProductInCart($productId, $configOptions ?? []));
        }

        return empty($errors) ? null : $errors;
    }

    /**
     * Does this product's configuration look OK?
     *
     * @param int   $productId
     * @param array $configOptions
     *
     * @return string[]
     */
    private function validateProductInCart(int $productId, array $configOptions): array
    {
        // Does this product belong to our module?
        if (!$this->productIsKatapult($productId)) {
            return [];
        }

        $customDiskSizeConfigOptionId = $this->configurableOption->customDiskSizeConfigurableOptionId();

        $customDiskSizeInput = $configOptions[$customDiskSizeConfigOptionId];

        if ($customDiskSizeInput === 0) {
            return [];
        }

        $customDiskSize = $this->configurableOption->getChosenValue(
            $customDiskSizeConfigOptionId,
            $customDiskSizeInput,
        )->rawValue;

        $diskTemplateConfigOptionId = $this->configurableOption->diskTemplateConfigurableOptionId();

        $chosenDiskTemplateInput = $configOptions[$diskTemplateConfigOptionId];

        $chosenDiskTemplate = $this->configurableOption->getChosenValue(
            $diskTemplateConfigOptionId,
            $chosenDiskTemplateInput,
        );

        $diskTemplate = $this->katapultAPI->getDiskTemplate([
            'disk_template[permalink]' => $chosenDiskTemplate->rawValue,
        ]);

        $minimumSize = $diskTemplate->getDiskTemplate()->getSizeInGb();

        if ($customDiskSize < $minimumSize) {
            return [
                sprintf(
                    'Custom disk size must be at least %dGB for %s',
                    $minimumSize,
                    $chosenDiskTemplate->name ?? $chosenDiskTemplate->rawValue,
                ),
            ];
        }

        return [];
    }

    private function productIsKatapult(int $productId): bool
    {
        $product = \WHMCS\Product\Product::where('id', $productId)
            ->where('servertype', 'katapult')
            ->first();

        return !is_null($product);
    }
}
