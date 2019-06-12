<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Traits;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Processors\Traits\FilesResources;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Class FilesResourcesTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Traits
 */
class FilesResourcesTest extends UnitTestCase
{
    use FilesResources;

    /**
     * @test
     */
    public function getResourceFactoryReturnsResourceFactoryAndSetItAsProperty()
    {
        $this->assertNull($this->resourceFactory);

        $resourceFactory = $this->getResourceFactory();

        $this->assertInstanceOf(ResourceFactory::class, $resourceFactory);
        $this->assertSame($resourceFactory, $this->resourceFactory);
    }

    /**
     * @test
     */
    public function getStorageUseDefaultStorageUidIfNotProvidedByConfiguration()
    {
        $resourceStorageMocked = $this->createMock(ResourceStorage::class);

        $resourceFactoryMocked = $this
            ->getMockBuilder(ResourceFactory::class)
            ->setMethods(['getStorageObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $resourceFactoryMocked
            ->expects($this->once())
            ->method('getStorageObject')
            ->with(1)
            ->willReturn($resourceStorageMocked);
        $this->resourceFactory = $resourceFactoryMocked;

        $this->getStorage();
    }

    /**
     * @test
     */
    public function getStorageUseConfigurationUidIfProvidedByConfiguration()
    {
        $resourceStorageMocked = $this->createMock(ResourceStorage::class);

        $resourceFactoryMocked = $this
            ->getMockBuilder(ResourceFactory::class)
            ->setMethods(['getStorageObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $resourceFactoryMocked
            ->expects($this->once())
            ->method('getStorageObject')
            ->with(1999)
            ->willReturn($resourceStorageMocked);
        $this->resourceFactory = $resourceFactoryMocked;

        $this->configuration = [
            'storageUid' => 1999
        ];
        $this->getStorage();
    }

    /**
     * @test
     */
    public function getFolderReturnRootFolderIfNotFolderProvidedByConfiguration()
    {
        $resourceStorageMocked = $this->createPartialMock(ResourceStorage::class, ['getRootLevelFolder']);
        $resourceStorageMocked
            ->expects($this->once())
            ->method('getRootLevelFolder')
            ->willReturn($this->createMock(Folder::class));

        $resourceFactoryMocked = $this
            ->getMockBuilder(ResourceFactory::class)
            ->setMethods(['getStorageObject'])
            ->disableOriginalConstructor()
            ->getMock();

        $resourceFactoryMocked
            ->expects($this->once())
            ->method('getStorageObject')
            ->willReturn($resourceStorageMocked);
        $this->resourceFactory = $resourceFactoryMocked;

        $this->getFolder();
    }

    /**
     * @test
     */
    public function getFolderReturnFolderProvidedByConfiguration()
    {
        $folder = 'uploads/';

        $resourceStorageMocked = $this->createPartialMock(ResourceStorage::class, ['getFolder']);
        $resourceStorageMocked
            ->expects($this->once())
            ->method('getFolder')
            ->with($folder)
            ->willReturn($this->createMock(Folder::class));

        $resourceFactoryMocked = $this
            ->getMockBuilder(ResourceFactory::class)
            ->setMethods(['getStorageObject'])
            ->disableOriginalConstructor()
            ->getMock();

        $resourceFactoryMocked
            ->expects($this->once())
            ->method('getStorageObject')
            ->willReturn($resourceStorageMocked);
        $this->resourceFactory = $resourceFactoryMocked;

        $this->configuration=[
            'folder' => $folder
        ];
        $this->getFolder();
    }

    /**
     * @test
     */
    public function convertFilesListValueToArrayThrowExceptionIfNonArrayOrNonStringGiven()
    {
        $list = 123;

        $this->expectException(\InvalidArgumentException::class);

        $this->convertFilesListValueToArray($list);
    }

    /**
     * @test
     */
    public function convertFilesListValueToArrayReturnGivenArray()
    {
        $list = ['path1', 'path2'];

        $this->assertEquals($list, $this->convertFilesListValueToArray($list));
    }

    /**
     * @test
     */
    public function convertFilesListValueToArrayConvertStringToArray()
    {
        $list = 'path1,path2, path3';
        $expect = ['path1', 'path2', 'path3'];

        $this->assertEquals($expect, $this->convertFilesListValueToArray($list));
    }
}
