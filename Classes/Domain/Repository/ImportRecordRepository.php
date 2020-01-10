<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Repository;

use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\HashUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Find raw records by import identifiers.
 * Respect the import storage
 *
 * @package Pixelant\PxaPmImporter\Domain\Repository
 */
class ImportRecordRepository
{
    /**
     * @var ImportContext
     */
    protected $context = null;

    /**
     * @param ImportContext $context
     */
    public function __construct(ImportContext $context)
    {
        $this->context = $context;
    }


    /**
     * Fetch records from DB by import Identifier
     *
     * @param string $id
     * @param string $table
     * @param int $language
     * @return array|null
     */
    public function findByImportId(string $id, string $table, int $language = 0): ?array
    {
        $hash = HashUtility::hashImportId($id);

        return $this->findByImportIdHash($hash, $table, $language);
    }

    /**
     * Fetch records from DB by import hash
     *
     * @param string $hash
     * @param string $table
     * @param int $language
     * @return array|null
     */
    public function findByImportIdHash(string $hash, string $table, int $language = 0): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    ImporterInterface::DB_IMPORT_ID_HASH_FIELD,
                    $queryBuilder->createNamedParameter($hash, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($this->context->getStoragePids(), Connection::PARAM_INT_ARRAY)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        return is_array($row) ? $row : null;
    }
}
