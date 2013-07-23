<?php
/**
 * @file
 * Accordion Container
 *
 * @package groupgrade
 */
namespace Drupal\ClassLearning\Common;

/**
 * Accordian Class
 *
 * Used to build accordians in a common way
 *
 * @package groupgrade
 */
class Accordion {
  /**
   * Groups Storage
   * 
   * @var array
   */
  protected $groups = [];

  /**
   * Accordion ID
   * 
   * @var string
   */
  protected $id;

  /**
   * Accordion Constructor
   *
   * @return void
   * @param string ID of the accordion
   */
  public function __construct($id = 'accordion')
  {
    $this->id = $id;
  }

  /**
   * Add an accordian group
   * 
   * @param string $title Public facing title
   * @param string $id Internal ID
   * @param string $contents Contents of the group
   */
  public function addGroup($title, $id, $contents, $open = false)
  {
    $this->groups[] = compact('title', 'id', 'contents', 'open');
  }

  /**
   * Render the Accordion
   *
   * @return string
   */
  public function __toString()
  {
    $return = '';
    $return .= sprintf('<div class="accordion" id="%s">', $this->id);

    if (count($this->groups) > 0) : foreach ($this->groups as $i => $group) :
      $return .= '<div class="accordion-group">
    <div class="accordion-heading">';

      $return .= sprintf('<a class="accordion-toggle" data-toggle="collapse" data-parent="#%s" href="#%s">%s</a>',
        $group['id']/*$this->id*/, $group['id'], $group['title']
      );
    
      $return .= '</div>';
      
      $return .= sprintf('<div id="%s" class="accordion-body collapse %s">', $group['id'], ($group['open']) ? 'in' : '');
      $return .= '<div class="accordion-inner">';

      $return .= $group['contents'];
      $return .= '</div>
    </div>
  </div>';
    endforeach; endif;

    $return .= '</div>';
    return $return;
  }
}
