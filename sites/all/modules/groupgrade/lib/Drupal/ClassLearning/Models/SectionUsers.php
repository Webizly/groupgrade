<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class SectionUsers extends ModelBase {
  protected $table = 'section_user';
  protected $primaryKey = 'su_id';
  public $timestamps = false;

  public function user()
  {
    return \user_load($this->user_id);
  }
}