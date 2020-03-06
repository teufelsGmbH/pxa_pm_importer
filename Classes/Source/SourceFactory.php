<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Source;

use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class SourceFactory
 * @package Pixelant\PxaPmImporter\Source
 */
class SourceFactory
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
     * Create source for import
     *
     * @param string $source
     * @param array|null $configuration
     * @return SourceInterface
     */
    public function createSource(string $source, array $configuration = null): SourceInterface
    {
        /** @var SourceInterface $sourceInstance */
        $sourceInstance = $this->objectManager->get($source);

        if (!$sourceInstance instanceof SourceInterface) {
            throw new \InvalidArgumentException(
                'Class "' . $source . '" must implement SourceInterface',
                1536044243356
            );
        }

        $sourceInstance->initialize($configuration ?? []);

        return $sourceInstance;
    }
}
