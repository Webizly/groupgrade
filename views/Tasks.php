<?php
use Drupal\ClassLearning\Models\Task;

function groupgrade_tasks_dashboard() {
  return groupgrade_tasks_view_specific('pending');
}

function groupgrade_tasks_view_specific($specific = '') {
  global $user;
  $tasks = Task::queryByStatus($user->uid, $specific)->get();
  $rows = array();

  switch($specific)
  {
    case 'pending' :
      $headers = array('Due Date', 'Type', 'Course', 'Assignment', 'Problem');
      

      if (count($tasks) > 0) : foreach($tasks as $task) :
        $row_t = array();
        $row_t[] = $task->force_end;
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
        $rowt = array();
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
