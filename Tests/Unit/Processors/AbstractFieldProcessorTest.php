<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Domain\Validation\Validator\ValidatorInterface;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\CriticalErrorValidationException;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\ErrorValidationException;
use Pixelant\PxaPmImporter\Processors\AbstractFieldProcessor;
use Pixelant\PxaPmImporter\Importer\ImporterInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class AbstractFieldProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors
 */
class AbstractFieldProcessorTest extends UnitTestCase
{
    /**
     * @var AbstractFieldProcessor|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            AbstractFieldProcessor::class,
            ['process'],
            [],
            '',
            false
        );
        parent::setUp();
    }

    /**
     * @test
     */
    public function simplePropertySetSetValueForEntity()
    {
        $entity = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $this->subject->_set('entity', $entity);
        $this->subject->_set('property', 'pid');

        $uid = 12;
        $this->subject->_call('simplePropertySet', $uid);

        $this->assertEquals($uid, $entity->getPid());
    }


}
