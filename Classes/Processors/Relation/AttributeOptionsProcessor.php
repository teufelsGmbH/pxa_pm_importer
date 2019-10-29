<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Exception\FailedInitEntityException;
use Pixelant\PxaPmImporter\Processors\Traits\InitRelationEntities;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaProductManager\Domain\Model\Option;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AttributeOptionsProcessor
 * @package Pixelant\PxaPmImporter\Processors\Relation
 */
class AttributeOptionsProcessor extends AbstractRelationFieldProcessor
{
    use InitRelationEntities;

    /**
     * Set options
     *
     * @param mixed $value
     * @return array
     */
    public function initEntities($value): array
    {
        try {
            $entities = $this->initEntitiesForTable(
                $value,
                Option::class
            );
        } catch (FailedInitEntityException $exception) {
            $this->failedInit = true;
            $this->logger->error('Failed to create option with value "' . $exception->getIdentifier() . '"');
        }

        return $entities ?? [];
    }

    /**
     * If not found create one
     *
     * @param string $identifier
     */
    protected function createNewEntity(string $identifier): void
    {
        $time = time();
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_pxaproductmanager_domain_model_option')
            ->insert(
                'tx_pxaproductmanager_domain_model_option',
                [
                    'value' => $identifier,
                    'pid' => $this->context->getNewRecordsPid(),
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
    }
}
