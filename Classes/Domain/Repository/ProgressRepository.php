<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ProgressRepository
 * @package Pixelant\PxaPmImporter\Domain\Repository
 */
class ProgressRepository
{
    /**
     * Find progress status by configuration
     *
     * @param string $configuration
     * @return array|null
     */
    public function findByConfiguration(string $configuration): ?array
    {
        $resultRow = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_pxapmimporter_domain_model_progress')
            ->select(
                ['*'],
                'tx_pxapmimporter_domain_model_progress',
                ['configuration' => $configuration]
            )
            ->fetch();

        return is_array($resultRow) ? $resultRow : null;
    }

    /**
     * Find all running imports progress
     */
    public function findAll()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_pxapmimporter_domain_model_progress')
            ->select(
                ['*'],
                'tx_pxapmimporter_domain_model_progress'
            )
            ->fetchAll();
    }

    /**
     * Create progress record for configuration and return UID
     *
     * @param string $configuration
     * @return int
     */
    public function addWithConfiguration(string $configuration): int
    {
        $time = time();

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnection = $connectionPool->getConnectionForTable('tx_pxapmimporter_domain_model_progress');
        $databaseConnection->insert(
            'tx_pxapmimporter_domain_model_progress',
            [
                'crdate' => $time,
                'tstamp' => $time,
                'configuration' => $configuration,
                'progress' => 0.0,
            ],
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
            ]
        );

        return (int)$databaseConnection->lastInsertId('tx_pxapmimporter_domain_model_progress');
    }

    /**
     * Update progress
     *
     * @param int $uid
     * @param float $progress
     */
    public function updateProgress(int $uid, float $progress): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_pxapmimporter_domain_model_progress')
            ->update(
                'tx_pxapmimporter_domain_model_progress',
                ['progress' => $progress],
                ['uid' => $uid],
                [\PDO::PARAM_STR]
            );
    }

    /**
     * Delete progress
     *
     * @param int $uid
     */
    public function deleteProgress(int $uid): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_pxapmimporter_domain_model_progress')
            ->delete(
                'tx_pxapmimporter_domain_model_progress',
                ['uid' => $uid],
                [\PDO::PARAM_INT]
            );
    }
}
