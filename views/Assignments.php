<?php
use Drupal\ClassLearning\Models\User,
  Drupal\ClassLearning\Models\WorkflowTask,
  Drupal\ClassLearning\Models\Assignment,
  Drupal\ClassLearning\Models\AssignmentSection;

function groupgrade_assignments_dashboard() {
  global $user;
  $assignments = User::assignedAssignments()->get();

  $return = '';
  
  $rows = [];
  $headers = ['Assignment', 'Course', 'Start Date'];

  if (count($assignments) > 0) : foreach ($assignments as $assignment) :
    $rows[] = [
      sprintf('<a href="%s">%s</a>', url('class/assignments/'.$assignment->section_id.'/'.$assignment->asec_id), $assignment->assignment_title),
      sprintf('%s &mdash; %s', $assignment->course_name, $assignment->section_name),
      $assignment->asec_start
    ];
  endforeach; endif;

  $return .= sprintf('<p>%s<p>',
    t('Select an assignment title to see the problems created for that assignment. Note that you might not be allowed to see some work in progress.')
  );

  $return .= theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'empty' => t('No assignments found.'),
    'attributes' => array('width' => '100%')
  ));

  return $return;
}

function gg_view_assignment_listing($section_id, $asec_id)
{
  if (! gg_in_section($section_id))
    return drupal_not_found();
  
  $asec = AssignmentSection::find($asec_id);
  $section = $asec->section()->first();

  if ((int) $section->section_id !== (int) $section_id)
    return drupal_not_found();

  $assignment = $asec->assignment()->first();
  
  drupal_set_title($assignment->assignment_title);

  $createProblems = WorkflowTask::whereIn('workflow_id', function($query) use ($asec_id)
  {
    $query->select('workflow_id')
      ->from('workflow')
      ->where('assignment_id', '=', $asec_id);
  })
    ->whereType('edit problem')
    ->whereStatus('complete')
    ->get();

  $headers = ['Problem'];
  $rows = [];

  if (count($createProblems) > 0) : foreach ($createProblems as $t) :
    $rows[] = [sprintf(
      '<a href="%s">%s</a>',
      url('class/workflow/'.$t->workflow_id),
      word_limiter($t->data['problem'], 20)
    )];
  endforeach; endif;

  $return = '';

  // Back Link
  $return .= sprintf('<p><a href="%s">%s %s</a>', url('class/assignments'), HTML_BACK_ARROW, t('Back to Assignment List'));

  // Course/section/semester
  $course = $section->course()->first();
  $semester = $section->semester()->first();

  $return .= sprintf('<p><strong>%s</strong>: %s &mdash; %s &mdash; %s',
    t('Course'),
    $course->course_name,
    $section->section_name,
    $semester->semester_name
  );

  // Assignment Description
  $return .= sprintf('<p class="summary">%s</p>', nl2br($assignment->assignment_description));
  $return .= '<hr />';
    
  // Instructions
  $return .= sprintf('<p>%s <em>%s</em><p>',
    t('Select a question to see the work on that question so far.'),
    t('Note that you will not be allowed to see some work in progress.')
  );

  $return .= theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'empty' => 'No problems found.',
    'attributes' => array('width' => '100%')
  ));

  return $return;
}
