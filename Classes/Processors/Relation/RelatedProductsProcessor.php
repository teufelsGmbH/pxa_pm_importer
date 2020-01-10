<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaProductManager\Domain\Model\Product;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $fields = array_merge($this->defaultNewFields($importId), [
            $this->tcaHiddenField() => 1,
            $this->tcaLabelField() => $importId,
        ]);

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_pxaproductmanager_domain_model_product')
            ->insert(
                'tx_pxaproductmanager_domain_model_product',
                $fields
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
