<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractFileSource
 * @package Pixelant\PxaPmImporter\Service\Source
 */
abstract class AbstractFileSource implements SourceInterface
{
    use EmitSignalTrait;

    /**
     * Path to source file
     *
     * @var string
     */
    protected $filePath = '';

    /**
     * @param array $configuration
     */
    public function initialize(array $configuration): void
    {
        $this->emitSignal('beforeReadSourceSettings', [&$configuration]);
        $this->readSourceSettings($configuration);
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

    abstract protected function readSourceSettings(array $sourceSettings): void;
}
