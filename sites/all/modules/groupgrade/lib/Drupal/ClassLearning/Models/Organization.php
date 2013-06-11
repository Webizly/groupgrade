<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Organization extends ModelBase {
  protected $table = 'organization';
  protected $primaryKey = 'organization_id';
  public $timestamps = false;
  public function courses()
  {
    return $this->hasMany('Drupal\ClassLearning\Models\Course');
  }
}