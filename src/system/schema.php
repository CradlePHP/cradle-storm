<?php //-->
/**
 * This file is part of a package designed for the CradlePHP Project.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use Cradle\Package\System\Schema\Validator;

use Cradle\Package\System\Schema;
use Cradle\Package\System\SystemException;

/**
 * After a Schema is created, create a database table
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-schema-create', function ($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || !$response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Get Data
  $schema = Schema::i($response->getResults());
  //----------------------------//
  // 2. Validate Data
  //----------------------------//
  // 3. Prepare Data
  $storm = $this->package('/module/cradle-system-storm');

  $columns = [];
  foreach ($schema->getFields() as $name => $field) {
    $columns[$name] = $storm->getFieldSchema($field);
    if (in_array('required', $field['types'])) {
      $columns[$name]['required'] = true;
    } else {
      $columns[$name]['null'] = true;
    }

    if (in_array('unique', $field['types'])) {
      $columns[$name]['unique'] = true;
    } else if (in_array('indexable', $field['types'])) {
      $columns[$name]['index'] = true;
    }
  }

  //----------------------------//
  // 4. Process Data
  $payload = $this->makePayload(false);

  if ($request->meta('mysql')) {
    $payload['request']->meta('mysql', $request->meta('mysql'));
  }

  if ($request->meta('storm')) {
    $payload['request']->meta('storm', $request->meta('storm'));
  }

  $payload['request']->setStage([
    'table' => $schema->getName(),
    'primary' => $schema->getPrimaryName(),
    'columns' => $columns
  ]);

  $this->method('storm-create', $payload['request'], $payload['response']);

  if ($payload['response']->isError()) {
    $response->setError(true, $payload['response']->getMessage());
  }
}, -10);

/**
 * After a Schema is removed, remove database table
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-schema-remove', function ($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || !$response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Get Data
  $schema = Schema::i($response->getResults());

  //----------------------------//
  // 2. Validate Data
  //----------------------------//
  // 3. Prepare Data
  //----------------------------//
  // 4. Process Data
  $payload = $this->makePayload(false);

  if ($request->meta('mysql')) {
    $payload['request']->meta('mysql', $request->meta('mysql'));
  }

  if ($request->meta('storm')) {
    $payload['request']->meta('storm', $request->meta('storm'));
  }

  if ($request->getStage('mode') !== 'permanent') {
    $payload['request']->setStage([
      'table' => $schema->getName(),
      'name' => '_' . $schema->getName()
    ]);

    $this->method('storm-rename', $payload['request'], $payload['response']);

    if ($payload['response']->isError()) {
      $response->setError(true, $payload['response']->getMessage());
    }

    return;
  }

  $payload['request']->setStage([
    'table' => $schema->getName()
  ]);

  $this->method('storm-drop', $payload['request'], $payload['response']);

  if ($payload['response']->isError()) {
    $response->setError(true, $payload['response']->getMessage());
  }
}, -10);

/**
 * After a Schema is restored, restore database table
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-schema-restore', function ($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || !$response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Get Data
  $schema = Schema::i($response->getResults());

  //----------------------------//
  // 2. Validate Data
  //----------------------------//
  // 3. Prepare Data
  //----------------------------//
  // 4. Process Data
  $payload = $this->makePayload(false);

  if ($request->meta('mysql')) {
    $payload['request']->meta('mysql', $request->meta('mysql'));
  }

  if ($request->meta('storm')) {
    $payload['request']->meta('storm', $request->meta('storm'));
  }

  $payload['request']->setStage([
    'table' => '_' . $schema->getName(),
    'name' => $schema->getName()
  ]);

  $this->method('storm-rename', $payload['request'], $payload['response']);

  if ($payload['response']->isError()) {
    $response->setError(true, $payload['response']->getMessage());
  }
}, -10);

/**
 * After a Schema is updated, update database table
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('system-schema-update', function ($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || !$response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Get Data
  $schema = Schema::i($response->getResults());
  //----------------------------//
  // 2. Validate Data
  //----------------------------//
  // 3. Prepare Data
  $storm = $this->package('/module/cradle-system-storm');

  $columns = [];
  foreach ($schema->getFields() as $name => $field) {
    $columns[$name] = $storm->getFieldSchema($field);
    if (in_array('required', $field['types'])) {
      $columns[$name]['required'] = true;
    } else {
      $columns[$name]['null'] = true;
    }

    if (in_array('unique', $field['types'])) {
      $columns[$name]['unique'] = true;
    } else if (in_array('indexable', $field['types'])) {
      $columns[$name]['index'] = true;
    }
  }

  //----------------------------//
  // 4. Process Data
  $payload = $this->makePayload(false);

  if ($request->meta('mysql')) {
    $payload['request']->meta('mysql', $request->meta('mysql'));
  }

  if ($request->meta('storm')) {
    $payload['request']->meta('storm', $request->meta('storm'));
  }

  $payload['request']->setStage([
    'table' => $schema->getName(),
    'primary' => $schema->getPrimaryName(),
    'columns' => $columns
  ]);

  $this->method('storm-alter', $payload['request'], $payload['response']);

  if ($payload['response']->isError()) {
    $response->setError(true, $payload['response']->getMessage());
  }
});
