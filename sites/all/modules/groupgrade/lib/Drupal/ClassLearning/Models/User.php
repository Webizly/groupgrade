<?php
namespace Drupal\ClassLearning\Models;
use Drupal\ClassLearning\Models\SectionUsers;

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
  public static function classes()
  {
    return SectionUser::where('user_id', '=', $this->getKey())
      ->groupBy('section_id')
      ->get();
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
}