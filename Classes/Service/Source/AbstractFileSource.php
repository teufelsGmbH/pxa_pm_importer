<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractFileSource
 * @package Pixelant\PxaPmImporter\Service\Source
 */
abstract class AbstractFileSource implements SourceInterface
{
    /**
     * Path to source file
     *
     * @var string
     */
    protected $filePath = '';

    /**
     * Initialize
     *
     * @param array $configuration
     */
    public function initialize(array $configuration): void
    {
        $this->filePath = $configuration['filePath'] ?? '';
    }

    /**
     * Check if file path is valid
     *
     * @return bool
     */
    protected function isSourceFilePathValid(): bool
    {
        $filePath = $this->getAbsoluteFilePath();

        return file_exists($filePath) && is_readable($filePath);
    }

    /**
     * Full path to file
     *
     * @return string
     */
    protected function getAbsoluteFilePath(): string
    {
        return GeneralUtility::getFileAbsFileName($this->filePath);
    }
}
