<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ImporterDirector
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
class ImporterDirector
{
    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create importer using builder
     *
     * @param array $configuration
     * @return ImporterInterface
     */
    public function build(array $configuration): ImporterInterface
    {
        $targetClass = $configuration['importer'] ?? Importer::class;
        $importer = $this->objectManager->get($targetClass);

        if (!$importer instanceof ImporterInterface) {
            throw new \InvalidArgumentException(
                "Class $targetClass must implement ImporterInterface",
                1571377673766
            );
        }

        return $importer;
    }
}
