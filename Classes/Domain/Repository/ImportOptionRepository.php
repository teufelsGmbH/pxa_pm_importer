<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Repository;

use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package Pixelant\PxaPmImporter\Domain\Repository
 */
class ImportOptionRepository extends AbstractImportRepository
{
    /**
     * Find options by hashes and attribute UID
     *
     * @param array $hashes
     * @param int $attributeUid
     * @return array
     */
    public function findByHashesAttribute(array $hashes, int $attributeUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_pxaproductmanager_domain_model_option');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('*')
            ->from('tx_pxaproductmanager_domain_model_option')
            ->where(
                $queryBuilder->expr()->in(
                    ImporterInterface::DB_IMPORT_ID_HASH_FIELD,
                    $queryBuilder->createNamedParameter($hashes, Connection::PARAM_STR_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($this->context->getStoragePids(), Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'attribute',
                    $queryBuilder->createNamedParameter($attributeUid, Connection::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();

        return is_array($result) ? $result : [];
    }
}
