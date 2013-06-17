<?php
namespace Drupal\ClassLearning\Models;
use Illuminate\Database\Eloquent\Model as ModelBase,
  Drupal\ClassLearning\Exception as ModelException;

class Assignment extends ModelBase {
  protected $primaryKey = 'assignment_id';
  protected $table = 'assignment';
  public $timestamps = false;

  public function sections()
  {
    return \Drupal\ClassLearning\Models\AssignmentSection::where('assignment_id', '=', $this->assignment_id)
      ->join('section', 'section.section_id', '=', 'assignment_section.section_id');
  }
}
