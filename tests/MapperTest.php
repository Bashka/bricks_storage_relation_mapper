<?php
namespace Bricks\Storage\Relation\Mapper;
require_once('Mapper.php');
require_once('SchemeException.php');
require_once('UniquenessException.php');
require_once('tests/EntityMock.php');
require_once('tests/RelatedEntityMock.php');

/**
 * @author Artur Sh. Mamedbekov
 */
class MapperTest extends \PHPUnit_Framework_TestCase{
  /**
   * @var Mapper Служба преобразований.
	 */
	private $mapper;

  /**
   * @var PDO Mock объект для соединения с БД.
   */
  private $pdo;

	public function setUp(){
    $this->pdo = $this->getMock('PDO', [], ['mysql:dbname=test', 'root', 'root']);

    $this->fetchStatement = $this->getMock('PDOStatement');
    $this->insertStatement = $this->getMock('PDOStatement');
    $this->updateStatement = $this->getMock('PDOStatement');
    $this->deleteStatement = $this->getMock('PDOStatement');
    $this->pdo->expects($this->at(0))->method('prepare')->will($this->returnValue($this->fetchStatement));
    $this->pdo->expects($this->at(1))->method('prepare')->will($this->returnValue($this->insertStatement));
    $this->pdo->expects($this->at(2))->method('prepare')->will($this->returnValue($this->updateStatement));
    $this->pdo->expects($this->at(3))->method('prepare')->will($this->returnValue($this->deleteStatement));

    $this->mapper = new Mapper($this->pdo, 'table', [
      'id' => 'id_field',
      'login' => 'login_field',
      'pass' => 'pass_field',
    ]);

    $this->mapper->prototype('Bricks\Storage\Relation\Mapper\EntityMock');
  }

  /**
   * Должен формировать запросы.
   */
  public function testConstructor(){
    $this->pdo->expects($this->at(0))
      ->method('prepare')
      ->with($this->equalTo('SELECT id_field AS id, login_field AS login, pass_field AS pass FROM table WHERE id_field = :id'));

    $this->pdo->expects($this->at(1))
      ->method('prepare')
      ->with($this->equalTo('INSERT INTO table (id_field, login_field, pass_field) VALUES (:id, :login, :pass)'));

    $this->pdo->expects($this->at(2))
      ->method('prepare')
      ->with($this->equalTo('UPDATE table SET id_field = :id, login_field = :login, pass_field = :pass WHERE id_field = :id'));

    $this->pdo->expects($this->at(3))
      ->method('prepare')
      ->with($this->equalTo('DELETE FROM table WHERE id_field = :id'));

    new Mapper($this->pdo, 'table', [
      'id' => 'id_field',
      'login' => 'login_field',
      'pass' => 'pass_field',
    ]);
  }

  /**
   * Должен восстанавливать объект.
   */
  public function testFetch(){
    $this->fetchStatement->expects($this->once())
      ->method('execute')
      ->with($this->equalTo(['id' => 1]))
      ->will($this->returnValue(true));

    $this->fetchStatement->expects($this->any())
      ->method('rowCount')
      ->will($this->returnValue(1));

    $this->fetchStatement->expects($this->once())
      ->method('fetchObject')
      ->with($this->equalTo('Bricks\Storage\Relation\Mapper\EntityMock'))
      ->will($this->returnValue('test'));

    $this->fetchStatement->expects($this->once())
      ->method('closeCursor');

    $this->assertEquals('test', $this->mapper->fetch(1));
  }

  /**
   * Должен закрывать указатель, если сущность не найдена.
   */
  public function testFetch_closeIfNotFetch(){
    $this->fetchStatement->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(true));

    $this->fetchStatement->expects($this->any())
      ->method('rowCount')
      ->will($this->returnValue(0));

    $this->fetchStatement->expects($this->once())
      ->method('closeCursor');

    $this->assertNull($this->mapper->fetch(1));
  }

  /**
   * Должен выбрасывать исключение, если найдено более одной сущности по данному 
   * идентификатору.
   */
  public function testFetch_throwExceptionIfIdNotUnique(){
    $this->setExpectedException(get_class(new UniquenessException));

    $this->fetchStatement->expects($this->once())
      ->method('execute')
      ->will($this->returnValue(true));

    $this->fetchStatement->expects($this->any())
      ->method('rowCount')
      ->will($this->returnValue(2));

    $this->mapper->fetch(1);
  }

  /**
   * Должен устанавливать выражение для сохранения RelatedEntity.
   */
  public function testFetch_setSaveStatement(){
    $this->mapper->prototype('Bricks\Storage\Relation\Mapper\RelatedEntityMock');
    
    $this->fetchStatement->expects($this->once())
      ->method('execute')
      ->with($this->equalTo(['id' => 1]))
      ->will($this->returnValue(true));

    $this->fetchStatement->expects($this->any())
      ->method('rowCount')
      ->will($this->returnValue(1));

    $entity = $this->getMock('Bricks\Storage\Relation\Mapper\RelatedEntity');

    $this->fetchStatement->expects($this->once())
      ->method('fetchObject')
      ->with($this->equalTo('Bricks\Storage\Relation\Mapper\RelatedEntityMock'))
      ->will($this->returnValue($entity));

    $entity->expects($this->once())
      ->method('setSaveStatement')
      ->with($this->equalTo($this->updateStatement));

    $this->assertEquals($entity, $this->mapper->fetch(1));
  }

  /**
   * Должен добавлять объект.
   */
  public function testInsert(){
    $entity = new EntityMock;
    $entity->login = 'login';
    $entity->pass = 'pass';

    $this->insertStatement->expects($this->once())
      ->method('execute')
      ->with($this->equalTo(['login' => 'login', 'pass' => 'pass', 'id' => null]))
      ->will($this->returnValue(true));

    $this->mapper->insert($entity);
  }

  /**
   * Должен обновлять объект.
   */
  public function testUpdate(){
    $entity = new EntityMock;
    $entity->id = 1;
    $entity->login = 'login';
    $entity->pass = 'pass';

    $this->updateStatement->expects($this->once())
      ->method('execute')
      ->with($this->equalTo(['login' => 'login', 'pass' => 'pass', 'id' => 1]))
      ->will($this->returnValue(true));

    $this->mapper->update($entity);
  }

  /**
   * Должен удалять объект.
   */
  public function testDelete(){
    $this->deleteStatement->expects($this->once())
      ->method('execute')
      ->with($this->equalTo(['id' => 1]))
      ->will($this->returnValue(true));

    $this->mapper->delete(1);
  }
}
