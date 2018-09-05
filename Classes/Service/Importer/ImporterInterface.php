<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;

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
     * Before import starts
     *
     * @param SourceInterface $source
     * @param Import $import
     * @param array $configuration
     */
    public function preImport(SourceInterface $source, Import $import, array $configuration = []): void;

    /**
     * Start import
     *
     * @param SourceInterface $source
     * @param Import $import
     * @param array $configuration
     */
    public function start(SourceInterface $source, Import $import, array $configuration = []): void;

    /**
     * After import
     *
     * @param Import $import
     * @return void
     */
    public function postImport(Import $import): void;

    /**
     * Return storage
     *
     * @return int
     */
    public function getPid(): int;
}
