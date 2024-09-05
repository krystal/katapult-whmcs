<?php

declare(strict_types=1);

namespace WHMCS\Module\Server\Katapult\Katapult;

use KatapultAPI\Core\Client as KatapultAPIClient;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\Katapult\Katapult\KeyValueStore\KeyValueStoreInterface;
use WHMCS\Module\Server\Katapult\KatapultWHMCS;

class ValidateConfiguration
{
    public function __construct(
        private readonly KatapultAPIClient $katapultAPI,
        private readonly KeyValueStoreInterface $keyValueStore,
    ) {
    }

    public function validateCartProducts(array $cartProducts): ?array
    {
        $errors = [];

        foreach ($cartProducts as $product) {
            $productId = $product['pid'];
            $configOptions = $product['configoptions'];

            $errors = array_merge($errors, $this->validateProductInCart($productId, $configOptions ?? []));
        }

        return empty($errors) ? null: $errors;
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

        $customDiskSize = $configOptions[$this->customDiskSizeConfigOptionId()];

        if ($customDiskSize === 0) {
            return [];
        }

        $diskTemplateConfigOptionId = $this->diskTemplateConfigOptionId();

        $chosenDiskTemplate = $configOptions[$diskTemplateConfigOptionId];

        $diskTemplatePermalinkWithName = Capsule::table('tblproductconfigoptionssub')
            ->where('id', $chosenDiskTemplate)
            ->value('optionname');

        [$diskTemplatePermalink, $diskTemplateName] = explode('|', $diskTemplatePermalinkWithName, 2);

        $diskTemplate = $this->katapultAPI->getDiskTemplate([
            'disk_template[permalink]' => $diskTemplatePermalink,
        ]);

        $minimumSize = $diskTemplate->getDiskTemplate()->getSizeInGb();

        if ($customDiskSize < $minimumSize) {
            return [
                sprintf('Custom disk size must be at least %dGB for %s', $minimumSize, $diskTemplateName),
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

    // @TODO REFACTOR OUT
    private function diskTemplateConfigOptionId(): int
    {
        return $this->keyValueStore->read(KatapultWHMCS::DS_VM_CONFIG_OPTION_DISK_TEMPLATE_ID);
    }

    // @TODO REFACTOR OUT
    private function customDiskSizeConfigOptionId(): int
    {
        return $this->keyValueStore->read(KatapultWHMCS::DS_VM_CONFIG_OPTION_CUSTOM_DISK_SIZE_ID);
    }
}
