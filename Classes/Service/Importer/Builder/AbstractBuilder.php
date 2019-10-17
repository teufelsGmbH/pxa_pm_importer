<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer\Builder;

use Pixelant\PxaPmImporter\Service\Importer\Importer;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AbstractBuilder
 * @package Pixelant\PxaPmImporter\Service\Importer\Builder
 */
abstract class AbstractBuilder implements ImporterBuilderInterface
{
    /**
     * @var Importer
     */
    protected $importer = null;

    /**
     * Could be override in builder
     *
     * @var string
     */
    protected $targetImporter = Importer::class;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * @param ObjectManager $manager
     */
    public function injectObjectManager(ObjectManager $manager)
    {
        $this->objectManager = $manager;
    }

    /**
     * Create importer
     */
    public function createImporter(): void
    {
        $this->importer = $this->objectManager->get($this->targetImporter);
    }

    /**
     * @return Importer
     */
    public function getImporter(): ImporterInterface
    {
        return $this->importer;
    }
}
