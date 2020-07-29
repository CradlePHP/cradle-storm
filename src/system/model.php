<?php //-->
/**
 * This file is part of a package designed for the CradlePHP Project.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use Cradle\Package\System\Schema;
use Cradle\Package\System\Exception;

use PDO as Resource;
use Cradle\Storm\SqlFactory;

/**
 * System Model Create Store Job
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-model-create', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || !$response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Prepare Data
  $table = $request->getStage('schema');
  $schema = Schema::load($request->getStage('schema'));

  $data = $request->getStage();
  if (!is_array($data)) {
    $data = [];
  }

  $storm = $this->package('/module/cradle-system-storm');
  $data = $storm->formatData($data, $schema);
  $data = $storm->flattenData($data);

  //----------------------------//
  // 2. Process Data
  $payload = $this->makePayload(false);
  if ($request->meta('mysql')) {
    $payload['request']->meta('mysql', $request->meta('mysql'));
  }

  if ($request->meta('storm')) {
    $payload['request']->meta('storm', $request->meta('storm'));
  }

  if (!$request->meta('storm-insert')) {
    $payload['request']->meta('storm-insert', $request->meta('storm-insert'));
  }

  $payload['request']->setStage([
    'table' => $table,
    'data' => $data
  ]);

  $this->method('storm-insert', $payload['request'], $response);
});

/**
 * System Model Detail Store Job
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-model-detail', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError()) {
    return;
  }

  //----------------------------//
  // 1. Get Data
  $data = [];
  if ($request->hasStage()) {
    $data = $request->getStage();
  }

  $schema = Schema::i($data['schema']);

  $id = $key = null;
  $uniques = $schema->getUniqueFieldNames();
  foreach ($uniques as $unique) {
    if (isset($data[$unique])) {
      $id = $data[$unique];
      $key = $unique;
      break;
    }
  }

  //----------------------------//
  // 2. Validate Data
  //we need an id
  if (!$id) {
    return $response->setError(true, 'Invalid ID');
  }

  //----------------------------//
  // 3. Prepare Data
  //no preparation needed
  //----------------------------//
  // 4. Process Data
});

/**
 * System Model Remove Store Job
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-model-remove', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError()) {
    return;
  }
});

/**
 * System Model Restore Store Job
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-model-restore', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError()) {
    return;
  }
});

/**
 * System Model Remove Store Job
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-model-update', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError()) {
    return;
  }
});
