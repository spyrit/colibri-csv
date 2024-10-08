<?php

namespace CSanquer\ColibriCsv\Tests;

use CSanquer\ColibriCsv\AbstractCsv;
use CSanquer\ColibriCsv\Dialect;
use CSanquer\ColibriCsv\Tests\AbstractCsvTestCase;
use InvalidArgumentException;

/**
 * AbstractCsvTest
 *
 * @author Charles SANQUER - <charles.sanquer@gmail.com>
 */
class AbstractCsvTest extends AbstractCsvTestCase
{
    /**
     *
     * @var AbstractCsv
     */
    protected $structure;

    protected function setUp(): void
    {
        $this->structure = $this->getMockForAbstractClass('CSanquer\ColibriCsv\AbstractCsv');
        $this->structure->expects($this->any())
             ->method('getCompatibleFileHanderModes')
             ->will($this->returnValue(array('rb', 'wb', 'r+b', 'w+b', 'a+b', 'x+b', 'c+b')));
    }

    /**
     * @dataProvider providerGetSetDialect
     */
    public function testGetSetDialect($input)
    {
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->setDialect($input));
        $this->assertInstanceOf('\\CSanquer\\ColibriCsv\\Dialect', $this->structure->getDialect());
    }

    public function providerGetSetDialect()
    {
        return array(
            array(new Dialect()),
        );
    }

    /**
     * @dataProvider providerGetSetFile
     */
    public function testGetSetFile($input, $expected, $expectedResource = false)
    {
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->setFile($input));
        $this->assertEquals($expected, $this->structure->getFilename());
        if ($expectedResource) {
            $this->assertIsResource($this->structure->getFileHandler());
        }
    }

    public function providerGetSetFile()
    {
        return array(
            array(null, null),
            array('', ''),
            array(__DIR__.'/Fixtures/test1.csv', __DIR__.'/Fixtures/test1.csv', false),
        );
    }

    public function testGetHeaders()
    {
        $this->assertEquals(array(), $this->structure->getHeaders());
    }

    public function testSetFileWithIncorrectResource()
    {
        $this->expectException(InvalidArgumentException::class);
        $context = stream_context_create();
        $this->structure->setFile($context);
    }

    public function testGetSetFilename()
    {
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->setFilename(__DIR__.'/Fixtures/test1.csv'));
        $this->assertEquals(__DIR__.'/Fixtures/test1.csv', $this->structure->getFilename());
    }

    public function testOpen()
    {
        $this->assertFalse($this->structure->isFileOpened());
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->open(__DIR__.'/Fixtures/test1.csv'));
        $this->assertTrue($this->structure->isFileOpened());
        $this->assertIsResource($this->structure->getFileHandler());

        return $this->structure;
    }

    public function testOpenFilePreviouslySet()
    {
        $this->assertFalse($this->structure->isFileOpened());
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->setFile(__DIR__.'/Fixtures/test1.csv'));
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->open());
        $this->assertTrue($this->structure->isFileOpened());
        $this->assertIsResource($this->structure->getFileHandler());
    }

    public function testOpenFileHandler()
    {
        $csv = <<<CSV
nom,prénom,age
Martin,Durand,"28"
Alain,Richard,"36"
CSV;

        $stream = fopen('php://memory','r+b');
        fwrite($stream, $csv);
        rewind($stream);

        $this->assertFalse($this->structure->isFileOpened());
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->open($stream));
        $this->assertTrue($this->structure->isFileOpened());
        $this->assertIsResource($this->structure->getFileHandler());
        $this->assertEquals($csv, stream_get_contents($this->structure->getFileHandler()));

    }

    public function testOpenNewFile()
    {
        $file1 = __DIR__.'/Fixtures/test1.csv';
        $file2 = __DIR__.'/Fixtures/test2.csv';

        $this->assertFalse($this->structure->isFileOpened());
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->open($file1));
        $this->assertEquals($file1, $this->structure->getFilename());
        $this->assertTrue($this->structure->isFileOpened());
        $fileHandler1 = $this->structure->getFileHandler();
        $this->assertIsResource($fileHandler1);

        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->open($file2));
        $this->assertEquals($file2, $this->structure->getFilename());
        $this->assertTrue($this->structure->isFileOpened());
        $fileHandler2 = $this->structure->getFileHandler();
        $this->assertIsResource($fileHandler2);

        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->close());
        $this->assertFalse($this->structure->isFileOpened());
        $fileHandler2 = $this->structure->getFileHandler();
        $this->assertIsNotResource($fileHandler2);
    }

    public function testOpenNoFilename()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->open());
    }

    public function testOpenNoExistingFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $this->structure->open(__DIR__.'/Fixtures/abc.csv'));
    }

    /**
     * @depends testOpen
     * @param AbstractCsv $structure
     */
    public function testClose($structure)
    {
        $this->assertTrue($structure->isFileOpened());
        $this->assertInstanceOf('CSanquer\ColibriCsv\AbstractCsv', $structure->close());
        $this->assertFalse($structure->isFileOpened());
        $this->assertIsNotResource($structure->getFileHandler());
    }
}
