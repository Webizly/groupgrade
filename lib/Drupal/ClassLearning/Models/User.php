<?php
namespace Drupal\ClassLearning\Models;
use Drupal\ClassLearning\Models\SectionUsers,
  Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\Semester,
  Drupal\ClassLearning\Models\AssignmentSection,
  Illuminate\Database\Capsule\Manager as Capsule;

/**
 * User Interface
 *
 * Unlike the other models, this isn't an Eloquent model (we let Drupal
 * handle that part). Instead, we interface with user data thoughout the
 * system here as IF it it were an ORM model
 *
 * @package groupgrade
 */
class User {
  /**
   * Retrieve the Classes a user has
   *
   * You can pass a two filters to the method:
   *  - current: classes a user is currently taking
   *  - past: classes a user took in the past
   *  - none: no filter, retrieve all classes
   *
   * @todo impliment caching
   * @param  string $filter [description]
   * @return object
   */
  public static function classes($filter = 'current')
  {
    $query = Section::whereIn('section.section_id', function($query)
    {
      global $user;

      $query->select('section_id')
        ->from('section_user')
        ->where('user_id', '=', (int) $user->uid);
    });

    $query->join('section_user', 'section.section_id', '=', 'section_user.section_id');
    $query->groupBy('section.section_id');
    //  ->select('section.*', 'section_user.su_role', 'section_user.su_status');
    $query->join('course', 'course.course_id', '=', 'section.course_id');
    $query->join('semester', 'semester.semester_id', '=', 'section.semester_id');

    $d = date('Y-m-d');
    if ($filter == 'current') :
      $current_semester = Semester::where('semester_start', '<=', $d)
        ->where('semester_end', '>=', $d)
        ->select('semester_id')
        ->first();

      // There is no current semester
      if ($current_semester == NULL)
        return NULL;

      $query->where('section.semester_id', '=', $current_semester->semester_id);
    endif;
    
    $query->orderBy('semester.semester_end', (($filter == 'current') ? 'desc' : 'asc'));
    
    return $query->get();
  }

  /**
   * We query the ACL lists for something along the lines of
   * organization:* with the user on it
   * 
   * @return array
   */
  public static function getOrganizationsCanAccess()
  {
    $lists = acl_get_ids_by_user('pla', self::key());
    var_dump(self::key(), $lists);
    exit;
  }

  /**
   * Retrieve the user ID
   *
   * @return int
   */
  public function getKey()
  {
    global $user;
    return $user->uid;
  }

  /**
   * Statically Retrieve the user ID
   *
   * @return int
   */
  public static function key()
  {
    global $user;
    return $user->uid;
  }

  /**
   * Retrieve the Assignments a user has created
   * 
   * @return object Query Builder
   */
  public static function assignments()
  {
    return Drupal\ClassLearning\Models\Assignments::where('user_id', '=', self::key());
  }

  /**
   * Return the sections that a user is apart of with a specific role
   * 
   * @param string
   * @return object Query Builder object
   */
  public static function sectionsWithRole($role)
  {
    return Section::select(array('section_user.su_role', 'section_user.su_status', 'section.*'))
      ->join('section_user', 'section.section_id', '=', 'section_user.section_id')
      ->where('section_user.user_id', '=', self::key())
      ->where('section_user.su_role', '=', $role);
  }

  /**
   * Get a user's assignments they're apart of
   *
   * @return object Query Builder Object 
   */
  public static function assignedAssignments()
  {
    return AssignmentSection::whereIn('assignment_section.section_id', function($query)
    {
      global $user;

      $query->select('section_id')
        ->from('section_user')
        ->where('user_id', '=', $user->uid);
    })
    ->join('assignment', 'assignment.assignment_id', '=', 'assignment_section.assignment_id')
    ->join('section', 'assignment_section.section_id', '=', 'section.section_id')
    ->join('course', 'course.course_id', '=', 'section.course_id')
    ->select('assignment.*', 'assignment_section.asec_id', 'assignment_section.section_id', 'assignment_section.asec_start')
    ->addSelect('section.course_id', 'section.section_name')
    ->addSelect('course.*')
    ->orderBy('assignment_section.asec_start', 'desc');
  }
}
