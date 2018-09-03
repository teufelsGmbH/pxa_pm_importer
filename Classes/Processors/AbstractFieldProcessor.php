<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    protected $fieldConfiguration = [];

    /**
     * Error message
     *
     * @var string
     */
    protected $validationError = '';

    /**
     * Initialize
     *
     * @param array $fieldConfiugration
     */
    public function __construct(array $fieldConfiugration)
    {
        $this->fieldConfiguration = $fieldConfiugration;
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
     * General validation rules
     *
     * @param $value
     * @param string $fieldName
     * @return bool
     */
    public function isValid($value, string $fieldName): bool
    {
        if ($this->isRequired() && empty($value)) {
            $this->validationError = sprintf(
                'Field "%s" is required',
                $fieldName
            );

            return false;
        }

        return true;
    }

    /**
     * Check if field is required
     *
     * @return bool
     */
    protected function isRequired()
    {
        return GeneralUtility::inList($this->fieldConfiguration['validation'] ?? '', 'required');
    }

    /**
     * @return string
     */
    public function getValidationError(): string
    {
        return $this->validationError;
    }
}
