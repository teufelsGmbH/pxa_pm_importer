<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

use Pixelant\PxaPmImporter\Exception\InvalidSourceFileException;

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
     * How many rows to skip
     *
     * @var int
     */
    protected $skipRows = 0;

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

            // Read
            while (!$fileStream->eof()) {
                $row = $fileStream->fgetcsv($this->delimiter);

                // Skip empty or ignored lines
                if ($this->isLineEmpty($row) || (($fileStream->key() + 1) <= $this->skipRows)) {
                    continue;
                }

                $sourceData[] = $row;
            }

            $this->emitSignal('sourceDataBeforeSet', [&$sourceData]);

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
            $this->skipRows = (int)$sourceSettings['skipRows'];
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
