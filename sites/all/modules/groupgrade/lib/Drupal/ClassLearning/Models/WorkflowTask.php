<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model,
  Drupal\ClassLearning\Exception as ModelException;

class WorkflowTask extends ModelBase {
  protected $table = 'workflow_task';
}