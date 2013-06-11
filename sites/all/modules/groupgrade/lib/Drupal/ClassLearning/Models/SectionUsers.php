<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Section_Users extends ModelBase {
  protected $table = 'section_user';
  protected $primaryKey = 'su_id';
  public $timestamps = false;
}