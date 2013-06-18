<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class WorkflowTask extends ModelBase {
  protected $table = 'task';
  protected $primaryKey = 'wt_id';
  public $timestamps = false;

  // Mutators
  public function getSettingsAttribute($value)
  {
    return json_decode($value, TRUE);
  }

  public function setSettingsAttribute($value)
  {
    $this->attributes['settings'] = json_encode($value);
  }

  public function getDataAttribute($value)
  {
    return json_decode($value, TRUE);
  }

  public function setDataAttribute($value)
  {
    $this->attributes['data'] = json_encode($value);
  }
}
