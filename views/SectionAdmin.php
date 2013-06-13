<?php
use Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\SectionUsers;

function groupgrade_section_view($course)
{
  return drupal_get_form('groupgrade_section_view_form', $course);
}

function groupgrade_section_view_form($form, &$form_state, $course)
{
  $cou = Drupal\ClassLearning\Models\Course::find($course);
  
  if ($cou == NULL) return drupal_not_found();

  $getSemesters = Drupal\ClassLearning\Models\Semester::where('organization_id', '=', $cou->organization_id)
    ->orderBy('semester_start', 'desc')
    ->get();

  $index = array();
  if (count($getSemesters) > 0) : foreach($getSemesters as $s) :
    $index[$s->semester_id] = $s->semester_name;
  endforeach; endif;

  $items = array();
  $items['back-text'] = array(
    '#type' => 'link',
    '#title' => 'Back to Course',
    '#href' => 'admin/pla/courses/'.$course,
  );

  $items['name'] = array(
    '#title' => 'Section Name',
    '#type' => 'textfield',
    '#required' => true,
  );

  $items['description'] = array(
    '#title' => 'Section Description',
    '#type' => 'textarea',
    '#required' => true,
  );

  $items['semester'] = array(
     '#type' => 'select',
     '#title' => t('Semester'),
     '#options' => $index,
     '#default_value' => null,
 );

  $items['course'] = array(
    '#value' => $course,
    '#type' => 'hidden'
  );

  $items['submit'] = array(
    '#value' => 'Create Section',
    '#type' => 'submit'
  );
  return $items;
}

function groupgrade_section_view_form_submit($form, &$form_state) {
  $name = $form['name']['#value'];
  $course = $form['course']['#value'];
  $description = $form['description']['#value'];
  $semester = $form['semester']['#value'];

  $section = new Section;
  $section->course_id = (int) $course;
  $section->semester_id = (int) $semester;
  $section->section_name = $name;
  $section->section_description = $description;
  $section->save();

  drupal_set_message(sprintf('Section "%s" created.', $name));
}

function groupgrade_view_section($section_id)
{
  $section = Section::find($section_id);
  if ($section == NULL) return drupal_not_found();

  $course = $section->course()->first();
  if ($course == NULL) return drupal_not_found();

  $return = '';
  $return .= '<h2>View Section <small>'.$course->course_name.' &mdash; '.$section->section_name.'</small></h2>';
  $return .= '<div class="admin clearfix"><div class="left clearfix">';
  $return .= '<h3>Assignments</h3>';

  $assignments = $section->assignments()->get();
  $rows = array();

  if (count($assignments) > 0) : foreach($assignments as $assignment) :
    $rows[] = array($assignment->assignment_title, $assignment->start);
  endforeach; endif;

  $return .= theme('table', array(
    'rows' => $rows,
    'header' => array('Title', 'Start Date'),
    'empty' => 'No assignments found.',
  ));

  $return .= '</div><div class="right clearfix">';

  foreach(array('instructor', 'student') as $role):
    $return .= '<h3>'.ucfirst($role).'s</h3>';
    $students = $section->students()
      ->where('su_role', '=', $role)
      ->get();

    $rows = array();
    if (count($students) > 0) : foreach($students as $student) :
      $user = $student->user();
      $rows[] = array(
        ggPrettyName($user),
        $student->su_status,
        '<a href="'.url('admin/pla/section/remove-user/'.$student->user_id.'/'.$section->section_id).'">remove</a> &mdash;
        <a href="'.url('admin/pla/section/change-status/'.$student->user_id.'/'.$section->section_id).'">change status</a>',
      );
    endforeach; endif;

    $return .= theme('table', array(
      'rows' => $rows,
      'header' => array('User', 'Status', 'Operations'),
      'empty' => 'No users found.',
    ));
  endforeach;

  $return .= '</div></div><div class="admin"><div class="clearfix">';
  $return .= '<h5>Add User to Section</h5>';

  $return .= '</div></div>';
  $form = drupal_get_form('groupgrade_add_student_form', $section->section_id);
  $return .= drupal_render($form);

  return $return;
}

function groupgrade_add_student_form($form, &$form_state, $section_id) {
  $items = array();

  $section = Section::find($section_id);
  $students = $section->studentsNotIn();
  if (count($students) == 0) :
    $items['mk'] = array(
      '#type' => 'item',
      '#markup' => 'No users available to add.'
    );
    return $items;
  endif;

  $index = array();
  if (count($students) > 0) : foreach($students as $student) :
    $user = \user_load($student->uid);

    $index[$student->uid] = ggPrettyName($user);
  endforeach; endif;

  $items['user'] = array(
     '#type' => 'select',
     '#title' => t('User'),
     '#options' => $index,
     '#default_value' => null,
     '#multiple' => TRUE,
 );

  $items['section'] = array(
    '#value' => $section_id,
    '#type' => 'hidden'
  );

  $items['role'] = array(
    '#type' => 'select',
    '#title' => 'Role',
    '#options' => array(
      'student' => 'Student',
      'instructor' => 'Instructor'
    ),
    '#default_value' => 'student'
  );

  $items['submit'] = array(
    '#value' => 'Add User(s) to Section',
    '#type' => 'submit'
  );
  return $items;
}

function groupgrade_add_student_form_submit($form, &$form_state) {
  $users = (array) $form['user']['#value'];
  $role = $form['role']['#value'];
  $section = $form['section']['#value'];

  if (count($users) > 0) : foreach($users as $user):
    $su = new SectionUsers;
    $su->section_id = (int) $section;
    $su->user_id = $user;
    $su->su_role = $role;
    $su->su_status = 'active';
    $su->save();
  endforeach; endif;

  drupal_set_message( sprintf('%d user(s) added to section %d', count($users), $section) );
}

function groupgrade_remove_user_section($user, $section)
{
  SectionUsers::where('section_id', '=', $section)
    ->where('user_id', '=', $user)
    ->delete();

  drupal_set_message(sprintf('User %d removed from section %d', $user, $section));
  return drupal_goto('admin/pla/section/'.$section);
}
