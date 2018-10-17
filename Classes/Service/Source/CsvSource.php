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

        if (!empty($configuration['delimiter'])) {
            $this->delimiter = $configuration['delimiter'];
        }
        if (!empty($configuration['skipRows'])) {
            $this->skipRows = (int)$configuration['skipRows'];
        }

        if ($this->isSourceFilePathValid()) {
            $this->fileStream = (new \SplFileObject($this->getAbsoluteFilePath()));
            $this->fileStream->setFlags(\SplFileObject::READ_CSV);
            $this->fileStream->setCsvControl($this->delimiter);
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
     *
     * @return array
     */
    public function current(): array
    {
        $current = $this->fileStream->current();

        return $current;
    }

    /**
     * Next file line
     */
    public function next(): void
    {
        $this->fileStream->next();
    }
}
