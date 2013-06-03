<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Section extends ModelBase {
  protected $table = 'section';
  protected $primaryKey = 'section_id';
}