<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        if ($this->isRequired() && empty($value)) {
            $this->addError('Property "' . $this->property . '" is required');

            return false;
        }

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
     * Check if field is required
     *
     * @return bool
     */
    protected function isRequired(): bool
    {
        return $this->isRuleInValidationList('required');
    }

    /**
     * Check if validation rule is in list
     *
     * @param string $rule
     * @return bool
     */
    protected function isRuleInValidationList(string $rule): bool
    {
        return GeneralUtility::inList($this->configuration['validation'] ?? '', $rule);
    }

    /**
     * Add validation error
     *
     * @param string $error
     */
    protected function addError(string $error): void
    {
        $this->validationErrors[] = $error;
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
}
