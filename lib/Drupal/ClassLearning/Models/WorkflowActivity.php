<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Models\Assignment,
  Drupal\ClassLearning\Exception as ModelException;

class TaskActivity extends ModelBase {
  protected $primaryKey = 'WA_id';
  protected $table = 'workflow_activity';
  public $timestamps = false;
  
  
}