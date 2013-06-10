<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Semester extends ModelBase {
  protected $primaryKey = 'semester_id';
  protected $table = 'semester';

  /**
   * Retrieve the Current Semester
   * 
   * @param int Organization ID
   * @return object
   */
  public static function currentSemester($organization)
  {
    $d = date('Y-m-d H:i:s');
    return self::where('semester_start', '<=', $d)
      ->where('semester_end', '>=', $d)
      ->where('organization_id', '=', $o)
      ->first();
  }
}