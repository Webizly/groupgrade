<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Task extends ModelBase {
  protected $primaryKey = 'task_id';
  protected $table = 'tasks';
}