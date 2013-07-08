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
      $headers = array('Assignment', 'Task', /*'Problem',*/ 'Date Completed');

      if (count($tasks) > 0) : foreach($tasks as $task) :
        $rowt = [];
        $rowt[] = sprintf(
        '<a href="%s">%s</a>',
          url('class/task/'.$task->task_id), $task->assignment()->first()->assignment_title
        );
        $rowt[] = $task->type;
        $rowt[] = ($task->end == NULL) ? 'n/a' : gg_time_human($task->end);

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

  // Permissions
  if ($task == NULL OR ! in_array($task->status, ['triggered', 'started', 'complete']))
    return drupal_not_found();

  if ($task->status !== 'complete' AND (int) $task->user_id !== (int) $user->uid)
    drupal_not_found();

  $anon = ((int) $task->user_id !== (int) $user->uid AND ! user_access('administer')) ? TRUE : FALSE;

  // Related Information
  $assignment = $task->assignment()->first();

  $return = '';
  drupal_set_title(t(sprintf('%s: %s', ucwords($task->type), $assignment->assignment_title)));

  $return .= sprintf('<p class="summary">%s</p>', $assignment->assignment_description);
  $return .= '<p><strong>'.ucwords($task->type).'</strong></p>';

  $params = [];
  $params['task'] = $task;
  $params['anon'] = $anon;
  
  if ($task->type == 'edit problem')
  {
    $params['previous task'] = Task::where('workflow_id', '=', $task->workflow_id)
      ->whereType('create problem')
      ->first();
  } else {
    // Automatically include the edited problem working with
    $params['problem'] = Task::where('workflow_id', '=', $task->workflow_id)
      ->whereType('edit problem')
      ->first();
  }
  
  if ($task->type == 'grade solution' OR $task->type == 'dispute' OR $task->type == 'resolve dispute')
  {
    $params['solution'] = Task::where('workflow_id', '=', $task->workflow_id)
      ->whereType('create solution')
      ->first();
  }

  if ($task->type == 'create solution')
  {
    $params['previous task'] = Task::where('workflow_id', '=', $task->workflow_id)
      ->whereType('edit problem')
      ->first();
  }

  $params['workflow'] = $task->workflow()->first();
  $params['edit'] = ($task->status == 'triggered' OR $task->status == 'started');

  $form = drupal_get_form('gg_task_'.str_replace(' ', '_', $task->type).'_form', $params);
  $return .= drupal_render($form);
  return $return;
}

/**
 * Impliments a create problem form
 */
function gg_task_create_problem_form($form, &$form_state, $params) {
  $items = [];

  if (! $params['edit']) :
    $items['edited problem lb'] = [
      '#markup' => '<strong>Submitted Problem:</strong>',
    ];
    $items['edited problem'] = [
      '#type' => 'item',
      '#markup' => nl2br($params['task']->data['problem']),
    ];

    return $items;
  endif;

  $items['body'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#default_value' => (isset($params['task']->data['problem'])) ? $params['task']->data['problem'] : '',
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
  $task = $form_state['build_info']['args'][0]['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
  
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

/**
 * Impliments a edit problem form
 */
function gg_task_edit_problem_form($form, &$form_state, $params) {
  $problem = '';
  $problem = $params['previous task']->data['problem'];

  if (! empty($params['task']->data['problem']))
    $problem = $params['task']->data['problem'];

  $items = [];
  $items['original problem'] = [
    '#markup' => '<p><strong>Original Problem:</strong></p><p>'.nl2br($params['previous task']->data['problem']).'</p><hr />'
  ];

  if (! $params['edit']) :
    $items['edited problem lb'] = [
      '#markup' => '<strong>Submitted Problem:</strong>',
    ];
    $items['edited problem'] = [
      '#type' => 'item',
      '#markup' => nl2br($problem),
    ];

    return $items;
  endif;

  $items['body'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#default_value' => $problem,
  ];

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Edited Problem For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Edited Problem',
  ];
  return $items;
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_edit_problem_form_submit($form, &$form_state) {
  $task = $form_state['build_info']['args'][0]['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();

  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  $task->setDataAttribute(['problem' =>  $form['body']['#value']]);
  $task->status = ($save) ? 'started' : 'completed';
  $task->save();

  if (! $save)
    $task->complete();
  
  drupal_set_message(sprintf('Edited problem %s.', ($save) ? 'saved' : 'completed'));

  if (! $save)
    return drupal_goto('class/default/completed');
}

/**
 * Impliments a edit problem form
 */
function gg_task_create_solution_form($form, &$form_state, $params) {
  $problem = '';
  $problem = $params['previous task']->data['problem'];

  if (! empty($params['task']->data['problem']))
    $problem = $params['task']->data['problem'];

  $items = [];
  $items['original problem'] = [
    '#markup' => '<p><strong>Problem:</strong></p><p>'.nl2br($params['previous task']->data['problem']).'</p><hr />'
  ];

  if (! $params['edit']) :
    $items['problem lb'] = [
      '#markup' => '<strong>Submitted Solution:</strong>',
    ];
    $items['problem'] = [
      '#type' => 'item',
      '#markup' => nl2br($problem),
    ];

    return $items;
  endif;

  $items['body'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#default_value' => (isset($params['task']->data['solution'])) ? $params['task']->data['solution'] : '',
  ];

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Solution For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Solution',
  ];
  return $items;
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_create_solution_form_submit($form, &$form_state) {
  $task = $form_state['build_info']['args'][0]['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
  
  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  $task->setDataAttribute(['solution' =>  $form['body']['#value']]);
  $task->status = ($save) ? 'started' : 'completed';
  $task->save();

  if (! $save)
    $task->complete();
  
  drupal_set_message(sprintf('Solution %s.', ($save) ? 'saved' : 'completed'));

  if (! $save)
    return drupal_goto('class/default/completed');
}



/**
 * Impliments a edit problem form
 */
function gg_task_grade_solution_form($form, &$form_state, $params) {
  $problem = $params['problem'];
  $solution = $params['solution'];
  $task = $params['task'];

  $items = [];
  $items['problem'] = [
    '#markup' => '<h4>Problem</h4><p>'.nl2br($problem->data['problem']).'</p><hr />',
  ];
  $items['solution'] = [
    '#markup' => '<h4>Solution</h4><p>'.nl2br($solution->data['solution']).'</p><hr />',
  ];

  if (! $params['edit']) :
    $items['grade lb'] = [
      '#markup' => '<strong>Grade:</strong>',
    ];
    $items['grade'] = [
      '#type' => 'item',
      '#markup' => $task->data['grade'],
    ];

    $items['justice lb'] = [
      '#markup' => '<strong>Grade Justification:</strong>',
    ];
    $items['justice'] = [
      '#type' => 'item',
      '#markup' => nl2br($task->data['justification']),
    ];

    return $items;
  endif;

  $items['grade'] = [
    '#type' => 'textfield',
    '#title' => 'Grade (0-100)',
    '#required' => true,
    '#default_value' => (isset($task->data['grade'])) ? $task->data['grade'] : '',
  ];

  $items['justification'] = [
    '#type' => 'textarea',
    '#title' => 'Grade Justification',
    '#required' => true,
    '#default_value' => (isset($task->data['justification'])) ? $task->data['justification'] : '',
  ];
  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Grade For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Grade',
  ];
  return $items;
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_grade_solution_form_submit($form, &$form_state) {
  $params = $form_state['build_info']['args'][0];
  $task = $params['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
    
  $grade = (int) $form['grade']['#value'];
  if ($grade !== abs($grade) OR $grade < 0 OR $grade > 100)
    return drupal_set_message(t('Invalid grade: '.$grade));
  
  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  $task->setDataAttribute([
    'grade' =>  (int) $grade,
    'justification' => $form['justification']['#value']
  ]);

  $task->status = ($save) ? 'started' : 'completed';
  $task->save();

  if (! $save)
    $task->complete();
  
  drupal_set_message(sprintf('Grade %s.', ($save) ? 'saved' : 'submitted'));

  if (! $save)
    return drupal_goto('class/default/completed');
}

/**
 * Dispute
 */
function gg_task_dispute_form($form, &$form_state, $params)
{
  $items = [];
  $items[] = [
    '#markup' => '<h3>'.t('Grade Recieved').': '.$params['workflow']->data['grade'].'%',
  ];
  $items[] = [
    '#markup' => '<h4>Problem:</h4>'
    .'<p>'.nl2br($params['problem']->data['problem']).'</p>'
  ];

  $items[] = [
    '#markup' => '<h4>Solution:</h4>'
    .'<p>'.nl2br($params['solution']->data['solution']).'</p><hr />'
  ];

  if (! $params['edit']) :
    $items[] = [
      '#markup' => '<p>You already opted to <strong>'.(($params['task']->data['value']) ? 'dispute' : 'not dispute').'</strong>.</p>'
    ];
    return $items;
  endif;

  $items[] = [
    '#markup' => '<p>Would you like to dispute this grade?</p>',
  ];

  $items['dispute'] = [
    '#type' => 'submit',
    '#value' => 'Dispute',
  ];
  $items['no-dispute'] = [
    '#type' => 'submit',
    '#value' => 'Do Not Dispute',
  ];
  return $items;
}

function gg_task_dispute_form_submit($form, &$form_state)
{
  $params = $form_state['build_info']['args'][0];
  $task = $params['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();

  $dispute = ($form_state['clicked_button']['#id'] == 'edit-dispute') ? true : false;
  $task->setData('value', $dispute);
  $task->complete();

  drupal_set_message(t('Your selection has been submitted.'));
  return drupal_goto('class/default/completed');
}



/**
 * Resolve Dispute
 */
function gg_task_resolve_dispute_form($form, &$form_state, $params)
{
  $task = $params['task'];

  $items = [];
  $items[] = [
    '#markup' => '<h3>'.t('Grade Recieved').': '.$params['workflow']->data['grade'].'%',
  ];
  $items[] = [
    '#markup' => '<h4>Problem:</h4>'
    .'<p>'.nl2br($params['problem']->data['problem']).'</p>'
  ];

  $items[] = [
    '#markup' => '<h4>Solution:</h4>'
    .'<p>'.nl2br($params['solution']->data['solution']).'</p><hr />'
  ];

  if (! $params['edit']) :
    $items['grade lb'] = [
      '#markup' => '<strong>Grade:</strong>',
    ];
    $items['grade'] = [
      '#type' => 'item',
      '#markup' => $task->data['grade'],
    ];

    $items['justice lb'] = [
      '#markup' => '<strong>Grade Justification:</strong>',
    ];
    $items['justice'] = [
      '#type' => 'item',
      '#markup' => nl2br($task->data['justification']),
    ];
    return $items;
  endif;

  $items['grade'] = [
    '#type' => 'textfield',
    '#title' => 'Grade (0-100)',
    '#required' => true,
    '#default_value' => (isset($task->data['grade'])) ? $task->data['grade'] : '',
  ];

  $items['justification'] = [
    '#type' => 'textarea',
    '#title' => 'Grade Justification',
    '#required' => true,
    '#default_value' => (isset($task->data['justification'])) ? $task->data['justification'] : '',
  ];
  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Grade For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Grade',
  ];
  return $items;
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_resolve_dispute_form_submit($form, &$form_state) {
  $params = $form_state['build_info']['args'][0];
  $task = $params['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
  
  $grade = (int) $form['grade']['#value'];
  if ($grade !== abs($grade) OR $grade < 0 OR $grade > 100)
    return drupal_set_message(t('Invalid grade: '.$grade));

  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  $task->setData('grade', (int) $grade);
  $task->setData('justification', $form['justification']['#value']);

  $task->status = ($save) ? 'started' : 'completed';
  $task->save();

  drupal_set_message(sprintf('Grade %s.', ($save) ? 'saved' : 'submitted'));

  if (! $save) :
    $task->complete();

    // Save to the workflow
    $params['workflow']->setData('grade', $grade);

    return drupal_goto('class/default/completed');
  endif;
}
