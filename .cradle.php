<?php //-->
/**
 * This file is part of a package designed for the CradlePHP Project.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use Cradle\Storm\SqlFactory;

//only load if there is a pdo package
if ($this->isPackage('pdo')) {
  $this
    //first register the package storm
    ->register('storm')
    //then load the package
    ->package('storm')
    //here we use SqlFactory to determine the right engine (mysql, sqlite, postgre)
    ->mapPackageMethods($this('resolver')->resolveStatic(
      SqlFactory::class,
      'load',
      //this should be the expected PDO object
      $this('pdo')->getPackageMap()
    ))
    //use one global resolver
    ->setResolverHandler($this('resolver')->getResolverHandler());

  //now we can require the events
  require_once __DIR__ . '/src/storm/schema.php';
  require_once __DIR__ . '/src/storm/table.php';
  require_once __DIR__ . '/src/system/schema.php';
  require_once __DIR__ . '/src/system/table.php';
  require_once __DIR__ . '/src/methods.php';
}
