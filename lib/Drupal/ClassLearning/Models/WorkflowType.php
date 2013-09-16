<?php namespace Drupal\ClassLearning\Models;

use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

/**
 * Workflow Type Storage
 *
 * Used to store the workflow structure
 */
class WorkflowType extends ModelBase {
  protected $table = 'workflow_types';
  protected $primaryKey = 'workflowtype_id';
  public $timestamps = true;
}