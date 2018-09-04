<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class AbstractFieldProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
abstract class AbstractFieldProcessor implements ImportFieldProcessorInterface
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
     * Error message
     *
     * @var string
     */
    protected $validationError = '';


    /**
     * Model that is currently populated
     *
     * @var AbstractEntity
     */
    protected $entity = null;

    /**
     * Init
     *
     * @param AbstractEntity $entity
     * @param string $property
     * @param array $configuration
     */
    public function init(AbstractEntity $entity, string $property, array $configuration): void
    {
        $this->entity = $entity;
        $this->property = $property;
        $this->configuration = $configuration;
    }

    /**
     * Pretty common for all fields
     *
     * @param mixed $value
     * @return mixed|string
     */
    public function preProcess($value)
    {
        return trim($value);
    }

    /**
     * Check if field is required
     *
     * @return bool
     */
    protected function isRequired()
    {
        return GeneralUtility::inList($this->configuration['validation'] ?? '', 'required');
    }

    /**
     * @return string
     */
    public function getValidationError(): string
    {
        return $this->validationError;
    }
}
