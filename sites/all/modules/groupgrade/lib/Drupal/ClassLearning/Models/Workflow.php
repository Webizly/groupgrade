<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Workflow extends ModelBase {
  protected $primaryKey = 'workflow_id';
  protected $table = 'workflow';
}