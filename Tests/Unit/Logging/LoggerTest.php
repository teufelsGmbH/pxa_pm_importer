<?php

namespace Pixelant\PxaPmImporter\Tests\Unit\Logging;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Logging\Logger;

/**
 * Class LoggerTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Logging
 */
class LoggerTest extends UnitTestCase
{
    protected function setUp()
    {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Pixelant']['PxaPmImporter']['writerConfiguration'] = [
            \TYPO3\CMS\Core\Log\LogLevel::INFO => [
                \Pixelant\PxaPmImporter\Logging\Writer\FileWriter::class => [
                    'logFile' => 'fake/pm_importer.log'
                ]
            ]
        ];
    }

    /**
     * @test
     */
    public function addingErrorsForDifferentLoggersInstancesWillSaveAllMessages()
    {
        $reflectionClass = new \ReflectionClass(Logger::class);
        $reflectionProperty = $reflectionClass->getProperty('errorMessages');

        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue([]);

        $mockedLogger = $this->createMock(\TYPO3\CMS\Core\Log\Logger::class);

        $logger1 = Logger::getInstance('fake.path');
        $logger2 = Logger::getInstance('fake.path2');

        $this->inject($logger1, 'logger', $mockedLogger);
        $this->inject($logger2, 'logger', $mockedLogger);

        $error1 = 'Here we have first error';
        $criticalError2 = 'Something went wrong';

        $logger1->error($error1);
        $logger2->critical($criticalError2);


        $expect = [$error1, $criticalError2];

        $this->assertEquals($expect, array_values(Logger::getErrorMessages()));
    }

    /**
     * @test
     */
    public function errorMessageIsSavedIfIsUnique()
    {
        $reflectionClass = new \ReflectionClass(Logger::class);
        $reflectionProperty = $reflectionClass->getProperty('errorMessages');

        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue([]);

        $mockedLogger = $this->createMock(\TYPO3\CMS\Core\Log\Logger::class);

        $logger1 = Logger::getInstance('fake.path');
        $logger2 = Logger::getInstance('fake.path2');

        $this->inject($logger1, 'logger', $mockedLogger);
        $this->inject($logger2, 'logger', $mockedLogger);

        $error1 = 'Here we have first error 123';
        $error2 = 'Here we have first error 123';
        $criticalError2 = 'Something went wrong 321';
        $criticalError3 = 'Something went wrong 321';

        $logger1->error($error1);
        $logger1->error($error2);

        $logger2->critical($criticalError2);
        $logger2->critical($criticalError3);


        $expect = [$error1, $criticalError2];

        $this->assertEquals($expect, array_values(Logger::getErrorMessages()));
    }

    protected function tearDown()
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['LOG']);
    }
}