<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer\Builder;

use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\ProductRepository;

/**
 * Class ProductImporterBuilder
 * @package Pixelant\PxaPmImporter\Service\Importer\Builder
 */
class ProductImporterBuilder extends AbstractBuilder
{
    /**
     * Add repository of import subject
     */
    public function addRepository(): void
    {
        $repository = $this->objectManager->get(ProductRepository::class);
        $this->importer->setRepository($repository);
    }

    /**
     * Add model name of import subject
     */
    public function addModelName(): void
    {
        $this->importer->setModelName(Product::class);
    }

    /**
     * Add table name of import subject
     */
    public function addDatabaseTableName(): void
    {
        $this->importer->setDatabaseTableName('tx_pxaproductmanager_domain_model_product');
    }

    /**
     * Add default fields of new created record
     */
    public function addDefaultNewRecordFields(): void
    {
        $this->importer->setDefaultNewRecordFields([]);
    }
}
