<?php


namespace Tests;

use \PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use \User;
use \UserCache;
class UserCacheTest extends TestCase
{
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

        $this->cacheMock = $this->getMockBuilder('Memcached')
          ->disableOriginalConstructor()
          ->onlyMethods(['get', 'set', 'delete'])
          ->getMock();
    }


    public function testCacheMiss(){
        $expectedUserData = [
          'id' => 1,
          'name' => 'test',
          'email' => 'adasdad@sdasd.com',
          'currency' => 'usd',
          'sum' => 100,
        ];

        $this->stmtMock->expects($this->once())
          ->method('execute')
          ->will($this->returnValue(true));
        $this->stmtMock->expects($this->once())->method('fetchAll')
          ->will($this->returnValue($expectedUserData));

        $this->dbMock->expects($this->once())
          ->method('prepare')
          ->with($this->stringContains('SELECT id,name,email,currency,sum FROM users WHERE id  = :1 LIMIT 1'))
          ->will($this->returnValue( $this->stmtMock));


        $this->cacheMock->expects($this->at(0))
          ->method('get')
          ->with($this->stringContains('User1'))
          ->will($this->returnValue( false));

        $user = new User($this->dbMock);
        $userCache = new UserCache($this->cacheMock, $user);

        $this->assertSame($expectedUserData, $userCache->findOne(1));
    }

    public function testCacheHit(){
        $expectedUserData = [
          'id' => 1,
          'name' => 'test',
          'email' => 'adasdad@sdasd.com',
          'currency' => 'usd',
          'sum' => 100,
        ];

        $this->cacheMock->expects($this->any(1))
          ->method('get')
          ->with($this->stringContains('User1'))
          ->will($this->returnValue( $expectedUserData));

        $user = new User($this->dbMock);

        $userCache = new UserCache($this->cacheMock, $user);
        $this->assertSame($expectedUserData, $userCache->findOne(1));
    }



    public function testCacheRemove(){
        $user = new User($this->dbMock, [
          'id' => 6,
          'name' => 'origin',
          'email' => 'origin@origin.com',
          'currency' => 'usd',
          'sum' => 200,
        ]);
        $userCache = new UserCache($this->cacheMock, $user);


        $this->stmtMock->expects($this->once())
          ->method('execute')
          ->will($this->returnValue(true));

        $this->dbMock->expects($this->once())
          ->method('prepare')
          ->with($this->stringContains('UPDATE users SET name=:1,email=:2,currency=:3,sum=:4 WHERE id  = :5'))
          ->will($this->returnValue( $this->stmtMock));
        $this->cacheMock->expects($this->once())
          ->method('delete')
          ->with($this->stringContains('User6'))
          ->will($this->returnValue(true));

        $this->assertSame(true, $userCache->save());

    }
}