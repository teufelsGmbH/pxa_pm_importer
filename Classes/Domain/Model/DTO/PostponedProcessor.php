<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Model\DTO;

use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;

/**
 * Class PostponedProcessor
 * @package Pixelant\PxaPmImporter\Domain\Model\DTO
 */
class PostponedProcessor
{
    /**
     * @var FieldProcessorInterface
     */
    protected $processor = null;

    /**
     * Import value
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Initialize
     *
     * @param FieldProcessorInterface $processor
     * @param $value
     */
    public function __construct(FieldProcessorInterface $processor, $value)
    {
        $processor->tearDown();

        $this->processor = $processor;
        $this->value = $value;
    }

    /**
     * @return FieldProcessorInterface
     */
    public function getProcessor(): FieldProcessorInterface
    {
        return $this->processor;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
