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
     * Source csv file stream
     *
     * @var \SplFileObject
     */
    protected $fileStream = null;

    /**
     * Initialize
     *
     * @param array $configuration
     */
    public function initialize(array $configuration): void
    {
        parent::initialize($configuration);

        if (!empty($sourceSettings['delimiter'])) {
            $this->delimiter = $sourceSettings['delimiter'];
        }
        if (!empty($sourceSettings['skipRows'])) {
            $this->skipRows = (int)$sourceSettings['skipRows'];
        }

        if ($this->isSourceFilePathValid()) {
            $this->fileStream = (new \SplFileObject($this->getAbsoluteFilePath()));
        } else {
            throw new InvalidSourceFileException('Could not read data from source file "' . $this->filePath . '"');
        }
    }

    /**
     * Rewind CSV source
     */
    public function rewind(): void
    {
        $this->fileStream->rewind();
        if ($this->skipRows > 0) {
            $this->fileStream->seek($this->skipRows);
        }
    }

    /**
     * Is end of file
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->fileStream->valid();
    }

    /**
     * Current key
     *
     * @return int|mixed
     */
    public function key()
    {
        return $this->fileStream->key();
    }

    /**
     * Current CSV line as array
     * @return mixed|void
     */
    public function current(): array
    {
        $current = $this->fileStream->current();

        str_getcsv($current, $this->delimiter);
    }

    public function next(): void
    {
        $this->fileStream->next();
    }
}
