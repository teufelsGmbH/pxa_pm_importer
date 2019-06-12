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
     * @param string $fileReferenceClass
     * @return FileReference
     */
    protected function createFileReference(
        File $file,
        int $uidForeign,
        int $pid,
        string $fileReferenceClass = null
    ): FileReference {
        $fileReference = GeneralUtility::makeInstance($fileReferenceClass ?? FileReference::class);

        $newFileReferenceObject = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => $uidForeign,
                'uid' => uniqid('NEW_')
            ]
        );

        $fileReference->setOriginalResource($newFileReferenceObject);
        $fileReference->setPid($pid);

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
            $this->emitSignal('beforeImportFileGet', [$fileIdentifier, $this->configuration]);

            if ($storage->hasFile($fileIdentifier)) {
                $files[] = $storage->getFile($fileIdentifier);
            } elseif ($logger !== null) {
                $logger->error(sprintf(
                    'File "%s" doesn\'t exist',
                    $fileIdentifier
                ));
            }
        }

        return $files;
    }

    /**
     * Convert to array if list is string.
     *
     * @param array|string $list Only array or string comma separated list is allowed as files list
     * @return array
     */
    protected function convertFilesListValueToArray($list): array
    {
        if (!is_array($list) && !is_string($list)) {
            $type = gettype($list);
            throw new \InvalidArgumentException(
                "Expect to get array or string as files list. '{$type}' given.",
                1560319588819
            );
        }
        if (is_string($list)) {
            $list = GeneralUtility::trimExplode(',', $list, true);
        }

        return $list;
    }
}
