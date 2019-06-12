<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatusInterface;
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
     * Error messages
     *
     * @var array
     */
    protected $validationErrors = [];

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
     * Parent object
     *
     * @var ImporterInterface
     */
    protected $importer = null;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->logger = Logger::getInstance(get_class($this));
    }

    /**
     * Init
     *
     * @param AbstractEntity $entity
     * @param array $dbRow
     * @param string $property
     * @param ImporterInterface $importer
     * @param array $configuration
     */
    public function init(
        AbstractEntity $entity,
        array $dbRow,
        string $property,
        ImporterInterface $importer,
        array $configuration
    ): void {
        $this->entity = $entity;
        $this->dbRow = $dbRow;
        $this->property = $property;
        $this->importer = $importer;
        $this->configuration = $configuration;
    }

    /**
     * Pretty common for all fields
     *
     * @param mixed &$value
     */
    public function preProcess(&$value): void
    {
        if (is_string($value)) {
            $value = trim($value);
        }
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
                switch ($validator->getValidationStatus()->getSeverity()) {
                    case ValidationStatusInterface::WARNING:
                        $this->addError($validator->getValidationStatus()->getMessage());
                        // on warnings just return false
                        return false;
                        break;
                    case ValidationStatusInterface::ERROR:
                        throw new ErrorValidationException(
                            $validator->getValidationStatus()->getMessage(),
                            1550065854955
                        );
                        break;
                    case ValidationStatusInterface::CRITICAL:
                        throw new CriticalErrorValidationException(
                            $validator->getValidationStatus()->getMessage(),
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
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * @return string
     */
    public function getValidationErrorsString(): string
    {
        return '"' . implode('", "', $this->validationErrors) . '"';
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
        $this->validationErrors = [];
        $this->dbRow = ['uid' => $this->dbRow['uid']]; // Leave only UID for later re-init of processor
        $this->importer = null;
    }

    /**
     * Add validation error
     *
     * @param string $error
     */
    protected function addError(string $error): void
    {
        $this->validationErrors[] = sprintf(
            'Failed validation for property "%s", with message - "%s", [ID - "%s", hash - "%s"]',
            $this->property,
            $error,
            $this->dbRow[ImporterInterface::DB_IMPORT_ID_FIELD],
            $this->dbRow[ImporterInterface::DB_IMPORT_ID_HASH_FIELD]
        );
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
    protected function getRecordByImportIdentifier(string $identifier, string $table, int $language = 0): ?array
    {
        return MainUtility::getRecordByImportId($identifier, $table, $this->importer->getPid(), $language);
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
