<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExcelSource
 * @package Pixelant\PxaPmImporter\Service\Source
 */
class ExcelSource extends AbstractFileSource
{
    /**
     * What rows to skip
     *
     * @var array
     */
    protected $skipRows = [];

    protected $sheet = 0;

    /**
     * Source raw data
     *
     * @var array
     */
    protected $sourceData = [];

    /**
     * Read settings
     *
     * @param array $sourceSettings
     */
    protected function readSourceSettings(array $sourceSettings): void
    {
        if (!empty($sourceSettings['sheet'])) {
            $this->sheet = (int)$sourceSettings['sheet'];
        }
        if (!empty($sourceSettings['skipRows'])) {
            $this->skipRows = GeneralUtility::trimExplode(',', $sourceSettings['skipRows'], true);
        }

        $this->filePath = $sourceSettings['filePath'] ?? '';
    }
}
