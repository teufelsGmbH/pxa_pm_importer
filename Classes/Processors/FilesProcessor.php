<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Command\ImportCommandController;
use Pixelant\PxaPmImporter\Domain\Repository\ProductRepository;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class FilesProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class FilesProcessor extends AbstractFieldProcessor
{
    /**
     * @var ResourceFactory
     */
    protected $resourceFactory = null;

    /**
     * @var ResourceStorage
     */
    protected $storage = null;

    /**
     * @var string
     */
    protected $storageFolder = 'uploads';

    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();

        $this->resourceFactory = ResourceFactory::getInstance();
        // File admin
        $this->storage = $this->resourceFactory->getStorageObject(1);
        $this->createFolder($this->storageFolder);
    }

    /**
     * Create folder if needed
     *
     * @param string $folder
     * @return void
     */
    protected function createFolder(string $folder): void
    {
        if (!$this->storage->hasFolder($folder)) {
            $folderPath = MainUtility::combinePathParts(
                true,
                false,
                PATH_site,
                $this->storage->getRootLevelFolder()->getPublicUrl(),
                MainUtility::sanitizePath($folder)
            );

            $this->logger->info('Create folder for files download "' . $folder . '"');
            GeneralUtility::mkdir_deep($folderPath);
        }
    }

    /**
     * Check if files exist. Also try to download it and check if there is newer
     *
     * @param $value
     * @param ImportCommandController $pObj
     * @param array $fieldConfiguration
     * @return bool
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    public function isValid($value, ImportCommandController $pObj, array $fieldConfiguration = []): bool
    {
        // @TODO if empty means remove all?
        if (!empty($value)) {
            $files = GeneralUtility::trimExplode(',', $value, true);

            foreach ($files as $file) {
                if (!$this->isFile($file)) {
                    $this->logger->error('Could not find "' . $file . '"');
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Connect file reference
     *
     * @param array $data
     * @param array $product
     * @param string $field
     * @param $value
     * @param ImportCommandController $pObj
     */
    public function process(array &$data, array &$product, string $field, $value, ImportCommandController $pObj): void
    {
        $productUid = (int)$product['uid'];
        $language = (int)($product['sys_language_uid'] ?? 0);

        $existingFilesUids = $this->getProductFilesUids(
            $productUid,
            $field,
            $language
        );
        $filesCountAtStart = count($existingFilesUids);

        // If it pass validation we should have file reference for each file
        $files = [];
        foreach (GeneralUtility::trimExplode(',', $value, true) as $filePath) {
            $localFilePath = $this->getLocalFilePath($filePath);

            if ($this->storage->hasFile($localFilePath)) {
                /** @var File $file */
                $file = $this->storage->getFile($localFilePath);
                $files[] = $file->getUid();
            }
        }

        $newFilesData = [];
        // Add missing files
        foreach ($files as $fileUid) {
            if (!in_array($fileUid, $existingFilesUids)) {
                $newFilesData[StringUtility::getUniqueId('NEW')] = [
                    'table_local' => 'sys_file',
                    'uid_local' => $fileUid,
                    'tablenames' => ProductRepository::TABLE_NAME,
                    'uid_foreign' => $productUid,
                    'sys_language_uid' => $language,
                    'fieldname' => $field,
                    'pid' => $pObj->getPid()
                ];
            }
        }

        if (!empty($newFilesData)) {
            if (!is_array($data['sys_file_reference'])) {
                $data['sys_file_reference'] = [];
            }
            $data['sys_file_reference'] = $data['sys_file_reference'] + $newFilesData;
            $product[$field] = implode(',', array_keys($newFilesData));

            // Mark as update
            $this->markProductAsChanged($product, $field);
        }

        $fileReferencesRemove = [];
        foreach ($existingFilesUids as $fileReferenceUid => $existingFilesUid) {
            if (!in_array($existingFilesUid, $files)) {
                $fileReferencesRemove[] = $fileReferenceUid;
            }
        }

        $this->removeFileReference($fileReferencesRemove);
        // Update count only if files were removed and not new uploaded
        // If new were added, data handler will update value automatically
        if (count($newFilesData) === 0 && !empty($fileReferencesRemove)) {
            $filesCountAtEnd = $filesCountAtStart - count($fileReferencesRemove);

            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(ProductRepository::TABLE_NAME)
                ->update(
                    ProductRepository::TABLE_NAME,
                    [$field => $filesCountAtEnd],
                    ['uid' => $productUid],
                    [Connection::PARAM_INT]
                );
        }
    }

    /**
     * Validate file
     *
     * @param string $file
     * @return bool
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    protected function isFile(string $file): bool
    {
        $localFilePath = $this->getLocalFilePath($file);

        if (!$this->storage->hasFile($localFilePath)
            || $this->isMamFileIsNewer($file, $this->storage->getFile($localFilePath))
        ) {
            $this->downloadFileFromMam($file, $localFilePath);
        }

        return $this->storage->hasFile($localFilePath);
    }

    /**
     * Get file path in file system
     *
     * @param string $filePath
     * @return string
     */
    protected function getLocalFilePath(string $filePath): string
    {
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        $normalizeFileName = MainUtility::normalizeFileName($fileName);

        $filePath = str_replace($fileName, $normalizeFileName, $filePath);

        return MainUtility::combinePathParts(
            false,
            true,
            $this->storageFolder,
            $filePath
        );
    }

    /**
     * Check if file on server is updated
     *
     * @param string $mamFilePath
     * @param FileInterface $localFile
     * @return bool
     */
    protected function isMamFileIsNewer(string $mamFilePath, FileInterface $localFile): bool
    {
        $path = 'GBG/gustavsberg/' . $mamFilePath;

        $args = [
            'sPath' => $path,
            'iTime' => $localFile->getModificationTime(),
        ];

        $requestOptions = [
            'connect_timeout' => 30,
            'http_errors' => false,
            'form_params' => $args,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ];

        /** @var RequestFactory $requestFactory */
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $response = $requestFactory->request(
            'https://cs.villeroy-boch.com/admin/rest/vbplugins/isdamfilenewer/',
            'POST',
            $requestOptions
        );

        if ($response->getStatusCode() === 200) {
            $result = json_decode((string)$response->getBody(), true);
        }

        $isNewer = isset($result) && $result['result'];

        $this->logger->info(sprintf(
            $isNewer ? 'New version of file "%s" found' : 'Use local version of file "%s"',
            $mamFilePath
        ));

        return $isNewer;
    }

    /**
     * Download file
     *
     * @param string $mamFilePath
     * @param string $localFilePath
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    protected function downloadFileFromMam(string $mamFilePath, string $localFilePath): void
    {
        $pi = pathinfo($mamFilePath);
        $localFolder = MainUtility::combinePathParts(
            false,
            false,
            $this->storageFolder,
            $pi['dirname']
        );
        $this->createFolder($localFolder);
        $url = sprintf(
            'https://cs.villeroy-boch.com/MAM/GBG/gustavsberg/%s/%s.%s',
            $pi['dirname'],
            rawurlencode($pi['filename']),
            $pi['extension']
        );

        $requestOptions = [
            'connect_timeout' => 30,
            'http_errors' => false
        ];

        /** @var RequestFactory $requestFactory */
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $response = $requestFactory->request(
            $url,
            'GET',
            $requestOptions
        );

        if ($response->getStatusCode() === 200) {
            $tempFile = GeneralUtility::tempnam('pxa_pim_products');
            file_put_contents($tempFile, $response->getBody());

            $this->storage->addFile(
                $tempFile,
                $this->storage->getFolder($localFolder),
                pathinfo($localFilePath, PATHINFO_BASENAME),
                DuplicationBehavior::REPLACE
            );

            unlink($tempFile);

            $this->logger->info('New file "' . $mamFilePath . '" downloaded from MAM', ['localPath' => $localFilePath]);
        } else {
            $this->logger->error('Error downloading file "' . $mamFilePath . '" from MAM', ['url' => $url]);
        }
    }

    /**
     * Fetch all file references for current product
     *
     * @param int $productUid
     * @param string $fieldName
     * @param int $sysLanguageUid
     * @return array File reference uid to file uid
     */
    protected function getProductFilesUids(int $productUid, string $fieldName, int $sysLanguageUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $statement = $queryBuilder
            ->select('uid', 'uid_local')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($productUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter($fieldName, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLanguageUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'table_local',
                    $queryBuilder->createNamedParameter('sys_file', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter(ProductRepository::TABLE_NAME, \PDO::PARAM_STR)
                )
            )
            ->execute();

        $result = [];

        while ($row = $statement->fetch()) {
            $result[$row['uid']] = $row['uid_local'];
        }

        return $result;
    }

    /**
     * Mark file reference as deleted
     *
     * @param array $fileUids
     * @return void
     */
    protected function removeFileReference(array $fileUids): void
    {
        if (empty($fileUids)) {
            return;
        }

        $deleteField = $GLOBALS['TCA']['sys_file_reference']['ctrl']['delete'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $queryBuilder
            ->update('sys_file_reference')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($fileUids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->set($deleteField, 1)
            ->execute();
    }
}
