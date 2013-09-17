<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException,
  Carbon\Carbon,
  Drupal\ClassLearning\Workflow\Manager as WorkflowManager,
  Drupal\ClassLearning\Workflow\InternalCallback;


class WorkflowTask extends ModelBase {
  protected $table = 'task';
  protected $primaryKey = 'task_id';
  public $timestamps = false;

  /**
   * Add a trigger condition to the task
   * 
   * @param array
   */
  public function addTriggerCondition($data)
  {
    if (! isset($this->settings['trigger']))
      $this->settings['trigger'] = [];

    $this->settings['trigger'][] = $data;
  }

  /**
   * Get the trigger conditions
   *
   * @return array|void
   */
  public function getTriggerConditions()
  {
    if (! isset($this->settings['trigger']))
      return NULL;

    return $this->settings['trigger'];
  }

  /**
   * Add a expiration condition to the task
   * 
   * @param array
   */
  public function addExpireCondition($data)
  {
    if (! isset($this->settings['expire']))
      $this->settings['expire'] = [];

    $this->settings['expire'][] = $data;
  }

  /**
   * Get the expire conditions
   *
   * @return array|void
   */
  public function getExpireConditions()
  {
    if (! isset($this->settings['expire']))
      return NULL;

    return $this->settings['expire'];
  }

  /**
   * Check to see if the trigger conditions have been met
   *
   * @todo determine what to do if conditions are false
   * @return bool
   */
  public function triggerConditionsAreMet()
  {
    // TRUE if it's already done
    if ($task->status == 'triggered' OR $task->status == 'started')
      return TRUE;

    $conditions = $this->getTriggerConditions();

    if ($conditions == NULL) return FALSE;

    foreach ($conditions as $condition) {
      if (! $this->conditionMet($condition))
        return FALSE;
    }

    return TRUE;
  }

  /**
   * Check to see if the expiration conditions have been met
   *
   * @todo determine what to do if conditions are false
   * @return bool
   */
  public function expireConditionsAreMet()
  {
    $conditions = $this->getExpireConditions();

    if ($conditions == NULL) return FALSE;

    foreach ($conditions as $condition) {
      if (! $this->conditionMet($condition))
        return FALSE;
    }

    return TRUE;
  }

  /**
   * Handler for dispatched condition requests
   *
   * Takes a condition and determines if it has been met
   * This is using types of conditions and then handling them
   * dynamically.
   *
   * @param array
   * @return bool
   */
  protected function conditionMet($condition)
  {
    if (! isset($condition['type']))
      throw new ModelException('No condition type defined');

    switch ($condition['type'])
    {
      // See if tasks in a work flow are all at a certain status (all complete/expired/etc.)
      // Query by the task type
      case 'reference task status' :
        if (! isset($condition['task status']))
          throw new ModelException('Condition not defined for "type of tasks status"', 500, null, $condition);

        if (! isset($condition['task type']))
          throw new ModelException('Condition not defined for "type of tasks status"', 500, null, $condition);
        
        // Query the other workflow tasks
        $tasks = WorkflowTask::where('workflow_id', '=', $this->workflow_id)
          ->whereType($condition['task type'])
          ->get();

        // If there are no tasks found, the conditions cannot be met
        // They need to all meet a status to meet the conditions
        if ($tasks == NULL)
          return FALSE;

        foreach ($tasks as $task) :
          // See if they match the condition
          if ($task->status !== $condition['task status'])
            return FALSE;
        endforeach;

        break;

      // Check the value of another task and see if it's not (or is) in a certain range
      // Reference the other task by the task type
      case 'value of task out of range' :
      case 'value of task in range' :
        if (! isset($condition['task type']))
          throw new ModelException('Condition not defined for "value of task out of range"', 500, null, $condition);
        
        // Query the other workflow tasks
        $task = WorkflowTask::where('workflow_id', '=', $this->workflow_id)
          ->whereType($condition['task type'])
          ->first();

        // Task not found!
        if ($task == NULL) return FALSE;

        // There is no value to be in range
        if (! isset($task->data['value']))
          return FALSE;
        else
          $value = $task->data['value'];


        break;

      // Check if the value of a task meets an expected value
      case 'compare value of task' :
        if (! isset($condition['task type']))
          throw new ModelException('Task type not defined for "compare value of task"', 500, null, $condition);
        
        if (! isset($condition['compare value']))
          throw new ModelException('Compare value not defined for "compare value of task"', 500, null, $condition);
        
        // Query the other workflow tasks
        $task = WorkflowTask::where('workflow_id', '=', $this->workflow_id)
          ->whereType($condition['task type'])
          ->first();

        // Task not found!
        if ($task == NULL) return FALSE;

        // There is no value to be in range
        if (! isset($task->data['value']))
          return FALSE;
        else
          $value = $task->data['value'];

        // This is a soft compare, not a compare of types
        // Just of values. So passing these would pass:
        // 
        // 0 == NULL
        // FALSE == NULL
        if ($value !== $condition['compare value'])
          return FALSE;
        else
          return TRUE;
        break;

      // See if a certain time has elapsed since this task was triggered
      case 'time since trigger' :
        if (! isset($condition['task elapsed']))
          throw new ModelException('Task elapsed time condition not defined for "time since trigger"', 500, null, $condition);
        
        $time = Carbon::createFromFormat(\MYSQL_DATETIME, $this->start)
          ->addSeconds((int) $condition['task elapsed']);

        // There is still time left
        if ($time->isFuture())
          return FALSE;
        break;

      // One of the tasks is a certain status
      case 'check tasks for status' :
        if (! isset($condition['task status']) OR ! is_array($condition['task types']))
          throw new ModelException('Condition error', 500, null, $condition);

        foreach ($condition['task types'] as $type) :
          // Query the other workflow tasks
          $task = WorkflowTask::where('workflow_id', '=', $this->workflow_id)
            ->whereType($type)
            ->first();

          // Task found and status matched
          // Return it right here!
          if ($task !== NULL AND $task->status == $condition['task status'])
            return TRUE;
        endforeach;

        // Nothing found, so no good
        return FALSE;
        break;

      // This will cause a task to be trigged if all other tasks in the workflow are not triggered
      case 'first task trigger' :
        if (WorkflowTask::where('workflow_id', '=', $this->workflow_id)
          ->where('status', '!=', 'not triggered')
          ->where('task_id', '!=', $this->task_id)
          ->count() == 0)
          return TRUE;
        break;

      // Unknown type
      default :
        throw new ModelException('Workflow task condition does not have registered type', 500, null, $condition);
    }

    // If they've passed this far, they're good
    return TRUE;
  }

  /**
   * Trigger the task
   *
   * Be careful with the function!
   *
   * @param bool Force it to be triggered
   */
  public function trigger($force = false)
  {
    // Nothing to trigger
    if ($this->status !== 'not triggered' AND ! $force)
      return true;

    // Check and see if there's an error with triggering
    if (! $this->isInternal() AND $this->user_id == NULL)
      throw new ModelException(sprintf('No user assigned to task to trigger it. %s %s', print_r($this, true), print_r($this->settings, true)));

    // Update the status
    $this->status = 'triggered';
    $this->start = Carbon::now()->toDateTimeString();
    $this->force_end = $this->timeoutTime()->toDateTimeString();
    $this->save();

    // Notify user
    WorkflowManager::notifyUser('triggered', $this);

    // Lastly, the callback
    $callbackName = str_replace(' ', '_', $this->type);
    echo sprintf('Calling internal callback %s with data \n', $callbackName);
    var_dump($this);
    echo '\n';
    
    InternalCallback::$callbackName($this);
  }

  /**
   * Timeout of the task
   */
  public function timeout()
  {
    // Update the status
    $this->status = 'timed out';
    $this->end = Carbon::now()->toDateTimeString();

    // Notify user
    WorkflowManager::notifyUser('expired', $this);

    $this->save();
  }

  /**
   * Expire the task
   */
  public function expire()
  {
    // Update the status
    $this->status = 'expired';
    $this->end = Carbon::now()->toDateTimeString();
    $this->save();
  }

  /**
   * Completion of the task
   */
  public function complete()
  {
    // Update the status
    $this->end = Carbon::now()->toDateTimeString();
    $this->status = 'complete';
    $this->save();
  }

  /**
   * Get the timeout time for this task
   *
   * This would be when the task would be timed out fully. This is
   * calculated relative to the start time of the task. It assumes that `start`
   * is already set.
   * 
   * @return object Carbon\Carbon
   * @throws Drupal\ClassLearning\Exception
   */
  public function timeoutTime()
  {
    if ($this->start == NULL)
      throw new ModelException('Start time for instance cannot be null.');

    $duration = (isset($this->settings['duration'])) ? $this->settings['duration'] : 2;
    return Carbon::createFromFormat(MYSQL_DATETIME, $this->start)->addDays($duration);
  }

  /**
   * Retrieve the Carbon object for the force end
   *
   * @deprecated Use {@link WorkflowTask::forceEndTime()}
   * @return Carbon\Carbon
   */
  public function forceEndTime()
  {
    return $this->timeoutTime();
  }

  /**
   * Retrieve Upcoming Tasks for a User
   * 
   * @param int User Id
   * @param string
   */
  public static function queryByStatus($user, $status = 'pending')
  {
    $query = self::where('user_id', '=', $user);

    switch ($status)
    {
      case 'pending' :
        $query->whereIn('status', ['triggered', 'started', 'timed out'])
          ->orderBy('force_end', 'asc');
        break;

      case 'completed' :
        $query->whereIn('status', ['complete'/*, 'timed out'*/])
          ->orderBy('force_end', 'desc');
        break;

      // No filter
      case 'all' :
        $query->where('status', '!=', 'not triggered')
          ->where('status', '!=', 'expired')
          ->orderBy('force_end', 'desc');

        break;
    }
    return $query;
  }

  // ============================
  // Mutators
  // ============================
  public function getSettingsAttribute($value)
  {
    if ($value == '') return [];
    return json_decode($value, TRUE);
  }

  public function setSettingsAttribute($value)
  {
    $this->attributes['settings'] = json_encode($value);
  }

  public function getDataAttribute($value)
  {
    if ($value == '') return [];
    return json_decode($value, TRUE);
  }

  public function setDataAttribute($value)
  {
    $this->attributes['data'] = json_encode($value);
  }

  /**
   * Set a Data point
   * 
   * @param string Key
   * @param mixed Value
   */
  public function setData($key, $value = NULL)
  {
    $data = $this->data;
    $data[$key] = $value;
    $this->data = $data;
  }

  /**
   * Set a Setting
   * 
   * @param string Key
   * @param mixed Value
   */
  public function setSetting($key, $value = NULL)
  {
    $settings = $this->settings;
    $settings[$key] = $value;
    $this->settings = $settings;
  }

  /**
   * Get the Human Task Type
   *
   * @return string
   */
  public function humanTask()
  {
    return WorkflowManager::humanTaskName($this->type);
  }

  /**
   * Determine if this workflow task type is internal from task settings
   * 
   * @return boolean
   */
  public function isInternal()
  {
    return (isset($this->settings['internal']) AND $this->settings['internal']);
  }

  // =============================
  // Relations
  // =============================
  public function workflow()
  {
    return $this->belongsTo('Drupal\ClassLearning\Models\Workflow');
  }

  public function assignmentSection()
  {
    return $this->workflow()->first()->assignmentSection();
  }

  public function section()
  {
    return $this->assignmentSection()->first()->section();
  }

  public function assignment()
  {
    return $this->assignmentSection()->first()->assignment();
  }
}
