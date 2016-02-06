<?php
namespace Bricks\Storage\Relation\Mapper;

/**
 * Класс для работы с реляционными базами данных в контексте 
 * объектно-ориентированной модели.
 *
 * @author Artur Sh. Mamedbekov
 */
class Mapper{
  /**
   * @var \PDO PDO адаптер.
   */
  private $pdo;

  /**
   * @var string Имя целевой таблицы.
   */
  private $table;

  /**
   * @var string Класс прототипа.
   */
  private $prototype;

  /**
   * @var array Схема преобразований. Структура: [имяСвойства => имяПоля, ...].
   */
  private $scheme;

  /**
   * @var string SQL выражение, используемое для восстановления коллекции 
   * сущностей по условию отбора.
   * SELECT field AS prop, ... FROM table
   */
  private $selectSql;

  /**
   * @var string SQL выражение, используемое для вычисления количества строк, 
   * затрагиваемых данным условием отбора.
   * SELECT COUNT(*) FROM table
   */
  private $countSql;

  /**
   * @var \PDOStatement[] Кэш используемых SQL запросов.
   */
  private $selectStatements;

  /**
   * @var \PDOStatement Выражение, используемое для восстановление сущности по 
   * ее идентификатору.
   * SELECT field AS prop, ... FROM table WHERE idField = :id
   */
  private $fetchStatement;

  /**
   * @var \PDOStatement Выражение, используемое для добавления сущности в 
   * таблицу.
   * INSERT INTO table (field, ...) VALUES (:prop, ...)
   */
  private $insertStatement;

  /**
   * @var \PDOStatement Выбражение, используемое для обновление сущности в 
   * таблице.
   * UPDATE table SET field = :prop, ... WHERE idField = :id
   */
  private $updateStatement;

  /**
   * @var \PDOStatement Выбражение, используемое для удаление сущности из 
   * таблицы.
   * DELETE FROM table WHERE idField = :id
   */
  private $deleteStatement;

  /**
   * Получить имя поля по имени свойства.
   *
   * @param string $property Целевое свойство.
   *
   * @return string|null Имя поля таблицы, используемое для хранения данного 
   * свойства или null - если соответствие не найдено.
   */
  protected function scheme($property){
    if(!isset($this->scheme[$property])){
      return null;
    }

    return $this->scheme[$property];
  }

  /**
   * Замена всех вхождений имен свойств в SQL выражении на соответствующие им 
   * поля.
   * Свойства в выражении должны начинаться с восклицательного знака.
   *
   * @param string $sql Исходное SQL выражение.
   *
   * @return string Результирующее выражение.
   */
  protected function convert($sql){
    return preg_replace_callback('/!([A-Za-z0-9_]+)/', function($matches){
      if(is_null($field = $this->scheme($matches[1]))){
        return $this->table() . '.' . $matches[1];
      }
      return $this->table() . '.' . $field;
    }, $sql);
  }

  /**
   * Формирует выражение объединения текущей схемы со сторонней.
   *
   * @param string $type Тип объединения (INNER, LEFT, RIGHT, FULL и т.д.).
   * @param Mapper $addedSheme Mapper добавляемой сущности.
   * @param string $foreign Имя свойства текущей схемы.
   * @param string $target Имя свойство добавляемой схемы.
   *
   * @return string Результирующее объединение.
   */
  protected function join($type, Mapper $addedSheme, $foreign, $target){
    return $type . ' JOIN ' .
      $addedSheme->table() .
      ' ON ' .
      $this->convert('!' . $foreign) .
      ' = ' .
      $addedSheme->convert('!' . $target);
  }

  /**
   * Получить коллекцию сущностей по условию отбора.
   * Используемые SQL запросы кэшируются.
   *
   * @param string $condition Условие отбора, в которое могут входить операции 
   * WHERE, LIMIT, GROUP, ORDER и т.д. Строка предварительно конвертируется с 
   * помощью метода convert.
   * @param array $params [optional] Параметры, заполняющие токены запроса.
   *
   * @throws \PDOException Выбрасывается в случае возникновения ошибки при 
   * выполнении запроса.
   *
   * @return Entity[] Коллекция сущностей.
   */
  protected function select($condition, array $params = []){
    $select = $this->selectSql . ' ' . $condition;

    $hash = md5($select);
    if(!isset($this->selectStatements[$hash])){
      $this->selectStatements[$hash] = $this->pdo()->prepare($this->selectSql . ' ' . $this->convert($condition));
      $this->selectStatements[$hash]->setFetchMode(\PDO::FETCH_CLASS, $this->prototype);
    }

    $selectStatement = $this->selectStatements[$hash];
    if(!$selectStatement->execute($params)){
      throw new \PDOException($selectStatement->errorInfo(), $selectStatement->errorCode());
    }

    $entities = $selectStatement->fetchAll();

    if(is_subclass_of($this->prototype, 'Bricks\Storage\Relation\Mapper\RelatedEntity')){
      foreach($entities as $entity){
        $entity->setSaveStatement($this->updateStatement);
      }
    }
    
    return $entities;
  }

  /**
   * Получить число строк, затронутых условием отбора.
   * Используемые SQL запросы кэшируются.
   *
   * @param string $condition Условие отбора, в которое могут входить операции 
   * WHERE, LIMIT, GROUP, ORDER и т.д. Строка предварительно конвертируется с 
   * помощью метода convert.
   * @param array $params [optional] Параметры, заполняющие токены запроса.
   *
   * @throws \PDOException Выбрасывается в случае возникновения ошибки при 
   * выполнении запроса.
   *
   * @return int Число строк, затронутых условием отбора.
   */
  protected function count($condition, array $params = []){
    $select = $this->countSql . ' ' . $condition;

    $hash = md5($select);
    if(!isset($this->selectStatements[$hash])){
      $this->selectStatements[$hash] = $this->pdo()->prepare($this->countSql . ' ' . $this->convert($condition));
    }

    $selectStatement = $this->selectStatements[$hash];
    if(!$selectStatement->execute($params)){
      throw new \PDOException($selectStatement->errorInfo(), $selectStatement->errorCode());
    }

    return $selectStatement->fetchObject()->count;
  }

  /**
   * @param \PDO PDO адаптер.
   * @param string $table Имя целевой таблицы.
   * @param array $scheme Схема преобразования полей целевой таблицы. В качестве 
   * схемы может использоваться:
   *   - Ассоциативный массив, ключами в котором служат имена свойств 
   *   результирующей сущности, а значениями поля таблицы, хранящие значения 
   *   этих свойств
   *   - Массив полей таблицы (в этом случае преобразование имен не выполняется)
   * Обязательным элементом схемы является элемент с ключем "id", который 
   * определяет первичный ключ таблицы.
   *
   * @throws SchemeException Выбрасывается в случае недопустимого формата схемы 
   * преобразования.
   */
  public function __construct(\PDO $pdo, $table, array $scheme){
    if(!isset($scheme['id'])){
      throw new SchemeException('Key "id" not found');
    }

    $this->pdo = $pdo;
    $this->table = $table;
    $this->prototype('Bricks\Storage\Relation\Mapper\Entity');
    $this->selectStatements = [];

    $idField = $scheme['id'];
    $fields = array_values($scheme);
    $properties = array_map(function($property, $field){
      if(is_int($property)){
        return $field;
      }
      else{
        return $property;
      }
    }, array_keys($scheme), $fields);
    $tokens = array_map(function($property){
      return ':' . $property;
    }, $properties);
    $this->scheme = array_combine($properties, $fields);

    // Select statement
    $sql = 'SELECT ' . implode(', ', array_map(function($field, $property){
      return $field . ' AS ' . $property;
    }, $fields, $properties)) . ' FROM ' . $table;
    $this->selectSql = $sql;

    // Count statement
    $sql = 'SELECT COUNT(*) AS count FROM ' . $table;
    $this->countSql = $sql;

    // Fetch statement
    $this->fetchStatement = $pdo->prepare($this->selectSql . ' WHERE ' . $idField . ' = :id');

    // Insert statement
    $this->insertStatement = $pdo->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $tokens)  . ')');

    // Update statment
    $sql = 'UPDATE ' . $table . ' SET ';
    $sql .= implode(', ', array_map(function($field, $token){
      return $field . ' = ' . $token;
    }, $fields, $tokens));
    $sql .= ' WHERE ' . $idField . ' = :id';
    $this->updateStatement = $pdo->prepare($sql);

    // Delete statement
    $this->deleteStatement = $pdo->prepare('DELETE FROM ' . $table . ' WHERE ' . $idField . ' = :id');
  }

  /**
   * Определение прототипа сущности. На основе данного класса будут создаваться 
   * результирующие данные методов fetch и select экземпляра класса.
   *
   * @param string $prototype [optional] Имя класса-прототипа.
   */
  public function prototype($prototype = null){
    if(is_null($prototype)){
      return $this->prototype;
    }

    $this->prototype = $prototype;
  }

  /**
   * Получить PDO адаптер, используемый объектом.
   *
   * @return \PDO Используемый PDO адаптер.
   */
  public function pdo(){
    return $this->pdo;
  }

  /**
   * Получить имя целевой таблицы.
   *
   * @return string Имя целевой таблицы.
   */
  public function table(){
    return $this->table;
  }

  /**
   * Метод восстанавливает сущность из таблицы по ее идентификатору.
   *
   * @param int $id Идентификатор целевой записи.
   *
   * @throws UniquenessException Выбрасывается в случае нарушения однозначности 
   * идентификатора.
   * @throws \PDOException Выбрасывается в случае возникновения ошибки при 
   * выполнении запроса.
   *
   * @return Entity|null Восстановленная сущность или null - если найти 
   * состояние сущности по данному идентификатору неудалось.
   */
  public function fetch($id){
    if(!$this->fetchStatement->execute(['id' => $id])){
      throw new \PDOException($this->fetchStatement->errorInfo(), $this->fetchStatement->errorCode());
    }

    if($this->fetchStatement->rowCount() == 0){
      $this->fetchStatement->closeCursor();
      return null;
    }
    elseif($this->fetchStatement->rowCount() == 1){
      $entity = $this->fetchStatement->fetchObject($this->prototype);
      if(is_subclass_of($this->prototype, 'Bricks\Storage\Relation\Mapper\RelatedEntity')){
        $entity->setSaveStatement($this->updateStatement);
      }

      $this->fetchStatement->closeCursor();

      return $entity;
    }
    else{
      throw new UniquenessException('ID is not unique');
    }
  }

  /**
   * Метод добавляет запись в таблицу.
   * В случае успешного выполнения, сущности добавляется идентификатор в 
   * качестве свойства id.
   *
   * @param Entity $entity Добавляемая сущность.
   *
   * @throws \PDOException Выбрасывается в случае возникновения ошибки при 
   * выполнении запроса.
   */
  public function insert(Entity &$entity){
    if($this->insertStatement->execute(get_object_vars($entity))){
      $entity->id = $this->pdo->lastInsertId();
    }
    else{
      throw new \PDOException($this->insertStatement->errorInfo(), $this->insertStatement->errorCode());
    }
  }

  /**
   * Метод обновляет запись в таблице.
   *
   * @param Entity $entity Обновляемая сущность с установленным свойством id.
   *
   * @throws \PDOException Выбрасывается в случае возникновения ошибки при 
   * выполнении запроса.
   */
  public function update(Entity $entity){
    if(!$this->updateStatement->execute(get_object_vars($entity))){
      throw new \PDOException($this->updateStatement->errorInfo(), $this->updateStatement->errorCode());
    }
  }

  /**
   * Метод удаляет запись из таблицы.
   *
   * @param int $id Идентификатор целевой записи.
   *
   * @throws \PDOException Выбрасывается в случае возникновения ошибки при 
   * выполнении запроса.
   */
  public function delete($id){
    if(!$this->deleteStatement->execute(['id' => $id])){
      throw new \PDOException($this->updateStatement->errorInfo(), $this->updateStatement->errorCode());
    }
  }
}
