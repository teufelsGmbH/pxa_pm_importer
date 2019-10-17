<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SourceFactory
 * @package Pixelant\PxaPmImporter\Service\Source
 */
class SourceFactory
{
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
        $sourceInstance = GeneralUtility::makeInstance($source);

        if (!$sourceInstance instanceof SourceInterface) {
            throw new \UnexpectedValueException(
                'Class "' . $source . '" must be instance of SourceInterface',
                1536044243356
            );
        }

        $sourceInstance->initialize($configuration ?? []);
        return $sourceInstance;
    }
}
