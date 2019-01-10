<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Processors\Traits\InitRelationEntities;
use Pixelant\PxaProductManager\Domain\Model\Product;

/**
 * Class RelatedProductsProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class RelatedProductsProcessor extends AbstractRelationFieldProcessor
{
    use InitRelationEntities;

    /**
     * Set categories
     *
     * @param mixed $value
     * @return array
     */
    protected function initEntities($value): array
    {
        return $this->initEntitiesForTable($value, 'tx_pxaproductmanager_domain_model_product', Product::class);
    }
}
