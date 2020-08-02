<?php //-->
/**
 * This file is part of the Cradle PHP Library.
 * (c) 2016-2018 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

namespace Cradle\Package\Storm;

use PDO;

use Cradle\Package\Package;

use Cradle\Storm\SqlFactory;

use Cradle\Framework\Package\PDO\PDOPackage;

/**
 * Storm Package
 *
 * @vendor   Cradle
 * @package  Package
 * @author   Christian Blanquera <cblanquera@openovate.com>
 * @standard PSR-2
 */
class StormPackage extends PDOPackage
{
  /**
   * Mutates to PDO using the given config
   *
   * @param *PDO $resource
   *
   * @return Package
   */
  public function loadPDO(PDO $resource): Package
  {
    //get the resolver
    $resolver = $this->handler->package('resolver');
    //use the sql factory to load the right engine
    $resource = $resolver->resolveStatic(SqlFactory::class, 'load', $resource);
    //load the storm package
    $package = $this->handler->package('storm');
    //re map to use the sql engine
    $package->mapPackageMethods($resource);
    //use one global resolver
    $package->setResolverHandler($resolver->getResolverHandler());
    return $package;
  }
}
