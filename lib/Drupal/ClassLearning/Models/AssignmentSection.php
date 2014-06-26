<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class AssignmentSection extends ModelBase {
  protected $primaryKey = 'asec_id';
  protected $table = 'assignment_section';
  public $timestamps = false;

  public function section()
  {
    return $this->belongsTo('Drupal\ClassLearning\Models\Section');
  }

  public function assignment()
  {
    return $this->belongsTo('Drupal\ClassLearning\Models\Assignment');
  }
}
