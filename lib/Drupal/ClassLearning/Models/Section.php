<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException,
  Drupal\ClassLearning\Models\AssignmentSection,
  Drupal\ClassLearning\Models\Assignment,
  Illuminate\Database\Capsule\Manager as Capsule;

class Section extends ModelBase {
  protected $table = 'section';
  protected $primaryKey = 'section_id';
  public $timestamps = false;
  
  public function course()
  {
    return $this->belongsTo('Drupal\ClassLearning\Models\Course');
  }

  public function students()
  {
    return $this->hasMany('Drupal\ClassLearning\Models\SectionUsers');
  }

  public function assignments()
  {
    return AssignmentSection::where('section_id', '=', $this->section_id)
      ->join('assignment', 'assignment.assignment_id', '=', 'assignment_section.assignment_id')
      ->select('assignment.*', 'assignment_section.*');
  }
  
  public function semester()
  {
    return $this->belongsTo('Drupal\ClassLearning\Models\Semester');
  }

  /**
   * Retrieve the Students not in the section currently
   * 
   * @return object
   */
  public function studentsNotIn()
  {
    return db_query('
    SELECT `uid` FROM {users} WHERE `uid` NOT IN (
      SELECT `user_id` FROM {pla_section_user} WHERE `section_id` = :sid
    ) AND `uid` > 0', array(
      'sid' => $this->section_id
    ))->fetchAll();
  }
}
