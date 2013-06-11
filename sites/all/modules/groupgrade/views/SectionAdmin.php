<?php
use Drupal\ClassLearning\Models\Section;

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
  $course = $section->course()->first();

  $return = '';
  $return .= '<h2>View Section <small>'.$course->course_name.' &mdash; '.$section->section_name.'</small></h2>';
  $return .= '<div class="admin clearfix"><div class="left clearfix">';

  $return .= '</div><div class="right clearfix">';
  
  $return .= '</div></div>';

  return $return;
}