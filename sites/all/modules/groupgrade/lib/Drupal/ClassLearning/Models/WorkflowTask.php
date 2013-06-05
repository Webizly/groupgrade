<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class WorkflowTask extends ModelBase {
  protected $table = 'task';
  protected $primaryKey = 'wt_id';
}