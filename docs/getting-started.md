# Введение

Данный пакет реализует механизм объектно-реляционного преобразования данных для 
взаимодействия с РСУБД на базе PDO адаптера. Для этого используется класс 
_Mapper_, включающий основные методы манипуляции реляционными моделями в 
контексте объектных структур.

Пример использования:
```php
use Bricks\Storage\Relation\Mapper\Mapper;
use Bricks\Storage\Relation\Mapper\Entity;

$pdo = new PDO(...);
$mapper = new Mapper(
  $pdo, // Используемый адаптер
  'users', // Целевая таблица
  // Схема преобразования
  [
    'id' => 'id',
    'user' => 'user_id', // Проекция свойства user на поле user_id
    'message' => 'message,'
  ]
);

// Добавление сущности
$entity = new Entity;
$entity->user = 5;
$entity->message = 'Hello world';
$mapper->insert($entity);
var_dump($entity->id); // Установленный идентификатор новой записи

// Обновление сущности
$entity->message = 'Goodbye world';
$entity->update($entity);

// Восстановление сущности
$id = $entity->id;
$entity = $mapper->fetch($id);
var_dump($entity->message); // 'Goodbye world'

// Удаление сущности
$mapper->delete($entity->id);
```
