<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Processors\Helpers\BulkInsertHelper;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\ProductRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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
    protected $defaultNewRecordFields = [];

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
     * Persist attribute values
     *
     * @param AbstractEntity $model
     * @param array $record
     * @param array $importRow
     * @return bool
     */
    protected function populateModelWithImportData(
        AbstractEntity $model,
        array $record,
        array $importRow
    ): bool {
        $result = parent::populateModelWithImportData($model, $record, $importRow);

        // Persist attribute values after each product
        $bulkInsert = GeneralUtility::makeInstance(BulkInsertHelper::class);
        if ($result && $bulkInsert->hasTableData('tx_pxaproductmanager_domain_model_attributevalue')) {
            $bulkInsert->setTypes(
                'tx_pxaproductmanager_domain_model_attributevalue',
                [
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT
                ]
            );
            $bulkInsert->persistBulkInsert('tx_pxaproductmanager_domain_model_attributevalue');
        } else {
            $bulkInsert->flushTable('tx_pxaproductmanager_domain_model_attributevalue');
        }

        return $result;
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
