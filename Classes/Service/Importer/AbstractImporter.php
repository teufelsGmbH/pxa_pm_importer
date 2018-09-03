<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Adapter\AdapterInterface;
use Pixelant\PxaPmImporter\Adapter\DefaultDataAdapter;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AbstractImporter
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
abstract class AbstractImporter implements ImporterInterface
{
    use EmitSignalTrait;

    /**
     * @var AdapterInterface
     */
    protected $adapter = null;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * Identifier column
     *
     * @var int
     */
    protected $identifierField = null;

    /**
     * Storage
     *
     * @var int
     */
    protected $pid = 0;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * Start import
     *
     * @param SourceInterface $source
     * @param Import $import
     * @param array $configuration
     */
    public function start(SourceInterface $source, Import $import, array $configuration = []): void
    {
        $this->initializeAdapter($source, $configuration);
        $this->determinateIdentifierField($configuration);
        $this->pid = (int)($configuration['pid'] ?? 0);
        die;
    }

    /**
     * Set identifier field
     *
     * @param array $configuration
     */
    protected function determinateIdentifierField(array $configuration)
    {
        $identifier = $configuration['identifierField'] ?? null;

        $this->emitSignal('determinateIdentifierField', [&$identifier]);

        if ($identifier === null) {
            // @codingStandardsIgnoreStart
            throw new \UnexpectedValueException('Identifier could not be null, check your import settings', 1535983109427);
            // @codingStandardsIgnoreEnd
        }

        $this->identifierField = $identifier;
    }

    /**
     * Initialize adapter
     *
     * @param SourceInterface $source
     * @param array $configuration
     */
    protected function initializeAdapter(SourceInterface $source, array $configuration): void
    {
        if (isset($configuration['adapter'])) {
            $adapter = GeneralUtility::makeInstance($configuration['adapter']);

            if (!($adapter instanceof AdapterInterface)) {
                // @codingStandardsIgnoreStart
                throw new \UnexpectedValueException('Adapter class "' . $configuration['adapter'] . '" must implement instance of AdapterInterface', 1535981100906);
                // @codingStandardsIgnoreEnd
            }

            $this->adapter = $adapter;
        } else {
            $this->adapter = GeneralUtility::makeInstance(DefaultDataAdapter::class);
        }

        $this->adapter->adapt($source->getSourceData(), $configuration[AdapterInterface::SETTINGS_FIELD] ?? []);
    }
}
