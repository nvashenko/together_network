<?php


namespace Tests;

use \PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use \User;

class UserTest extends TestCase
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
    }

    public function testEmptyUserName(){
        $this->expectException(InvalidArgumentException::class);
        $user = new User($this->dbMock);
        $user->name = '  ';
    }

    public function testLongUserName(){
        $this->expectException(InvalidArgumentException::class);
        $user = new User($this->dbMock);
        $user->name = str_repeat('longname', 32); /*get long name*/
    }

    public function testInvalidEmailName(){
        $this->expectException(InvalidArgumentException::class);
        $user = new User($this->dbMock);
        $user->email = 'notemail';
        $user->save();
    }

    public function testLongUserEmail(){
        $this->expectException(InvalidArgumentException::class);
        $user = new User($this->dbMock);
        $user->email = str_repeat('long@email.com', 32); /*get long email*/
    }

    public function testLongUserCurrency(){
        $this->expectException(InvalidArgumentException::class);
        $user = new User($this->dbMock);
        $user->currency = 'currency';
    }

    public function testInvalidUserSum(){
        $this->expectException(InvalidArgumentException::class);
        $user = new User($this->dbMock);
        $user->sum = 'string';
    }

    public function testInsertOne(){
        $user = new User($this->dbMock);
        $user->name = 'test';
        $user->email = 'test@test.com';
        $user->currency = 'UAH';
        $user->sum = 100;

        $this->stmtMock->method('execute')
          ->will($this->returnValue(true));

        $this->dbMock->expects($this->any())->method('prepare')
          ->with($this->stringContains('INSERT INTO users (id,name,email,currency,sum) VALUES (:1,:2,:3,:4,:5)'))
          ->will($this->returnValue( $this->stmtMock));
        $this->dbMock->method('lastInsertId')
          ->will($this->returnValue(1));

        $user->save();
        $this->assertSame(1, $user->getPrimaryKey());
        $this->assertSame(1, $user->id);
        $this->assertSame('test', $user->name);
        $this->assertSame('test@test.com', $user->email);
        $this->assertSame('UAH', $user->currency);
        $this->assertSame(100, $user->sum);
    }

    public function testInsertEmptyData(){
        $user = new User($this->dbMock);
        $this->stmtMock->expects($this->once())->method('execute')
          ->will($this->returnValue(true));

        $this->dbMock->expects($this->once())->method('prepare')
          ->with($this->stringContains('INSERT INTO users default values'))
          ->will($this->returnValue( $this->stmtMock));
        $this->dbMock->method('lastInsertId')
          ->will($this->returnValue(1));

        $user->save();
        $this->assertSame(1, $user->getPrimaryKey());
    }


    public function testInsertMultiple(){
        $user = new User($this->dbMock);

        $this->stmtMock->expects($this->once())
          ->method('execute')
          ->will($this->returnValue(true));

        $this->dbMock->expects($this->once())
          ->method('prepare')
          ->with($this->stringContains('INSERT INTO users (id,name,email,currency,sum) VALUES (:1,:2,:3,:4,:5),(:6,:7,:8,:9,:10)'))
          ->will($this->returnValue( $this->stmtMock));

        $this->assertSame(true,
          $user->insertMultiple(
            [
              [
                'name' => 'test',
                'email' => 'adasdad@sdasd.com',
                'currency' => 'usd',
                'sum' => 1232,
              ],
              [
                'name' => 'test2',
                'email' => 'adasdad@dasd.com',
                'currency' => 'uah',
                'sum' => 333,
              ],
            ]
          )
        );
    }


    public function testGetOne(){
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

        $user = new User($this->dbMock);

        $this->assertSame($expectedUserData, $user->findOne(1));
    }

    public function testGetMultiple(){
        $expectedUsersData = [
          [
            'id' => 1,
            'name' => 'test',
            'email' => 'adasdad@sdasd.com',
            'currency' => 'usd',
            'sum' => 100,
          ],
          [
            'id' => 2,
            'name' => 'test',
            'email' => 'adasdad@sdasd.com',
            'currency' => 'usd',
            'sum' => 200,
          ],
        ];

        $this->stmtMock->expects($this->once())
          ->method('execute')
          ->will($this->returnValue(true));
        $this->stmtMock->expects($this->once())->method('fetchAll')
          ->will($this->returnValue($expectedUsersData));

        $this->dbMock->expects($this->once())
          ->method('prepare')
          ->with($this->stringContains('SELECT id,name,email,currency,sum FROM users'))
          ->will($this->returnValue( $this->stmtMock));
        $user = new User($this->dbMock);
        $this->assertSame($expectedUsersData, $user->find([]));
    }


    public function testGetMultipleIn(){
        $expectedUsersData = [
          [
            'id' => 1,
            'name' => 'test',
            'email' => 'adasdad@sdasd.com',
            'currency' => 'usd',
            'sum' => 100,
          ],
          [
            'id' => 2,
            'name' => 'test2',
            'email' => 'adasdad@sdasd.com',
            'currency' => 'usd',
            'sum' => 200,
          ],
        ];

        $this->stmtMock->expects($this->once())
          ->method('execute')
          ->will($this->returnValue(true));
        $this->stmtMock->expects($this->once())->method('fetchAll')
          ->will($this->returnValue($expectedUsersData));

        $this->dbMock->expects($this->once())
          ->method('prepare')
          ->with($this->stringContains('SELECT id,name,email,currency,sum FROM users'))
          ->will($this->returnValue( $this->stmtMock));
        $user = new User($this->dbMock);
        $this->assertSame($expectedUsersData, $user->find(['name' => ['test', 'test2']]));
    }


    public function testGetMultipleOffset(){
        $expectedUsersData = [
          [
            'id' => 2,
            'name' => 'test2',
            'email' => 'adasdad@sdasd.com',
            'currency' => 'usd',
            'sum' => 200,
          ],
        ];

        $this->stmtMock->expects($this->once())
          ->method('execute')
          ->will($this->returnValue(true));
        $this->stmtMock->expects($this->once())->method('fetchAll')
          ->will($this->returnValue($expectedUsersData));

        $this->dbMock->expects($this->once())
          ->method('prepare')
          ->with($this->stringContains('SELECT id,name,email,currency,sum FROM users'))
          ->will($this->returnValue( $this->stmtMock));
        $user = new User($this->dbMock);
        $user->setOffset(1)->setLimit(1);
        $this->assertSame($expectedUsersData, $user->find(['name' => ['test', 'test2']]));
    }



    public function testUpdate(){
        $expectedUserData = [
            'id' => 2,
            'name' => 'updated',
            'email' => 'updated@updated.com',
            'currency' => 'gbp',
            'sum' => 300,
        ];

        $this->stmtMock->expects($this->once())
          ->method('execute')
          ->will($this->returnValue(true));

        $this->dbMock->expects($this->once())
          ->method('prepare')
          ->with($this->stringContains('UPDATE users SET name=:1,email=:2,currency=:3,sum=:4 WHERE id  = :5'))
          ->will($this->returnValue( $this->stmtMock));
        $user = new User($this->dbMock, [
          'id' => 2,
          'name' => 'origin',
          'email' => 'origin@origin.com',
          'currency' => 'usd',
          'sum' => 200,
        ]);
        $user->name = $expectedUserData['name'];
        $user->email = $expectedUserData['email'];
        $user->currency = $expectedUserData['currency'];
        $user->sum = $expectedUserData['sum'];
        $this->assertSame(true, $user->save());
        $this->assertSame($expectedUserData['name'], $user->getName());
        $this->assertSame($expectedUserData['email'], $user->getEmail());
        $this->assertSame($expectedUserData['currency'], $user->getCurrency());
        $this->assertSame($expectedUserData['sum'], $user->getSum());
    }
}