<?php
namespace Bricks\Storage\Relation\Mapper;
require_once('Entity.php');
require_once('RelatedEntity.php');

/**
 * Сущность, используемая для тестирования.
 *
 * @author Artur Sh. Mamedbekov
 */
class RelatedEntityMock extends RelatedEntity{
  public $login;

  public $pass;
}
