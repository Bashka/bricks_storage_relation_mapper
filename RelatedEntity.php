<?php
namespace Bricks\Storage\Relation\Mapper;

/**
 * Представление перманентных данных (сущностей) с сохраняемой ссылкой на 
 * источник.
 *
 * @author Artur Sh. Mamedbekov
 */
class RelatedEntity extends Entity{
  /**
   * @var \PDOStatement Выражение, используемое для сохранения состояния 
   * сущности.
   */
  private $saveStatement;

  /**
   * Устанавливает выражение, используемое для сохранения состояния сущности.
   * Метод может быть вызван только единожды. Повторный вызов метода не 
   * изменяет установленного ранее выражения.
   *
   * @param \PDOStatement $saveStatement Выражение, используемое для сохранения 
   * состояния сущности.
   */
  public function setSaveStatement(\PDOStatement $saveStatement){
    if(is_null($this->saveStatement)){
      $this->saveStatement = $saveStatement;
    }
  }

  /**
   * Сохраняет состояние сущности.
   *
   * @throws \PDOException Выбрасывается в случае возникновения ошибки при 
   * выполнении запроса.
   */
  public function save(){
    $values = get_object_vars($this);
    unset($values['saveStatement']);
    if(!$this->saveStatement->execute($values)){
      throw new \PDOException($this->saveStatement->errorInfo(), $this->saveStatement->errorCode());
    }
  }
}
