# Сущности со ссылкой на источник

Класс _RelatedEntity_ расширяет возможности класса _Entity_ добавляя ему метод 
`save`. Этот метод может быть использован после изменения состояния сущности для 
его сохранения в БД. В остальном данный класс полностью совместим со своим 
родителем и может быть использован в качестве прототипа класса _Mapper_.

Пример объявления сущности со ссылкой на источник:
```php
use Bricks\Storage\Relation\Mapper\RelatedEntity;

class Message extends RelatedEntity{
  public $user;

  public $message;
}
```

При восстановлении состояния такой сущности с помощью методов `fetch` или 
`select`, ее метод `save` может быть вызван для сохранения изменений:
```php
$mapper = new Mapper(...);
$mapper->prototype('Message');
$message = $mapper->fetch(5);
$message->message = 'new value';
$message->save();
```
