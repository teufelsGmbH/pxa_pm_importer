<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

use Pixelant\PxaPmImporter\Exception\InvalidSourceFileException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CsvSource
 * @package Pixelant\PxaPmImporter\Service\Source
 */
class CsvSource extends AbstractFileSource
{
    /**
     * Default CSV delimiter
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * What rows to skip
     *
     * @var array
     */
    protected $skipRows = [];

    /**
     * Source data from file
     *
     * @var array
     */
    protected $sourceData = null;

    /**
     * Get data from CSV file as array
     *
     * @return array
     */
    public function getSourceData(): array
    {
        // If was set for previous importer
        if ($this->sourceData !== null) {
            return $this->sourceData;
        }

        if ($this->isSourceFilePathValid()) {
            $fileStream = (new \SplFileObject($this->getAbsoluteFilePath()));
            $sourceData = [];

            // Import
            while (!$fileStream->eof()) {
                $row = $fileStream->fgetcsv($this->delimiter);

                // Skip empty or ignored lines
                if ($this->isLineEmpty($row) || in_array($fileStream->key() + 1, $this->skipRows)) {
                    continue;
                }

                $sourceData[] = $row;
            }

            $this->sourceData = $sourceData;
            unset($sourceData);

            return $this->sourceData;
        }

        throw new InvalidSourceFileException('Could not read data from source file "' . $this->filePath . '"');
    }

    /**
     * Read CSV settings
     *
     * @param array $sourceSettings
     */
    protected function readSourceSettings(array $sourceSettings): void
    {
        if (!empty($sourceSettings['delimiter'])) {
            $this->delimiter = $sourceSettings['delimiter'];
        }
        if (!empty($sourceSettings['skipRows'])) {
            $this->skipRows = GeneralUtility::trimExplode(',', $sourceSettings['skipRows'], true);
        }

        $this->filePath = $sourceSettings['filePath'] ?? '';
    }

    /**
     * Check if line for CSV is empty
     *
     * @param array $data
     * @return bool
     */
    protected function isLineEmpty(array $data): bool
    {
        return ([null] === $data || implode('', $data) === '');
    }
}
