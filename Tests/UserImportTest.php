<?php


namespace Tests;

use \PHPUnit\Framework\TestCase;
use SplFileObject;
use \User;
use \UserImport;
use \CsvLineReader;
class UserImportTest extends TestCase
{
    private static $file;
    private static $expected;

    public static function setUpBeforeClass(): void
    {
        $tmpFilePath = tempnam('/tmp', 'uimporttest');
        self::$expected = [
          'name,email,currency,sum',
          'Myrtie Bowen,fi@gihob.net,UAH,422',
          'Jordan Pierce,jinsiur@je.mx,GBP,52',
          'Abbie Wilkerson,banfu@sijaldu.hm,UAH,211',
          'Lettie Daniel,lal@wo.mg,USD,198',
        ];
        file_put_contents($tmpFilePath, implode("\n", self::$expected));
        self::$file = new SplFileObject($tmpFilePath);
    }

    protected function setUp(): void
    {
        $this->stmtMock = $this->getMockBuilder('stdClass')
          ->disableOriginalConstructor()
          ->addMethods(['execute', 'fetchAll'])
          ->getMock();

        $this->dbMock = $this->getMockBuilder('PDO')
          ->disableOriginalConstructor()
          ->onlyMethods(['prepare', 'lastInsertId'])
          ->getMock();

    }


    public function testUserImport()
    {

        $this->stmtMock->expects($this->any())
          ->method('execute')
          ->will($this->returnValue(true));

        $this->dbMock->expects($this->at(0))
          ->method('prepare')
          ->with($this->stringContains('INSERT INTO users (id,name,email,currency,sum) VALUES (:1,:2,:3,:4,:5),(:6,:7,:8,:9,:10),(:11,:12,:13,:14,:15)'))
          ->will($this->returnValue($this->stmtMock));
        $this->dbMock->expects($this->at(1))
          ->method('prepare')
          ->with($this->stringContains('INSERT INTO users (id,name,email,currency,sum) VALUES (:1,:2,:3,:4,:5)'))
          ->will($this->returnValue($this->stmtMock));


        $user =  new User($this->dbMock);
        $importer = new UserImport($user, 3);
        $lineReader = new CsvLineReader(self::$file);
        $lineReader->readHeader();
        $lineReader->attach($importer, 'new_line');
        $lineReader->attach($importer, 'dataend');
        $lineReader->read();
        $this->assertSame(explode(',', self::$expected[0]), $lineReader->getHeader());
    }

    protected function tearDown(): void
    {
        unlink(self::$file->getPathName());

    }


}