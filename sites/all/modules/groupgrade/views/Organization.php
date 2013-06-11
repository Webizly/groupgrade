<?php
use Drupal\ClassLearning\Models\User,
  Drupal\ClassLearning\Models\Organization;

function groupgrade_organization_main()
{
  $organizations = Organization::all();

  $headers = array('Name');
  $rows = array();

  if (count($organizations) > 0) : foreach($organizations as $org)
    $rows[] = array('<a href="'.url('class/organization/'.$org->organization_id).'">'.
      $org->organization_name.'</a>');
  endif;

  return theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'attributes' => array('width' => '100%')
  ));
}

/**
 * View an Organization Overview
 * 
 * @param int
 */
function groupgrade_organization_view($id)
{
  $org = Organization::find($id);

  if ($org == NULL) drupal_not_found();

  $courses = $org->courses();

  $return = '';
  $return .= '<h3>Courses</h3>';
  $return .= '<p><a href="'.url('class/classes/new/'.$org->organization_id).'" class="btn btn-primary">Create Class</a></p>';

  $rows = array();
  if (count($courses) > 0) : foreach($courses as $course) :
    $rows[] = array($course->course_name);
  endforeach; endif;
  $return .= theme('table', array(
    'header' => array('Course Name'),
    'rows' => $rows,
    'attributes' => array('width' => '100%')
  ));

  return $return;

}