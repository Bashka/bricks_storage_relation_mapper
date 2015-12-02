# Основные методы манипуляции сущностями

Класс _Mapper_ определяет только некоторые основные методы манипуляции 
сущностями. Дополнительные методы могут быть реализованы путем расширения этого 
класса и использования защищенных (protected) методов формирования сложных SQL 
запросов.

## Сохранение новой сущности

Для сохранения новой сущности в РСУБД используется метод _insert_, принимающий 
инициализированный экземпляр класса _Entity_ или его подклассов, но без 
установленного значения свойства _id_.

Пример создания сущности:
```php
use Bricks\Storage\Relation\Mapper\Mapper;
use Bricks\Storage\Relation\Mapper\Entity;

$mapper = new Mapper(...);

$entity = new Entity;
$entity->user = 5;
$entity->message = 'Hello world';

$mapper->insert($entity);
```

После сохранения новой сущности, свойство _id_ экземпляра этой сущности будет 
инициализировано значением первичного ключа добавленной записи.

## Восстановление сущности

Для восстановления состояния сущности из РСУБД используется метод _fetch_, 
принимающий значение идентификатора целевой сущности.

Пример восстановления сущности:
```php
use Bricks\Storage\Relation\Mapper\Mapper;
use Bricks\Storage\Relation\Mapper\Entity;

$mapper = new Mapper(...);

$entity = $mapper->fetch(5);
var_dump($entity->message); // 'Hello world'
```

## Обновление сущности

Для обновления состояния сущности в РСУБД используется метод _update_, 
принимающий инициализированный экземпляр класса _Entity_ или его подклассов, с 
установленным значением свойства _id_.

Пример обновления сущности:
```php
use Bricks\Storage\Relation\Mapper\Mapper;
use Bricks\Storage\Relation\Mapper\Entity;

$mapper = new Mapper(...);

$entity = $mapper->fetch(5);
$entity->message = 'Goodbye world';
$mapper->update($entity);
```

## Удаление сущности

Для удаления состояния сущности из РСУБД используется метод _delete_, 
принимающий значение идентификатора целевой сущности.

Пример удаления сущности:
```php
use Bricks\Storage\Relation\Mapper\Mapper;
use Bricks\Storage\Relation\Mapper\Entity;

$mapper = new Mapper(...);

$mapper->delete(5);
```
