<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Traits;

use Pixelant\PxaPmImporter\Logging\Logger;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * Common function to work with files & storage
 *
 * @package Pixelant\PxaPmImporter\Processors\Traits
 */
trait FilesResources
{
    use EmitSignalTrait;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory = null;

    /**
     * Get resource factory instance
     *
     * @return ResourceFactory
     */
    protected function getResourceFactory(): ResourceFactory
    {
        if ($this->resourceFactory === null) {
            $this->resourceFactory = ResourceFactory::getInstance();
        }

        return $this->resourceFactory;
    }

    /**
     * Find storage
     *
     * @return ResourceStorage
     */
    protected function getStorage(): ResourceStorage
    {
        return $this->getResourceFactory()->getStorageObject((int)($this->configuration['storageUid'] ?? 1));
    }

    /**
     * Get folder from configuration or root folder from storage
     *
     * @return Folder
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    protected function getFolder(): Folder
    {
        $storage = $this->getStorage();

        return isset($this->configuration['folder'])
            ? $storage->getFolder($this->configuration['folder'])
            : $storage->getRootLevelFolder();
    }

    /**
     * Create new file reference
     *
     * @param File $file
     * @param int $uidForeign
     * @param int $pid
     * @param int $languageUid
     * @param string $fileReferenceClass
     * @return FileReference
     */
    protected function createFileReference(
        File $file,
        int $uidForeign,
        int $pid,
        int $languageUid = 0,
        string $fileReferenceClass = null
    ): FileReference {
        /** @var FileReference $fileReference */
        $fileReference = GeneralUtility::makeInstance($fileReferenceClass ?? FileReference::class);

        $newFileReferenceObject = $this->getResourceFactory()->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => $uidForeign,
                'uid' => uniqid('NEW_')
            ]
        );

        $fileReference->setOriginalResource($newFileReferenceObject);
        $fileReference->setPid($pid);
        $fileReference->_setProperty('_languageUid', $languageUid); // Extbase doesn't set this automatically

        return $fileReference;
    }

    /**
     * Get array of Files from path list
     *
     * @param Folder $folder
     * @param array $list
     * @param Logger|null $logger
     * @return File[]
     */
    protected function collectFilesFromList(Folder $folder, array $list, Logger $logger = null): array
    {
        $storage = $this->getStorage();

        /** @var File[] $files */
        $files = [];

        foreach ($list as $filePath) {
            $fileIdentifier = $folder->getIdentifier() . ltrim($filePath, '/');

            // Emit signal
            $this->emitSignal(__CLASS__, 'beforeImportFileGet', [$fileIdentifier, $this->configuration]);

            if ($storage->hasFile($fileIdentifier)) {
                $files[$filePath] = $storage->getFile($fileIdentifier);
            } elseif ($logger !== null) {
                $logger->error(sprintf(
                    'File "%s" doesn\'t exist',
                    $fileIdentifier
                ));
            }
        }

        return $files;
    }
}
