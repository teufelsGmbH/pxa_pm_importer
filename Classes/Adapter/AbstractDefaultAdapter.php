<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;

use Pixelant\PxaPmImporter\Exception\InvalidAdapterFieldMapping;

/**
 * Class AbstractDefaultAdapter
 * @package Pixelant\PxaPmImporter\Adapter
 */
abstract class AbstractDefaultAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected static $alphabet = [
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
        'Z'
    ];

    /**
     * Adapted data for all lanugages
     *
     * @var array
     */
    protected $data = [];

    /**
     * Identifier column
     *
     * @var int
     */
    protected $identifier = null;

    /**
     * Mapping configuration for languages
     *
     * @var null
     */
    protected $languagesMapping = null;

    /**
     * Adapt source data
     *
     * @param array $data
     * @param array $configuration
     */
    public function adapt(array $data, array $configuration): void
    {
        $this->initialize($configuration);
        $this->data = $this->adaptSourceData($data);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getLanguages(): array
    {
        return array_keys($this->languagesMapping);
    }

    /**
     * @param int $languageUid
     * @return array
     */
    public function getLanguageData(int $languageUid): array
    {
        if (isset($this->data[$languageUid])) {
            return $this->data[$languageUid];
        }

        // @codingStandardsIgnoreStart
        throw new \UnexpectedValueException('Language with uid "' . $languageUid . '" doesn\'t have data in data adapter', 1536051135215);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Initialize default settings
     *
     * @param array $configuration
     */
    protected function initialize(array $configuration): void
    {
        if (empty($configuration['mapping'])) {
            throw new \RuntimeException('Adapter mapping configuration is invalid.', 1536050678725);
        }

        if (isset($configuration['mapping']['id'])) {
            if (is_numeric($configuration['mapping']['id'])) {
                $this->identifier = (int)$configuration['mapping']['id'];
            } else {
                $this->identifier = $this->convertAlphabetColumnToNumber($configuration['mapping']['id']);
            }

        } else {
            throw new \RuntimeException('Adapter mapping require "id" (identifier) mapping to be set.', 1536050717594);
        }

        if (!empty($configuration['mapping']['languages']) && is_array($configuration['mapping']['languages'])) {
            $this->languagesMapping = $configuration['mapping']['languages'];

            foreach ($this->languagesMapping as $language => $languageMapping) {
                foreach ($languageMapping as $field => $column) {
                    if (!is_numeric($column)) {
                        $columnNumber = $this->convertAlphabetColumnToNumber($column);
                        $this->languagesMapping[$language][$field] = $columnNumber;
                    }
                }
            }
        } else {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException('Adapter mapping require at least one language mapping configuration.', 1536050795179);
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * Get single field data from row
     *
     * @param int $column
     * @param array $row
     * @return mixed
     */
    protected function getFieldData(int $column, array $row)
    {
        if (isset($row[$column])) {
            return $row[$column];
        }

        throw new InvalidAdapterFieldMapping('Data column "' . $column . '" is not set', 1536051927592);
    }

    /**
     * Convert A to 0, B to 1 and so on
     *
     * @param string $column
     * @return int
     */
    public function convertAlphabetColumnToNumber(string $column): int
    {
        $column = trim($column);

        if (empty($column)) {
            throw new \UnexpectedValueException('Column value could not be empty', 1536221838124);
        }
        $length = strlen($column);
        if ($length > 2) {
            throw new \LengthException('Maximum column value can be 2 chars', 1536221841673);
        }

        if ($length === 1) {
            return array_search(strtoupper($column), self::$alphabet);
        } else {
            $firstValue = (array_search(strtoupper($column[0]), self::$alphabet) + 1) * count(self::$alphabet);
            return $firstValue + array_search(strtoupper($column[1]), self::$alphabet);
        }
    }

    /**
     * Convert source data according to mapping
     *
     * @param array $data
     * @return array
     */
    abstract protected function adaptSourceData(array $data): array;
}
