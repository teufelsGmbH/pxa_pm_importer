<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Service\Importer\Builder\ImporterBuilderInterface;

/**
 * Class ImporterDirector
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
class ImporterDirector
{
    /**
     * Create importer using builder
     *
     * @param ImporterBuilderInterface $importerBuilder
     * @return ImporterInterface
     */
    public function build(ImporterBuilderInterface $importerBuilder): ImporterInterface
    {
        $importerBuilder->createImporter();
        $importerBuilder->addDatabaseTableName();
        $importerBuilder->addDefaultNewRecordFields();
        $importerBuilder->addRepository();
        $importerBuilder->addModelName();

        return $importerBuilder->getImporter();
    }
}
