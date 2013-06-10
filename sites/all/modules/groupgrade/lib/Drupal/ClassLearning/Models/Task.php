<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Task extends ModelBase {
  protected $primaryKey = 'task_id';
  protected $table = 'task';

  /**
   * Retrieve Upcoming Tasks for a User
   * 
   * @param int User Id
   * @param string
   */
  public static function queryByStatus($user, $status = 'pending')
  {
    return self::where('user_id', '=', $user)
      ->whereStatus($status)
      ->orderBy('force_end', 'asc')
      ->get();
  }

  public function assignment()
  {
    return $this->belongsTo('Assignment');
  }
}