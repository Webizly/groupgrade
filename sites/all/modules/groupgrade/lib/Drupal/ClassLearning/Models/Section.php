<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Section extends ModelBase {
  protected $table = 'section';
  protected $primaryKey = 'section_id';
  public $timestamps = false;
  
  public function course()
  {
    return $this->belongsTo('Drupal\ClassLearning\Models\Course');
  }

  public function users()
  {
    return $this->hasMany('Drupal\ClassLearning\Models\SectionUsers');
  }
}