<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer\Builder;

use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;

/**
 * Interface ImporterBuilderInterface
 * @package Pixelant\PxaPmImporter\Service\Importer\Builder
 */
interface ImporterBuilderInterface
{
    /**
     * Create importer instance
     */
    public function createImporter(): void;

    /**
     * Add repository of import subject
     */
    public function addRepository(): void;

    /**
     * Add model name of import subject
     */
    public function addModelName(): void;

    /**
     * Add table name of import subject
     */
    public function addDatabaseTableName(): void;

    /**
     * Add default fields of new created record
     */
    public function addDefaultNewRecordFields(): void;

    /**
     * Get importer
     *
     * @return ImporterInterface
     */
    public function getImporter(): ImporterInterface;
}
