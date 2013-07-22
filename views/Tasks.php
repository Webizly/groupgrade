<?php
use Drupal\ClassLearning\Models\WorkflowTask as Task,
  Drupal\ClassLearning\Models\Workflow,
  Drupal\ClassLearning\Common\Accordion;

function groupgrade_tasks_dashboard() {
  return groupgrade_tasks_view_specific('pending');
}

function groupgrade_tasks_view_specific($specific = '') {
  global $user;
  $tasks = Task::queryByStatus($user->uid, $specific)->get();
  $rows = [];
  $return = '';

  switch($specific)
  {
    case 'pending' :
      $headers = array('Due Date', 'Type', 'Course', 'Assignment');
      
      $return .= sprintf('<p>%s</p>', t('These are the pending tasks you need to do. Click on a due date to open the task.'));
      if (count($tasks) > 0) : foreach($tasks as $task) :
        $row_t = [];
        $row_t[] = sprintf(
          '<a href="%s">%s</a>',
          url('class/task/'.$task->task_id), groupgrade_carbon_span($task->timeoutTime())
        );

        $row_t[] = t(ucwords($task->type));

        $section = $task->section()->first();
        $course = $section->course()->first();
        $assignment = $task->assignment()->first();
        $semester = $section->semester()->first();

        $row_t[] = sprintf('%s &mdash; %s &mdash; %s', 
          $course->course_name, 
          $section->section_name,
          $semester->semester_name
        );

        $row_t[] = $assignment->assignment_title;

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

  $return .= theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'attributes' => ['width' => '100%'],
    'empty' => 'No tasks found.',
  ));

  return $return;
}

/**
 * View a specific task
 *
 * @param int Task ID
 * @param string How to display it (default = everything, overview = Just submitted data, no other info)
 */
function groupgrade_view_task($task_id, $action = 'display')
{
  global $user;

  if (is_object($task_id))
    $task = $task_id;
  else
    $task = Task::find($task_id);

  // Permissions
  if ($task == NULL OR ! in_array($task->status, ['triggered', 'started', 'complete']))
    return drupal_not_found();

  if ($task->status !== 'complete' AND (int) $task->user_id !== (int) $user->uid)
    return drupal_not_found();

  $anon = ((int) $task->user_id !== (int) $user->uid AND ! user_access('administer')) ? TRUE : FALSE;

  // Related Information
  $assignment = $task->assignment()->first();

  $return = '';
  drupal_set_title(t(sprintf('%s: %s', ucwords($task->type), $assignment->assignment_title)));

  if ($action == 'display') :
    $return .= sprintf('<p><a href="%s">%s %s</a>', url('class'), HTML_BACK_ARROW, t('Back to Tasks'));

    // Course information
    $section = $task->section()->first();
    $course = $section->course()->first();
    $semester = $section->semester()->first();

    $return .= sprintf('<p><strong>%s</strong>: %s &mdash; %s &mdash; %s',
      t('Course'),
      $course->course_name,
      $section->section_name,
      $semester->semester_name
    );

    $return .= '<hr />';
    
    $return .= sprintf('<h4>%s</h4>', t('Assignment Description'));
    $return .= sprintf('<p class="summary">%s</p>', nl2br($assignment->assignment_description));
    $return .= '<hr />';
    $return .= '<p><strong>'.t(ucwords($task->type)).'</strong></p>';
  endif;

  $params = [];
  $params['task'] = $task;
  $params['anon'] = $anon;
  $params['action'] = $action;

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
  
  if ($task->type == 'grade solution' OR $task->type == 'dispute' OR $task->type == 'resolve dispute' OR $task->type == 'resolution grader')
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
      '#markup' => '<strong>'.t('Problem').':</strong>',
    ];
    $items['edited problem'] = [
      '#type' => 'item',
      '#markup' => nl2br($params['task']->data['problem']),
    ];

    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

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
  
  drupal_set_message(sprintf('%s %s.', t('Problem'), ($save) ? 'saved (You must submit this still to complete the task.)' : 'created'));

  if (! $save)
    return drupal_goto('class');
}

/**
 * Impliments a edit problem form
 */
function gg_task_edit_problem_form($form, &$form_state, $params) {
  $problem = $comment = '';
  $problem = $params['previous task']->data['problem'];

  if (! empty($params['task']->data['problem']))
    $problem = $params['task']->data['problem'];

  if (! empty($params['task']->data['comment']))
    $comment = $params['task']->data['comment'];

  $items = [];

  if ($params['action'] == 'display')
    $items['original problem'] = [
      '#markup' => sprintf('<p><strong>%s:</strong></p><p>%s</p><hr />',
        t('Original Problem'),
        nl2br($params['previous task']->data['problem'])
      )
    ];

  if (! $params['edit']) :
    $items['edited problem lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Edited Problem')),
    ];
    $items['edited problem'] = [
      '#type' => 'item',
      '#markup' => nl2br($problem),
    ];

    $items['comment lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Edited Comments')),
    ];
    $items['comment'] = [
      '#type' => 'item',
      '#markup' => (empty($comment)) ? sprintf('<em>%s</em>', t('none')) : nl2br($comment),
    ];

    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  $items['body'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#default_value' => $problem,
  ];

  $items['comment'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#default_value' => $comment,
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
  $task->setDataAttribute([
    'problem' =>  $form['body']['#value'],
    'comment' => $form['comment']['#value'],
  ]);
  $task->status = ($save) ? 'started' : 'completed';
  $task->save();

  if (! $save)
    $task->complete();
  
  drupal_set_message(sprintf('Edited problem %s.', ($save) ? 'saved (You must submit this still to complete the task.)' : 'completed'));

  if (! $save)
    return drupal_goto('class');
}

/**
 * Impliments a edit problem form
 */
function gg_task_create_solution_form($form, &$form_state, $params) {
  $problem = (isset($params['task']->data['solution'])) ? $params['task']->data['solution'] : '';
  $items = [];

  if ($params['action'] == 'display')
    $items['original problem'] = [
      '#markup' => '<p><strong>'.t('Problem').':</strong></p><p>'.nl2br($params['previous task']->data['problem']).'</p><hr />'
    ];

  if (! $params['edit']) :
    $items['problem lb'] = [
      '#markup' => '<strong>'.t('Solution').':</strong>',
    ];
    $items['problem'] = [
      '#type' => 'item',
      '#markup' => nl2br($problem),
    ];

    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

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
  
  drupal_set_message(sprintf(t('Solution').' %s.', ($save) ? 'saved (You must submit this still to complete the task.)' : 'completed'));

  if (! $save)
    return drupal_goto('class');
}

/**
 * Impliments a edit problem form
 */
function gg_task_grade_solution_form($form, &$form_state, $params) {
  $problem = $params['problem'];
  $solution = $params['solution'];
  $task = $params['task'];

  $items = [];

  if (! $params['edit']) :
    $items['grade lb'] = [
      '#markup' => '<strong>'.t('Grade').':</strong>',
    ];
    $items['grade'] = [
      '#type' => 'item',
      '#markup' => $task->data['grade'],
    ];

    $items['justice lb'] = [
      '#markup' => '<strong>'.t('Grade Justification').':</strong>',
    ];
    $items['justice'] = [
      '#type' => 'item',
      '#markup' => nl2br($task->data['justification']),
    ];

    return $items;
  endif;

  $items['problem'] = [
    '#markup' => '<h4>'.t('Problem').'</h4><p>'.nl2br($problem->data['problem']).'</p><hr />',
  ];
  $items['solution'] = [
    '#markup' => '<h4>'.t('Solution').'</h4><p>'.nl2br($solution->data['solution']).'</p><hr />',
  ];

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

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
  
  drupal_set_message(sprintf(t('Grade').' %s.', ($save) ? 'saved (You must submit this still to complete the task.)' : 'submitted'));

  if (! $save)
    return drupal_goto('class');
}

/**
 * Dispute
 */
function gg_task_dispute_form($form, &$form_state, $params)
{
  $items = [];

  if (! $params['edit']) :
    $items[] = [
      '#markup' => sprintf('<p>%s <strong>%s</strong>.</p>',
        t('The solution grade was'),
        (($params['task']->data['value']) ? 'disputed' : 'not disputed')
      )
    ];
    return $items;
  endif;

  $items[] = [
    '#markup' => '<h3>'.t('Grade Recieved').': '.$params['workflow']->data['grade'].'%',
  ];
  $items[] = [
    '#markup' => sprintf('<h4>%s:</h4><p>%s</p>',
      t('Problem'),
      nl2br($params['problem']->data['problem'])
    )
  ];

  $items[] = [
    '#markup' => sprintf('<h4>%s:</h4><p>%s</p><hr />',
      t('Solution'),
      nl2br($params['solution']->data['solution'])
    )
  ];

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  $items[] = [
    '#markup' => sprintf('<p>%s</p>', t('Would you like to dispute this grade?')),
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
  return drupal_goto('class');
}



/**
 * Resolve Dispute
 */
function gg_task_resolve_dispute_form($form, &$form_state, $params)
{
  $task = $params['task'];

  $items = [];

  if (! $params['edit']) :
    $items['grade lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Grade'))
    ];
    $items['grade'] = [
      '#type' => 'item',
      '#markup' => $task->data['grade'],
    ];

    $items['justice lb'] = [
      '#markup' => '<strong>'.t('Grade Justification').':</strong>',
    ];
    $items['justice'] = [
      '#type' => 'item',
      '#markup' => nl2br($task->data['justification']),
    ];
    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  $items[] = [
    '#markup' => '<h3>'.t('Grade Recieved').': '.$params['workflow']->data['grade'].'%',
  ];
  $items[] = [
    '#markup' => '<h4>'.t('Problem').':</h4>'
    .'<p>'.nl2br($params['problem']->data['problem']).'</p>'
  ];

  $items[] = [
    '#markup' => '<h4>'.t('Solution').':</h4>'
    .'<p>'.nl2br($params['solution']->data['solution']).'</p><hr />'
  ];

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

  drupal_set_message(sprintf('%s %s.', t('Grade'), ($save) ? 'saved (You must submit this still to complete the task.)' : 'submitted'));

  if (! $save) :
    $task->complete();

    // Save to the workflow
    $params['workflow']->setData('grade', $grade);

    return drupal_goto('class');
  endif;
}

/**
 * View a workflow
 * @param int
 */
function gg_view_workflow($workflow_id)
{
  $workflow = Workflow::find($workflow_id);
  if ($workflow == NULL) return drupal_not_found();

  $tasks = $workflow->tasks()
    ->whereStatus('complete')
    ->get();

  $return = '';

  $asec = $workflow->assignmentSection()->first();
  $assignment = $asec->assignment()->first();

  $return .= sprintf('<p><a href="%s">%s %s</a></p>', url('class/assignments/'.$asec->section_id.'/'.$asec->asec_id), HTML_BACK_ARROW, t('Back to Problem Listing'));

  $return .= '<p class="summary">'.nl2br($assignment->assignment_description).'</p><hr />';

  // Wrap it all inside an accordion
  $a = new Accordion('workflow-'.$workflow->workflow_id);

  if (count($tasks) > 0) : foreach ($tasks as $task) :
    if ($task->type !== 'grades ok' AND isset($task->settings['internal']) AND $task->settings['internal'])
      continue;

    $a->addGroup(t(ucwords($task->type)), $workflow->workflow_id.'-'.$task->task_id, groupgrade_view_task($task, 'overview'));
  endforeach; endif;

  $return .= $a;

  drupal_set_title(sprintf('%s: %s', t('Assignment'), $assignment->assignment_title));

  return $return;
}

function gg_task_grades_ok_form($form, &$form_state, $params) {
  $workflow = $params['task']->workflow()->first();

  $items = [];
  $items['final grade'] = [
    '#markup' => sprintf('<p><strong>%s:</strong> %d', t('Final Grade (Automatically Resolved)'), $workflow->data['grade']),
  ];
  return $items;
}


/**
 * Impliments a edit problem form
 */
function gg_task_resolution_grader_form($form, &$form_state, $params) {
  $problem = $params['problem'];
  $solution = $params['solution'];
  $task = $params['task'];

  // Previous Grades
  $grades = Task::where('workflow_id', '=', $task->workflow_id)
    ->whereType('grade solution')
    ->whereStatus('complete')
    ->get();

  $items = [];
  $items['problem'] = [
    '#markup' => '<h4>Problem</h4><p>'.nl2br($problem->data['problem']).'</p><hr />',
  ];
  $items['solution'] = [
    '#markup' => '<h4>Solution</h4><p>'.nl2br($solution->data['solution']).'</p><hr />',
  ];

  if (! $params['edit']) :
    $items['grade lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Grade')),
    ];
    $items['grade'] = [
      '#type' => 'item',
      '#markup' => $task->data['grade'],
    ];

    $items['justice lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Grade Justification')),
    ];
    $items['justice'] = [
      '#type' => 'item',
      '#markup' => nl2br($task->data['justification']),
    ];

    $items['comment lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Why was it resolved it this way?')),
    ];
    $items['comment'] = [
      '#type' => 'item',
      '#markup' => (isset($task->data['grade comment'])) ? nl2br($task->data['grade comment']) : '',
    ];

    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  // Previous grades
  if (count($grades) > 0) : foreach ($grades as $grade) :
    $items[] = [
      '#markup' => sprintf('<h4>%s: %s</h4>', t('Grade'), $grade->data['grade'].'%')
    ];

    $items[] = [
      '#markup' => sprintf('<p><strong>%s</strong>: %s</p>', t('Grade Justification'), nl2br($grade->data['justification']))
    ];
  endforeach; endif;

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

  $items['comment'] = [
    '#type' => 'textarea',
    '#title' => 'Why did you resolve it this way?',
    '#required' => true,
    '#default_value' => (isset($task->data['comment'])) ? $task->data['comment'] : '',
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
function gg_task_resolution_grader_form_submit($form, &$form_state) {
  $params = $form_state['build_info']['args'][0];
  $task = $params['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
    
  $grade = (int) $form['grade']['#value'];
  if ($grade !== abs($grade) OR $grade < 0 OR $grade > 100)
    return drupal_set_message(t('Invalid grade: '.$grade));
  
  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  $task->setDataAttribute([
    'grade' =>  $grade,
    'justification' => $form['justification']['#value'],
    'comment' => $form['comment']['#value']
  ]);

  $task->status = ($save) ? 'started' : 'completed';
  $task->save();

  if (! $save) :
    $task->complete();

    $workflow = $task->workflow()->first();
    $workflow->setData('grade', $grade);
    $workflow->save();
  endif;
  
  drupal_set_message(sprintf('%s %s.', t('Grade'), ($save) ? 'saved (You must submit this still to complete the task.)' : 'submitted'));

  if (! $save)
    return drupal_goto('class');
}
