<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

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
     * @param string $property
     * @param array $configuration
     */
    public function init(AbstractEntity $entity, string $property, array $configuration): void;

    /**
     * Pre-process value
     *
     * @param mixed $value
     * @return mixed
     */
    public function preProcess($value);

    /**
     * Check if value is valid before import
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool;

    /**
     * Post process single import field
     * This should return compatible value for extbase model where import
     *
     * @param $value
     * @return mixed $value Value after processing
     */
    public function postProcess($value);
}
