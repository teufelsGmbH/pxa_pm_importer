<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Interface ImportFieldProcessorInterface
 * @package Pixelant\PxaPmImporter\Processors
 */
interface ImportFieldProcessorInterface
{
    /**
     * Init processor
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
    ): void;

    /**
     * Pre-process value, before validation
     *
     * @param mixed &$value
     */
    public function preProcess(&$value): void;

    /**
     * Check if value is valid before import
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool;

    /**
     * Process single import field
     * This method is called to set value in model property and do any other required operations
     *
     * @param $value
     */
    public function process($value): void;

    /**
     * Return validation errors
     *
     * @return string
     */
    public function getValidationErrorsString(): string;
}
