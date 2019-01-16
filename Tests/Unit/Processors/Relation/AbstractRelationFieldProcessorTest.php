<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Processors\Relation\AbstractRelationFieldProcessor;
use Pixelant\PxaProductManager\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class AbstractRelationFieldProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation
 */
class AbstractRelationFieldProcessorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function preProcessWillCallInitEntities()
    {
        /** @var AbstractRelationFieldProcessor|MockObject $subject */
        $subject = $this
            ->getMockBuilder(AbstractRelationFieldProcessor::class)
            ->setMethods(['initEntities'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject
            ->expects($this->once())
            ->method('initEntities');

        $value = '';
        $subject->preProcess($value);
    }

    /**
     * @test
     */
    public function preProcessThrowExceptionIfEntitiesNotValud()
    {
        /** @var AbstractRelationFieldProcessor|MockObject $subject */
        $subject = $this
            ->getMockBuilder(AbstractRelationFieldProcessor::class)
            ->setMethods(['initEntities'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject
            ->expects($this->once())
            ->method('initEntities')
            ->willReturn(['test', 123]);

        $this->expectException(\UnexpectedValueException::class);

        $value = '';
        $subject->preProcess($value);
    }

    /**
     * @test
     */
    public function initValueOfFailedInitIsFalse()
    {
        /** @var AbstractRelationFieldProcessor|MockObject $subject */
        $subject = $this
            ->getMockBuilder(AbstractRelationFieldProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertFalse($this->getObjectAttribute($subject, 'failedInit'));
    }

    /**
     * @test
     */
    public function validationFailsIfFailedInit()
    {
        /** @var AbstractRelationFieldProcessor|MockObject $subject */
        $subject = $this
            ->getMockBuilder(AbstractRelationFieldProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertFalse($subject->isValid('test'));
    }
}
