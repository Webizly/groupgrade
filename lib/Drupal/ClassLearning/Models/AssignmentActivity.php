<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Models\Assignment,
  Drupal\ClassLearning\Exception as ModelException;

class AssignmentActivity extends ModelBase {
  protected $primaryKey = 'A_id';
  protected $table = 'assignment_activity';
  public $timestamps = false;
  
  
}
