<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Processors\Relation\AbstractRelationFieldProcessor;

/**
 * Class AbstractRelationFieldProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation
 */
class AbstractRelationFieldProcessorTest extends UnitTestCase
{
    /**
     * @var \Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface|\PHPUnit\Framework\MockObject\MockObject|AbstractRelationFieldProcessor
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(AbstractRelationFieldProcessor::class, ['domainModel'], [], '', false);
        parent::setUp();
    }

    /**
     * @test
     */
    public function treatIdentifierAsUidReturnFalseIfNotSetInConfiguration()
    {
        $this->assertFalse($this->subject->_call('treatIdentifierAsUid'));
    }

    /**
     * @test
     */
    public function treatIdentifierAsUidReturnTrueIfSetInConfiguration()
    {
        $this->inject($this->subject, 'configuration', ['treatIdentifierAsUid' => true]);
        $this->assertTrue($this->subject->_call('treatIdentifierAsUid'));
    }

    /**
     * @test
     */
    public function delimReturnDelimFromConfiguration()
    {
        $delim = '--';
        $this->inject($this->subject, 'configuration', ['delim' => $delim]);
        $this->assertEquals($delim, $this->subject->_call('delim'));
    }

    /**
     * @test
     */
    public function delimReturnDefaultValueIfNoConfig()
    {
        $this->assertEquals(',', $this->subject->_call('delim'));
    }
}
