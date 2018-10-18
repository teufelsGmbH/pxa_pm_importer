<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

use Pixelant\PxaPmImporter\Domain\Model\Import;

/**
 * Class SourceInterface
 * @package Pixelant\PxaPmImporter\Service\Source
 */
interface SourceInterface extends \Iterator
{
    /**
     * Initialize source
     *
     * @param array $configuration
     */
    public function initialize(array $configuration): void;
}
