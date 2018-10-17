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
     *
     * @var bool
     */
    protected $failedCreateOptions = false;

    /**
     * Set options
     *
     * @param mixed $value
     */
    public function initEntities($value): void
    {
        $this->entities = []; // Reset
        $value = GeneralUtility::trimExplode(',', $value, true);

        foreach ($value as $identifier) {
            if (true === (bool)($this->configuration['treatIdentifierAsUid'] ?? false)) {
                $record = BackendUtility::getRecord('tx_pxaproductmanager_domain_model_option', (int)$identifier);
            } else {
                $record = $this->getRecordByImportIdentifier($identifier, 'tx_pxaproductmanager_domain_model_option'); // Default language record
                if ($record === null) {
                    $time = time();
                    // If not found create one
                    GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable('tx_pxaproductmanager_domain_model_option')
                        ->insert(
                            'tx_pxaproductmanager_domain_model_option',
                            [
                                'value' => $identifier,
                                'pid' => $this->importer->getPid(),
                                'sys_language_uid' => 0,
                                'attribute' => $this->entity->getUid(),
                                ImporterInterface::DB_IMPORT_ID_FIELD => $identifier,
                                ImporterInterface::DB_IMPORT_ID_HASH_FIELD => MainUtility::getImportIdHash($identifier),
                                'tstamp' => $time,
                                'crdate' => $time,
                            ],
                            [
                                \PDO::PARAM_STR,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_STR,
                                \PDO::PARAM_STR,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT
                            ]
                        );
                    $record = $this->getRecordByImportIdentifier($identifier, 'tx_pxaproductmanager_domain_model_option'); // Try again
                }
            }

            if ($record !== null) {
                $model = MainUtility::convertRecordArrayToModel($record, Option::class);
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
}
