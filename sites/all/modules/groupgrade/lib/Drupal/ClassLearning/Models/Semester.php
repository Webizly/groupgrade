<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Semester extends ModelBase {
  protected $primaryKey = 'semester_id';
  protected $table = 'semester';
}