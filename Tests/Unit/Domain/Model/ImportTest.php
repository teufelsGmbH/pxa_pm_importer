<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Domain\Model;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Service\Configuration\ConfigurationInterface;
use Pixelant\PxaPmImporter\Service\Configuration\YamlConfiguration;

/**
 * Class ImportTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Domain\Model
 */
class ImportTest extends UnitTestCase
{
    /**
     * @var Import|MockObject
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->createPartialMock(Import::class, ['getConfigurationInstance']);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function defaultNameEmptyString()
    {
        $this->assertEmpty($this->subject->getName());
    }

    /**
     * @test
     */
    public function nameCanBeSet()
    {
        $value = 'test';

        $this->subject->setName($value);

        $this->assertEquals($value, $this->subject->getName());
    }

    /**
     * @test
     */
    public function defaultConfigurationPathIsEmptyString()
    {
        $this->assertEmpty($this->subject->getConfigurationPath());
    }

    /**
     * @test
     */
    public function configurationPathCanBeSet()
    {
        $value = 'configurationPathCanBeSet';

        $this->subject->setConfigurationPath($value);

        $this->assertEquals($value, $this->subject->getConfigurationPath());
    }

    /**
     * @test
     */
    public function gettingConfigurationServiceTryToInitializeServiceFirstIfNull()
    {
        $this->subject->_setProperty('configurationService', null);

        $this->subject
            ->expects($this->once())
            ->method('getConfigurationInstance')
            ->willReturn($this->createMock(ConfigurationInterface::class));

        $this->subject->getConfigurationService();
    }

    /**
     * @test
     */
    public function gettingConfigurationServiceWhenIsSetWillJustReturn()
    {
        $mockedService = $this->createMock(ConfigurationInterface::class);
        $this->subject->_setProperty('configurationService', $mockedService);

        $this->subject
            ->expects($this->never())
            ->method('getConfigurationInstance');

        $this->assertSame($mockedService, $this->subject->getConfigurationService());
    }

    /**
     * @test
     */
    public function defaultLocalFilePathEmptyString()
    {
        $this->assertEmpty($this->subject->getLocalFilePath());
    }

    /**
     * @test
     */
    public function localFilePathCanBeSet()
    {
        $value = 'test/path';
        $this->subject->setLocalFilePath($value);

        $this->assertEquals($value, $this->subject->getLocalFilePath());
    }

    /**
     * @test
     */
    public function defaultLocalConfigurationIsFalse()
    {
        $this->assertFalse($this->subject->isLocalConfiguration());
    }

    /**
     * @test
     */
    public function localConfigurationCanBeSet()
    {
        $this->subject->setLocalConfiguration(true);

        $this->assertTrue($this->subject->isLocalConfiguration());
    }
}
