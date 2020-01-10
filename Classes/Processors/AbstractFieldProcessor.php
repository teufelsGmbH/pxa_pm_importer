<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Domain\Repository\ImportRecordRepository;
use Pixelant\PxaPmImporter\Domain\Validation\Validator\ProcessorFieldValueValidatorInterface;
use Pixelant\PxaPmImporter\Domain\Validation\Validator\ValidatorFactory;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\CriticalErrorValidationException;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\ErrorValidationException;
use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaPmImporter\Utility\MainUtility;
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
     * If something is need to be done before process, override in child class
     *
     * @param mixed &$value
     */
    public function preProcess(&$value): void
    {
    }

    /**
     * Check if required
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $validators = isset($this->configuration['validation']) && is_array($this->configuration['validation'])
            ? $this->configuration['validation']
            : [];

        foreach ($validators as $validatorName) {
            $validator = $this->resolveValidator($validatorName);

            // Failed validation
            if (!$validator->validate($value, $this)) {
                switch ($validator->getSeverity()) {
                    case ProcessorFieldValueValidatorInterface::WARNING:
                        $this->logger->error(sprintf(
                            'Error mapping property. Skipping property. [ID-"%s", UID-"%s", PROP-"%s", REASON-"%s"].',
                            $this->dbRow[ImporterInterface::DB_IMPORT_ID_FIELD],
                            $this->dbRow['uid'],
                            $this->property,
                            $validator->getValidationError()
                        ));

                        // on warnings just return false
                        return false;
                        break;
                    case ProcessorFieldValueValidatorInterface::ERROR:
                        throw new ErrorValidationException(
                            $validator->getValidationError(),
                            1550065854955
                        );
                        break;
                    case ProcessorFieldValueValidatorInterface::CRITICAL:
                        throw new CriticalErrorValidationException(
                            $validator->getValidationError(),
                            1550065854955
                        );
                        break;
                }
            }
        }

        // All passed
        return true;
    }

    /**
     * @return AbstractEntity
     */
    public function getProcessingEntity(): AbstractEntity
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getProcessingDbRow(): array
    {
        return $this->dbRow;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function getProcessingProperty(): string
    {
        return $this->property;
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        $this->entity = null;
        $this->dbRow = ['uid' => $this->dbRow['uid']]; // Leave only UID for later re-init of processor
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
        return $this->repository->findByImportIdHash($identifier, $table, $language);
    }

    /**
     * Wrapper for testing
     *
     * @param string $validator
     * @return ProcessorFieldValueValidatorInterface
     */
    protected function resolveValidator(string $validator): ProcessorFieldValueValidatorInterface
    {
        return ValidatorFactory::factory($validator);
    }
}
