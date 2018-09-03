<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

/**
 * Interface ImportFieldProcessorInterface
 * @package Pixelant\PxaPmImporter\Processors
 */
interface ImportFieldProcessorInterface
{
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
     * @param string $fieldName
     * @return bool
     */
    public function isValid($value, string $fieldName): bool;

    /**
     * Post process single import field
     * This should return compatible value for extbase model where import
     *
     * @param $value
     * @return mixed $value Value after processing
     */
    public function postProcess($value);
}
