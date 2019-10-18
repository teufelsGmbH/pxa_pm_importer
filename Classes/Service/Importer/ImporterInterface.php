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
     * Initialize importer
     *
     * @param SourceInterface $source
     * @param array $configuration
     * @return ImporterInterface
     */
    public function initialize(SourceInterface $source, array $configuration): self;

    /**
     * Execute import
     */
    public function execute(): void;
}
