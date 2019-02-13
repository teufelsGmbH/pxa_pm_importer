<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
     * Excel sheet
     *
     * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     */
    protected $worksheet = null;

    /**
     * Highest column for sheet
     *
     * @var int
     */
    protected $highestDataColumn = 0;

    /**
     * Highest row for sheet
     *
     * @var int
     */
    protected $highestDataRow = 0;

    /**
     * Current sheet row
     *
     * @var int
     */
    protected $currentRow = 1;

    /**
     * Rewind
     */
    public function rewind(): void
    {
        $this->currentRow = 1 + $this->skipRows;
    }

    /**
     * Check if reach end of sheet
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->currentRow <= $this->highestDataRow;
    }

    /**
     * Current row number
     *
     * @return int|mixed
     */
    public function key(): int
    {
        return $this->currentRow;
    }

    /**
     * Current sheet row as array
     *
     * @return array
     */
    public function current(): array
    {
        $row = [];
        for ($col = 1; $col <= $this->highestDataColumn; $col++) {
            $row[] = trim(
                (string)($this->worksheet->getCellByColumnAndRow($col, $this->currentRow)->getValue() ?? '')
            );
        }

        return $row;
    }

    /**
     * Next sheet row
     */
    public function next(): void
    {
        ++$this->currentRow;
    }

    /**
     * Number of rows
     *
     * @return int
     */
    public function count()
    {
        return $this->highestDataRow;
    }

    /**
     * Initialize
     *
     * @param array $configuration
     */
    public function initialize(array $configuration): void
    {
        parent::initialize($configuration);

        if (isset($configuration['sheet'])) {
            $this->sheet = (int)$configuration['sheet'];
        }
        if (!empty($configuration['skipRows'])) {
            $this->skipRows = (int)$configuration['skipRows'];
        }

        if ($this->isSourceFilePathValid()) {
            $spreadsheet = IOFactory::load($this->getAbsoluteFilePath());

            if ($this->sheet >= 0) {
                $this->worksheet = $spreadsheet->getSheet($this->sheet);
            } else {
                $this->worksheet = $spreadsheet->getActiveSheet();
            }

            // Garbage collect...
            $this->worksheet->garbageCollect();

            // Identify the range that we need to extract from the worksheet
            $this->highestDataColumn = Coordinate::columnIndexFromString($this->worksheet->getHighestDataColumn());
            $this->highestDataRow = $this->worksheet->getHighestDataRow();
        } else {
            throw new InvalidSourceFileException('Could not read data from source file "' . $this->filePath . '"');
        }
    }
}
