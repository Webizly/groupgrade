<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Task extends ModelBase {
  protected $primaryKey = 'task_id';
  protected $table = 'task';
  public $timestamps = false;
  
  /**
   * Retrieve Upcoming Tasks for a User
   * 
   * @param int User Id
   * @param string
   */
  public static function queryByStatus($user, $status = 'pending')
  {
    $query = self::where('user_id', '=', $user)
      ->orderBy('force_end', 'asc');

    switch ($status)
    {
      case 'pending' :
        $query->whereIn('status', ['triggered', 'started']);
        break;

      case 'completed' :
        $query->whereIn('status', ['complete', 'timed out']);
        break;

      // No filter
      case 'all' :
        $query->where('status', '!=', 'not triggered');
        $query->where('status', '!=', 'expired');

        break;
    }
    return $query;
  }

  public function workflow()
  {
    return $this->belongsTo('Drupal\ClassLearning\Models\Workflow');
  }

  public function assignmentSection()
  {
    return $this->workflow()->first()->assignmentSection();
  }

  public function section()
  {
    return $this->assignmentSection()->first()->section();
  }

  public function assignment()
  {
    return $this->assignmentSection()->first()->assignment();
  }
}
