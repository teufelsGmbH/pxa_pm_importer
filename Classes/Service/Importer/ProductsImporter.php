<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\ProductRepository;

/**
 * Class ProductsImporter
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
class ProductsImporter extends AbstractImporter
{
    /**
     * @var ProductRepository
     */
    protected $repository = null;

    /**
     * Default fields for new record
     *
     * @var array
     */
    protected $defaultNewRecordFields = [

    ];

    /**
     * @param SourceInterface $source
     * @param Import $import
     * @param array $configuration
     */
    public function preImport(SourceInterface $source, Import $import, array $configuration = []): void
    {
    }

    /**
     * @param Import $import
     */
    public function postImport(Import $import): void
    {
    }

    /**
     * Set table name
     */
    protected function initDbTableName(): void
    {
        $this->dbTable = 'tx_pxaproductmanager_domain_model_product';
    }

    /**
     * Init repository
     */
    protected function initRepository(): void
    {
        $this->repository = $this->objectManager->get(ProductRepository::class);
    }

    /**
     * Category mode name
     */
    protected function initModelName(): void
    {
        $this->modelName = Product::class;
    }
}
