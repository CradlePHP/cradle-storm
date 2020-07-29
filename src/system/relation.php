<?php //-->
/**
 * This file is part of a package designed for the CradlePHP Project.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

/**
 * Links Model to Relation Job
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-relation-link-store', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError()) {
    return;
  }
});

/**
 * Uninks Model to Relation Job
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-relation-unlink-store', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError()) {
    return;
  }
});

/**
 * Unlinks All Model From Relation Job
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-relation-unlink-all-store', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError()) {
    return;
  }
});
