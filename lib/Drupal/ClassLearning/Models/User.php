<?php
namespace Drupal\ClassLearning\Models;
use Drupal\ClassLearning\Models\SectionUsers,
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
    switch($filter)
    {
      case 'current' :
        $d = date('Y-m-d H:i:s');

        $connection = Capsule::connection();

        return $connection->select('
SELECT * FROM `pla_course` WHERE `course_id` IN (
  # Get the current courses
  SELECT `course_id` FROM `pla_section` WHERE `section_id` IN (
    SELECT
      `semester_id`
    FROM
      `pla_semester` 
    WHERE 
      `semester_start` >= ?
    AND
      `semester_end` <= ?
  )
) AND `course_id` IN (
  # Get the courses a user is taking
  SELECT `course_id` FROM `pla_section` WHERE `section_id` IN (
    # Get a users sections
    SELECT `section_id` FROM `pla_section_user` WHERE `user_id` = ?
  ) 
)
ORDER BY `organization_id` ASC
', array($d, $d, self::key()));
        break;

      case 'past' :

        break;

      case 'all' :
        $query = SectionUsers::where('user_id', '=', $this->getKey())
          ->groupBy('section_id');

        break;

    }
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
   * Staticlly Retrieve the user ID
   *
   * @return int
   */
  public static function key()
  {
    global $user;
    return $user->uid;
  }
}