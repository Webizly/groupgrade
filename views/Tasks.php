<?php
use Drupal\ClassLearning\Models\WorkflowTask as Task,
  Drupal\ClassLearning\Models\Workflow;

function groupgrade_tasks_dashboard() {
  return groupgrade_tasks_view_specific('pending');
}

function groupgrade_tasks_view_specific($specific = '') {
  global $user;
  $tasks = Task::queryByStatus($user->uid, $specific)->get();
  $rows = [];

  switch($specific)
  {
    case 'pending' :
      $headers = array('Due Date', 'Type', 'Course', 'Assignment', 'Problem');
      

      if (count($tasks) > 0) : foreach($tasks as $task) :
        $row_t = [];
        $row_t[] = sprintf(
          '<a href="%s">%s</a>',
          url('class/task/'.$task->task_id), groupgrade_carbon_span($task->timeoutTime())
        );

        $row_t[] = $task->type;
        //$row_t[] = $task->status;

        $section = $task->section()->first();
        $course = $section->course()->first();
        $assignment = $task->assignment()->first();

        $row_t[] = sprintf('%s &mdash; %s', $course->course_name, $section->section_name);
        $row_t[] = $assignment->assignment_title;
        $row_t[] = '';

        $rows[] = $row_t;
      endforeach; endif;
      break;

    // All/completed tasks
    default :
      $headers = array('Assignment', 'Task', 'Problem', 'Date Completed');

      if (count($tasks) > 0) : foreach($tasks as $task) :
        $rowt = [];
        $rowt[] = $task->assignment()->first()->assignment_title;
        $rowt[] = $task->type;
        $rowt[] = '';
        $rowt[] = $task->end;

        $rows[] = $rowt;
      endforeach; endif;
      break;
  }

  return theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'attributes' => ['width' => '100%'],
    'empty' => 'No tasks found.',
  ));
}

/**
 * View a specific task
 * 
 */
function groupgrade_view_task($task_id, $action = 'default')
{
  global $user;

  $task = Task::find($task_id);

  if ($task == NULL OR (int) $task->user_id !== (int) $user->uid)
    return drupal_not_found();

  // Related Information
  $assignment = $task->assignment()->first();

  $return = '';
  drupal_set_title(t(sprintf('%s: %s', ucwords($task->type), $assignment->assignment_title)));

  $return .= sprintf('<p class="summary">%s</p>', $assignment->assignment_description);
  $return .= '<p><strong>'.ucwords($task->type).'</strong></p>';

  $form = drupal_get_form('gg_task_'.str_replace(' ', '_', $task->type).'_form', $task);
  $return .= drupal_render($form);
  return $return;
}

/**
 * Impliments a create problem form
 */
function gg_task_create_problem_form($form, &$form_state, $task) {
  $items = [];

  $items['body'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#default_value' => (isset($task->data['problem'])) ? $task->data['problem'] : '',
  ];

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Problem For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Problem',
  ];
  return $items;
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_create_problem_form_submit($form, &$form_state) {
  $task_id = $form_state['build_info']['args'][0]->task_id;
  $task = Task::find($task_id);

  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  $task->setDataAttribute(['problem' =>  $form['body']['#value']]);
  $task->status = ($save) ? 'started' : 'completed';
  $task->save();

  if (! $save)
    $task->complete();
  
  drupal_set_message(sprintf('Problem %s.', ($save) ? 'saved' : 'completed'));

  if (! $save)
    return drupal_goto('class/default/completed');
}
