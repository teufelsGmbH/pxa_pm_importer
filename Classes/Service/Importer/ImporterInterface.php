<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

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
     * DB flag indicates if record was created as placeholder
     */
    const DB_IMPORT_PLACEHOLDER = 'pm_importer_placeholder';

    /**
     * Execute import
     *
     * @param SourceInterface $source
     * @param array $configuration
     */
    public function execute(SourceInterface $source, array $configuration): void;
}
