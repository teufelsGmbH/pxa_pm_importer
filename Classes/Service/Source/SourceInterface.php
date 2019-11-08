<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

/**
 * Class SourceInterface
 * @package Pixelant\PxaPmImporter\Service\Source
 */
interface SourceInterface extends \Iterator, \Countable
{
    /**
     * Initialize source
     *
     * @param array $configuration
     */
    public function initialize(array $configuration): void;
}
