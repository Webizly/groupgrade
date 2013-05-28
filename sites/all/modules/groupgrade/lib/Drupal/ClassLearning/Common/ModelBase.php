<?php
namespace Drupal\ClassLearning\Common;
use Drupal\ClassLearning\Exception as ModelException;

/**
 * Model Base Class
 *
 * Provides an ORM-style interface to the models
 */
abstract class ModelBase {
  protected $table;
  protected $fields;

  /**
   * Constructor
   * 
   * @param array
   * @throws Drupal\ClassLearning\Exception
   */
  public function __construct($data = array())
  {
    if ($this->fields == NULL)
      throw new ModelException('Items for the model are not initialized.');

    if ($this->table == NULL)
      throw new ModelException('Table not defined for model.');
  }

  /**
   * Save the Model
   * 
   * @return object
   */
  public function save()
  {
    // Validate
    try {
      $this->validate();
    } catch (ModelException $e) {
      // We're in error here!
    }
  }

  /**
   * Magic method to get a data from the model
   * 
   * @param string
   * @return mixed
   */
  public function __get($item)
  {
    if (! isset($this->fields[$item]))
      throw new ModelException(sprintf('Field %s not defined.', $item));

    return $this->fields[$item];
  }
  
  /**
   * @return void
   */
  public function __set($item, $value)
  {
    if (! isset($this->fields[$item]))
      throw new ModelException(sprintf('Field %s not defined', $item));

    $this->fields[$item] = $value;
  }

  /**
   * Placeholder to validate the Model
   *
   * It should throw Drupal\ClassLearning\Exception on error
   *
   * @throws Drupal\ClassLearning\Exception
   * @return bool
   */
  protected function validate()
  {
    return TRUE;
  }
}