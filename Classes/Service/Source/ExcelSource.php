<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Pixelant\PxaPmImporter\Exception\InvalidSourceFileException;

/**
 * Class ExcelSource
 * @package Pixelant\PxaPmImporter\Service\Source
 */
class ExcelSource extends AbstractFileSource
{
    /**
     * How many rows to skip
     *
     * @var int
     */
    protected $skipRows = 0;

    /**
     * Sheet number to fetch
     * -1 = Active sheet
     *
     * @var int
     */
    protected $sheet = -1;

    /**
     * Source raw data
     *
     * @var array
     */
    protected $sourceData = null;

    /**
     * Read excel data
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
            $spreadsheet = IOFactory::load($this->getAbsoluteFilePath());
            if ($this->sheet >= 0) {
                $worksheet = $spreadsheet->getSheet($this->sheet);
            } else {
                $worksheet = $spreadsheet->getActiveSheet();
            }

            // Garbage collect...
            $worksheet->garbageCollect();

            // Identify the range that we need to extract from the worksheet
            $maxCol = $worksheet->getHighestDataColumn();
            $maxRow = $worksheet->getHighestDataRow();

            $sourceData = $worksheet->rangeToArray('A1:' . $maxCol . $maxRow, '');
            if ($this->skipRows > 0) {
                $sourceData = array_slice($sourceData, $this->skipRows);
            }

            $this->emitSignal('sourceDataBeforeSet', [&$sourceData]);

            $this->sourceData = $sourceData;
            unset($sourceData);

            return $this->sourceData;
        }

        throw new InvalidSourceFileException('Could not read data from source file "' . $this->filePath . '"');
    }

    /**
     * Read settings
     *
     * @param array $sourceSettings
     */
    protected function readSourceSettings(array $sourceSettings): void
    {
        if (isset($sourceSettings['sheet'])) {
            $this->sheet = (int)$sourceSettings['sheet'];
        }
        if (!empty($sourceSettings['skipRows'])) {
            $this->skipRows = (int)$sourceSettings['skipRows'];
        }

        $this->filePath = $sourceSettings['filePath'] ?? '';
    }
}
