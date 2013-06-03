<?php
namespace Drupal\ClassLearning\Common;
use Illuminate\Database\Capsule\Manager as Capsule;

class Database {
  private $isSetup = false;

  public static function setupCapsule()
  {
    if ($this->isSetup) return;

    global $databases;
    $capsule = new Capsule;
    
    $db = $databases['default']['default'];

    $capsule->addConnection([
        'driver'    => 'mysql',
        'host'      => $db['host'],
        'database'  => $db['database'],
        'username'  => $db['username'],
        'password'  => $db['password'],
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => $db['prefix'].'pla_',
    ]);

    // Setup the Eloquent ORM... (optional)
    $capsule->bootEloquent();

    // Set the event dispatcher used by Eloquent models... (optional)
    $capsule->setEventDispatcher(...);

    // Make this Capsule instance available globally via static methods... (optional)
    $capsule->setAsGlobal();
  }
}