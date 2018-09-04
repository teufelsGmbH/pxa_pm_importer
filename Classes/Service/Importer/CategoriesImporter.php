<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaProductManager\Domain\Model\Category;
use Pixelant\PxaProductManager\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CategoriesImporter
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
class CategoriesImporter extends AbstractImporter
{
    /**
     * @var CategoryRepository
     */
    protected $repository = null;

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
        $this->dbTable = 'sys_category';
    }

    /**
     * Init repository
     */
    protected function initRepository(): void
    {
        $this->repository = $this->objectManager->get(CategoryRepository::class);
    }

    /**
     * Category mode name
     */
    protected function initModelName(): void
    {
        $this->modelName = Category::class;
    }

    /**
     * Do import of data
     */
    protected function runImport(): void
    {
        $languages = $this->adapter->getLanguages();

        foreach ($languages as $language) {
            $data = $this->adapter->getLanguageData($language);

            // One row per record
            foreach ($data as $row) {
                $id = $this->getImportIdFromRow($row);
                $idHash = $this->getImportIdHash($id);
                $record = $this->getRecordByImportIdHash($idHash, $language);

                if ($record === null) {
                    $this->logger->info(sprintf(
                        'Creating new record for table "%", with ID "%s"',
                        $this->dbTable,
                        $id
                    ));

                    $this->createNewEmptyRecord($id, $idHash, $language);

                    // Get new empty record
                    $record = $this->getRecordByImportIdHash($idHash, $language);
                    if ($record === null) {
                        // @codingStandardsIgnoreStart
                        throw new \RuntimeException('Error fetching new created record. This should never happen.', 1536063924811);
                        // @codingStandardsIgnoreEnd
                    }
                }

                $model = $this->mapRow($record);



                if ($model->getUid() === null) {
                    $this->repository->add($model);
                } elseif ($this->modelHasChanged($model)) {
                    $this->repository->update($model);
                }
            }
        }
    }

    /**
     * Create new empty record
     *
     * @param string $id
     * @param string $idHash
     * @param int $language
     */
    protected function createNewEmptyRecord(string $id, string $idHash, int $language): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->dbTable)
            ->insert(
                $this->dbTable,
                [
                    self::DB_IMPORT_ID_FIELD => $id,
                    self::DB_IMPORT_ID_HASH_FIELD => $idHash,
                    'sys_language_uid' => $language,
                    'pid' => $this->pid,
                    'title' => ''
                ],
                [
                    Connection::PARAM_STR,
                    Connection::PARAM_STR,
                    Connection::PARAM_INT,
                    Connection::PARAM_INT,
                    Connection::PARAM_STR
                ]
            );
    }
}
