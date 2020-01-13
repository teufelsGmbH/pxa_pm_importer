<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaProductManager\Domain\Model\Product;

/**
 * Class RelatedProductsProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class RelatedProductsProcessor extends AbstractRelationFieldProcessor implements AbleCreateMissingEntities
{
    /**
     * @inheritDoc
     */
    public function createMissingEntity(string $importId)
    {
        $fields = [
            $this->tcaHiddenField() => 1,
            $this->tcaLabelField() => $importId
        ];

        $this->repository->createEmpty(
            $importId,
            'tx_pxaproductmanager_domain_model_product',
            0,
            $this->newRecordFieldsWithPlaceHolder($fields)
        );
    }

    /**
     * @inheritDoc
     */
    protected function domainModel(): string
    {
        return Product::class;
    }
}
