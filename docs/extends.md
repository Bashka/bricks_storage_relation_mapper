# Реализация собственных механизмов манпуляции сущностями

Для реализации сложных механизмов манипуляции сущностями необходимо расширить 
класс _Mapper_ собственными реализациями. Для этого классом _Mapper_ 
предоставляются следующие защищенные (protected) методы:

- `scheme(property)` - получение имени поля таблицы по имени свойства сущности
- `convert(sql)` - преобразование переданного SQL запроса с заменой всех 
  вхождений свойств вида `!имяПоля` на имена соответствующих им полей
- `join(type, mapper, foreign, target)` - формирование строки объединения (JOIN)
- `select(condition, params)` - выполнение SQL запроса для восстановления 
  массива сущностей с использованием данного условия. Условие предварительно 
  конвертируется методом _convert_ и кэшируется

Далее приведены несколько примеров расширения класса _Mapper_ для реализации 
различных механизмов доступа к данным. Все примеры используют следующие 
сущности:

```php
use Bricks\Storage\Relation\Mapper\Entity;

class User extends Entity{
  public $login;
}

class Message extends Entity{
  public $user;
  public $message;
}
```

## Предопределение конфигурации доступа

Если для конкретной сущности реализуется отдельный подкласс класса _Mapper_, 
имеет смысл предопределить конфигурации доступа в конструкторе этого класса:

```php
use Bricks\Storage\Relation\Mapper\Mapper;

class UserMapper extends Mapper{
  public function __construct($pdo){
    parent::__construct($pdo, 'users', [
      'id' => 'id',
      'login' => 'login',
    ]);
    $this->prototype('User');
  }
}

class MessageMapper extends Mapper{
  public function __construct($pdo){
    parent::__construct($pdo, 'messages', [
      'id' => 'id',
      'user' => 'user_id',
      'message' => 'message',
    ]);
    $this->prototype('Message');
  }
}
```

## Восстановление массива сущностей по значению свойства

```php
class MessageMapper extends Mapper{
  ...

  public function fetchFromUser($userId){
    return $this->select('WHERE !user = ?', [$userId]);
  }
}

$mapper = new UserMapper(...);
$user = $mapper->fetch(5);

$mapper = new MessageMapper(...);
$messages = $mapper->fetchFromUser($user->id);
```

## Восстановление массива сущностей по значению свойства связанной сущности

```php
class MessageMapper extends Mapper{
  ...

  public function fetchFromLogin($login, UserMapper $userMapper){
    $join = $this->join('INNER', $userMapper, 'user', 'id');
    $condition = 'WHERE ' . $userMapper->convert('!login = ?');
    return $this->select($join . ' ' . $condition, [$login]);
  }
}

$mapper = new UserMapper(...);
$user = $mapper->fetch(5);

$mapper = new MessageMapper(...);
$messages = $mapper->fetchFromLogin($user->login);
```

## Пагинация

```php
class MessageMapper extends Mapper{
  ...

  public function fetchFromUser($userId, $limit = 10, $page = 1){
    $start = (($page - 1) * $limit);
    return $this->select(
      'WHERE !user = ? LIMIT ' . $start . ',' . $limit,
      [$userId]
    );
  }
}

$mapper = new UserMapper(...);
$user = $mapper->fetch(5);

$mapper = new MessageMapper(...);
$messages = $mapper->fetchFromLogin($user->id, 10, 5);
```
