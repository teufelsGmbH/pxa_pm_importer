<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation\Files;

use Pixelant\PxaPmImporter\Processors\Relation\AbstractRelationFieldProcessor;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class LocalFileProcessor
 * @package Pixelant\PxaPmImporter\Processors\File
 */
class LocalFileProcessor extends AbstractRelationFieldProcessor
{
    use EmitSignalTrait;

    /**
     * Flag if files preparations failed
     *
     * @var bool
     */
    protected $failedInit = false;

    /**
     * @var File[]
     */
    protected $entities = [];

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->resourceFactory = ResourceFactory::getInstance();
    }

    /**
     * Is files valid
     *
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if ($this->failedInit) {
            return false;
        }
        return parent::isValid($value);
    }

    /**
     * Set files
     *
     * @param $value
     */
    public function process($value): void
    {
        $currentValue = ObjectAccess::getProperty($this->entity, $this->property);
        $firstFile = $this->entities[0] ?? false;

        if ($currentValue === null && $firstFile) {
            ObjectAccess::setProperty($this->entity, $this->property, $this->createFileReference($firstFile));
            return;
        }

        if ($currentValue instanceof FileReference
            && $firstFile
            && $currentValue->getOriginalResource()->getOriginalFile()->getUid() !== $firstFile->getUid()
        ) {
            ObjectAccess::setProperty($this->entity, $this->property, $this->createFileReference($firstFile));
            return;
        }

        if ($currentValue instanceof ObjectStorage) {
            $filesUids = [];
            foreach ($this->entities as $file) {
                $filesUids[] = $file->getUid();
            }
            $attachedFilesUids = [];

            /** @var FileReference $fileReference */
            foreach ($currentValue->toArray() as $fileReference) {
                $originUid = $fileReference->getOriginalResource()->getOriginalFile()->getUid();
                if (!in_array($originUid, $filesUids)) {
                    $currentValue->detach($fileReference);
                } else {
                    $attachedFilesUids[] = $originUid;
                }
            }

            foreach ($this->entities as $file) {
                if (!in_array($file->getUid(), $attachedFilesUids)) {
                    $currentValue->attach($this->createFileReference($file));
                }
            }
        }
    }

    /**
     * Set all files
     *
     * @param mixed $value
     */
    public function initEntities($value): void
    {
        $storage = $this->resourceFactory->getStorageObject(intval($this->configuration['storageUid'] ?? 1));
        try {
            $folder = isset($this->configuration['folder'])
                ? $storage->getFolder($this->configuration['folder'])
                : $storage->getRootLevelFolder();
        } catch (FolderDoesNotExistException $exception) {
            $this->addError($exception->getMessage());
            $this->failedInit = true;

            return;
        }

        foreach (GeneralUtility::trimExplode(',', $value, true) as $fileName) {
            $fileIdentifier = $folder->getIdentifier() . $fileName;

            $this->emitSignal('beforeImportFileCheck' . __METHOD__, [$fileIdentifier]);

            try {
                $file = $storage->getFile($fileIdentifier);
                $this->entities[] = $file;
            } catch (FileDoesNotExistException $exception) {
                $this->addError($exception->getMessage());
                $this->failedInit = true;
            }
        }
    }

    /**
     * Create new file reference
     *
     * @param File $file
     * @return FileReference
     */
    protected function createFileReference(File $file): FileReference
    {
        $fileReference = GeneralUtility::makeInstance(FileReference::class);
        $newFileReferenceObject = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => $this->entity->getUid(),
                'uid' => uniqid('NEW_')
            ]
        );

        $fileReference->setOriginalResource($newFileReferenceObject);
        $fileReference->setPid($this->importer->getPid());

        return $fileReference;
    }
}
