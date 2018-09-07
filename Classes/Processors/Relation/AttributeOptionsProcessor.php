<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaProductManager\Domain\Model\Option;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class AttributeOptionsProcessor
 * @package Pixelant\PxaPmImporter\Processors\Relation
 */
class AttributeOptionsProcessor extends AbstractRelationFieldProcessor
{
    /**
     * Flag if validation should fail
     * @var bool
     */
    protected $failedCreateOptions = false;

    /**
     * Check if category exist
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        if (!is_string($value)) {
            $value = trim((string)$value);
        }

        $this->entities = []; // Reset categories
        $value = GeneralUtility::trimExplode(',', $value, true);

        foreach ($value as $identifier) {
            if (true === (bool)$this->configuration['treatAsIdentifierAsUid']) {
                $record = BackendUtility::getRecord($this->getDbTable(), (int)$identifier);
            } else {
                $record = $this->getRecord($identifier); // Default language record
                if ($record === null) {
                    // If not found create one
                    GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable($this->getDbTable())
                        ->insert(
                            $this->getDbTable(),
                            [
                                'value' => $identifier,
                                'pid' => $this->importer->getPid(),
                                'sys_language_uid' => 0,
                                'attribute' => $this->entity->getUid(),
                                ImporterInterface::DB_IMPORT_ID_FIELD => $identifier,
                                ImporterInterface::DB_IMPORT_ID_HASH_FIELD => MainUtility::getImportIdHash($identifier)
                            ],
                            [
                                \PDO::PARAM_STR,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_STR,
                                \PDO::PARAM_STR
                            ]
                        );
                    $record = $this->getRecord($identifier); // Try again
                }
            }

            if ($record !== null) {
                $model = MainUtility::convertRecordArrayToModel($record, $this->getModelClassName());
            }

            if (isset($model) && is_object($model)) {
                $this->entities[] = $model;
            } else {
                $this->failedCreateOptions = true;
                $this->addError('Failed to create option with value "' . $identifier . '"');
            }
        }
    }

    /**
     * Validation
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if ($this->failedCreateOptions) {
            return false;
        }

        return parent::isValid($value);
    }

    /**
     * Table
     *
     * @return string
     */
    protected function getDbTable(): string
    {
        return 'tx_pxaproductmanager_domain_model_option';
    }


    /**
     * @return string
     */
    protected function getModelClassName(): string
    {
        return Option::class;
    }

    /**
     * @return Repository
     */
    protected function getRepository(): Repository
    {
        throw new \RuntimeException('Options repository doesn\'t exists', 1536319446223);
    }
}
