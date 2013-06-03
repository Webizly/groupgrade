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
   * @var string
   */
  protected $prefix = 'pla_';

  /**
   * Unique key for this table's rows
   * 
   * @var string
   * @access protected
   */
  protected $key = 'id';

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

    if ((int) $this->fields[$this->key] > 0) :
      $items = $this->sanitizeSaving(clone $this->fields);

      // We don't want to overwrite the ID
      // or the creation date of the request
      unset($items->{$this->key});

      // They're updating
      db_update($this->table)
        ->fields((array) $items)
        ->condition($this->key, $this->fields[$this->key])
        ->execute();
    else :
      // Inserting
      $items = $this->sanitizeSaving(clone $this->items);

      unset($items->{$this->key});
      $items->created_date = time();

      // Set the new insert ID
      $this->{$this->key} = db_insert($this->table)
        ->fields((array) $items)
        ->execute();
    endif;
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
    if ($this->rules == NULL) return TRUE;

    foreach($this->rules as $key => $validators) :
      $explode = explode('|', $validators);

      foreach($explode as $single) :
        $explode_colen = explode(':', $single, 1);

        $v = $this->handleValidate($key, $explode_colen[0],
          (isset($explode_colen[1])) ? $explode_colen[1] : []
        );

        // We're out!
        if ($v !== true)
        {
          throw new ModelException($v);
          return false;
        }
      endforeach;
    endforeach;

    return true;
  }

  /**
   * Helper function to handle Validation
   *
   * @param string The Key
   * @param string the validator method
   * @param array additional data
   * @return bool|string
   */
  private function handleValidate($key, $single, $data = [])
  {
    switch($single) :
      case 'required' :
        if (! isset($this->fields[$key]) OR is_null($this->fields[$key]))
          return sprintf('Field %s is required', $key);
        break;

      case 'min' :

        break;

      case 'max' :

        break;

      case 'alpha' :
        if (isset($this->fields[$key]) AND ! preg_match('/^([a-z])+$/i', $this->fields[$key]) )
          return sprintf('Field %s is not alpha formatted', $key);
        break;

      case 'alphanum' :
        if (isset($this->fields[$key]) AND ! preg_match('/^([a-z0-9])+$/i', $this->fields[$key]) )
          return sprintf('Field %s is not alpha numeric formatted', $key);
        break;

      case 'date' :

        break;

      case 'dateinformat' :

        break;
    endswitch;
  }

  protected function sanitizeSaving($object)
  {
    return $object;
  }

  public function sanitizeLoading($object)
  {
    return $object;
  }

  /**
   * Generate a table name with prefix
   * 
   * @param string
   * @return string
   */
  protected function table($table = '')
  {
    if ($table == '') :
      if ($this->table !== NULL)
        $table = $this->table;
      else 
        $table = strtolower(get_called_class());
    endif;

    return $this->prefix.$table;
  }
}