<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Workflow extends ModelBase {
  protected $primaryKey = 'workflow_id';
  protected $table = 'workflow';
  public $timestamps = false;
  
  public function tasks()
  {
    return $this->hasMany('Drupal\ClassLearning\Models\WorkflowTask');
  }

  public function assignmentSection()
  {
    return $this->belongsTo('Drupal\ClassLearning\Models\AssignmentSection', 'assignment_id');
  }

  // ============================
  // Mutators
  // ============================
  public function getDataAttribute($value)
  {
    if ($value == '') return [];
    return json_decode($value, TRUE);
  }

  public function setDataAttribute($value)
  {
    $this->attributes['data'] = json_encode($value);
  }

  /**
   * Set a Data point
   * 
   * @param string Key
   * @param mixed Value
   */
  public function setData($key, $value = NULL)
  {
    $data = $this->data;
    $data[$key] = $value;
    $this->data = $data;
  }
}
