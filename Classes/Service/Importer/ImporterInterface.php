<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Interface ImporterInterface
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
interface ImporterInterface
{
    /**
     * DB field name with identifier original value
     */
    const DB_IMPORT_ID_FIELD = 'pm_importer_import_id';

    /**
     * DB field name where import hash stored
     */
    const DB_IMPORT_ID_HASH_FIELD = 'pm_importer_import_id_hash';

    /**
     * Sets repository of import subject
     *
     * @param Repository $repository
     */
    public function setRepository(Repository $repository): void;

    /**
     * Sets model name of import subject
     *
     * @param string $model
     */
    public function setModelName(string $model): void;

    /**
     * Set table name of import subject
     *
     * @param string $table
     */
    public function setDatabaseTableName(string $table): void;

    /**
     * Sets default fields of new created record
     * Example:
     * [
     *    'values' => ['title' => ''],
     *    'types' => [\PDO::PARAM_STR]
     * ]
     *
     * @param array $fields
     */
    public function setDefaultNewRecordFields(array $fields): void;

    /**
     * Before importer started
     *
     */
    public function preImport(): void;

    /**
     * Start import
     *
     * @param SourceInterface $source
     * @param array $configuration
     */
    public function start(SourceInterface $source, array $configuration): void;

    /**
     * After importer finish job
     *
     * @return void
     */
    public function postImport(): void;
}
