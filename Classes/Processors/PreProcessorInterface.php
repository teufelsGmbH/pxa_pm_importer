<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

/**
 * @package Pixelant\PxaPmImporter\Processors
 */
interface PreProcessorInterface
{
    /**
     * Pre process import value
     *
     * @param mixed $value Import value
     * @return mixed New value
     */
    public function preProcess($value);
}
