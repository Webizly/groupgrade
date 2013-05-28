<?php
namespace Drupal\ClassLearning\Models;
use Drupal\ClassLearning\Common\ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class CourseStudents extends ModelBase {
  /**
   * @var array
   */
  protected $fields = array(
    'course_id' => NULL,
    'user_id' => NULL,
  );

  /**
   * @var string
   */
  protected $table = 'gg_course_students';

  /**
   * Validate the Course Students Model
   *
   * @access protected
   */
  protected function validate()
  {
    if ($this->user_id == NULL)
      throw new ModelException('User ID is not set.');

    if ($this->course_id == NULL)
      throw new ModelException('Course ID not set.');

    return TRUE;
  }
}