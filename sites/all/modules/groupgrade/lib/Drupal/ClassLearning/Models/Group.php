<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Group extends ModelBase {
  protected $primaryKey = 'group_id';
  protected $table = 'groups';
}