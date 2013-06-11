<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Course extends ModelBase {
  protected $primaryKey = 'course_id';
  protected $table = 'course';
  public $timestamps = false;
}