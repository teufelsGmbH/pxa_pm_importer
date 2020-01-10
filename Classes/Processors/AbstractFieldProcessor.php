<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRecordRepository;
use Pixelant\PxaPmImporter\Logging\Logger;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class AbstractFieldProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
abstract class AbstractFieldProcessor implements FieldProcessorInterface
{
    /**
     * Field processing configuration
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * Current property
     *
     * @var string
     */
    protected $property = '';

    /**
     * Model that is currently populated
     *
     * @var AbstractEntity
     */
    protected $entity = null;

    /**
     * Original entity DB row
     *
     * @var array
     */
    protected $dbRow = [];

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var ImportContext
     */
    protected $context = null;

    /**
     * @var ImportRecordRepository
     */
    protected $repository = null;

    /**
     * Initialize
     */
    public function initializeObject()
    {
        $this->logger = Logger::getInstance(get_class($this));
    }

    /**
     * @param ImportContext $importContext
     */
    public function injectImportContext(ImportContext $importContext)
    {
        $this->context = $importContext;
    }

    /**
     * @param ImportRecordRepository $repository
     */
    public function injectImportRecordRepository(ImportRecordRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Init
     *
     * @param AbstractEntity $entity
     * @param array $dbRow
     * @param string $property
     * @param array $configuration
     */
    public function init(
        AbstractEntity $entity,
        array $dbRow,
        string $property,
        array $configuration
    ): void {
        $this->entity = $entity;
        $this->dbRow = $dbRow;
        $this->property = $property;
        $this->configuration = $configuration;
    }

    /**
     * Sets value
     *
     * @param $value
     */
    public function process($value): void
    {
        $this->simplePropertySet($value);
    }

    /**
     * Set entity properties like strings, numbers, etc..
     *
     * @param $value
     */
    protected function simplePropertySet($value)
    {
        // Setter for simple values
        $currentValue = ObjectAccess::getProperty($this->entity, $this->property);
        if ($currentValue !== $value) {
            ObjectAccess::setProperty($this->entity, $this->property, $value);
        }
    }

    /**
     * Fetch record by import ID hash
     *
     * @param string $identifier
     * @param string $table
     * @param int $language
     * @return array|null
     */
    protected function findRecordByImportIdentifier(string $identifier, string $table, int $language = 0): ?array
    {
        return $this->repository->findByImportId($identifier, $table, $language);
    }
}
