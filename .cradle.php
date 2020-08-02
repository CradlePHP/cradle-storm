<?php //-->
/**
 * This file is part of a package designed for the CradlePHP Project.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

require_once __DIR__ . '/src/storm/schema.php';
require_once __DIR__ . '/src/storm/table.php';
require_once __DIR__ . '/src/system/schema.php';
require_once __DIR__ . '/src/system/table.php';
require_once __DIR__ . '/src/methods.php';

use Cradle\Package\Storm\StormPackage;

//Register a pseudo package storm
$this
  //Register a pseudo package storm
  ->register('storm')
  //then load the package
  ->package('storm')
  //map the package with the event package class methods
  ->mapPackageMethods($this('resolver')->resolve(StormPackage::class, $this));
