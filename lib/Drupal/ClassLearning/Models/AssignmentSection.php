<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class AssignmentSection extends ModelBase {
  protected $primaryKey = 'asec_id';
  protected $table = 'assignment_section';
  public $timestamps = false;
}
