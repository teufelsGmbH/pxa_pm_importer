<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaProductManager\Domain\Model\ProductType;

/**
 * Class ProductTypeProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class ProductTypeProcessor extends AbstractRelationFieldProcessor implements AbleCreateMissingEntities
{
    /**
     * @inheritDoc
     */
    public function createMissingEntity(string $importId)
    {
        $fields = ['name' => $importId, $this->tcaHiddenField() => 1];
        $sysLanguageUid = 0;

        $this->repository->createEmpty(
            $importId,
            'tx_pxaproductmanager_domain_model_producttype',
            $sysLanguageUid,
            $this->newRecordFieldsWithPlaceHolder($fields)
        );
    }

    /**
     * @inheritDoc
     */
    protected function domainModel(): string
    {
        return ProductType::class;
    }
}
