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
     * Start import
     *
     * @param SourceInterface $source
     * @param Import $import
     * @param array $configuration
     */
    public function start(SourceInterface $source, Import $import, array $configuration = []): void;
}
