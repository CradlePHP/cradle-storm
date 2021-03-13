<?php //-->

use Cradle\IO\Request\RequestInterface;
use Cradle\IO\Response\ResponseInterface;

/**
 * Database alter job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('system-store-alter', function (RequestInterface $request, ResponseInterface $response) {
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

  if (!isset($data['relations']) || !is_array($data['relations'])) {
    $data['relations'] = [];
  }

  //----------------------------//
  // 2. Validate Data
  $errors = [];

  if (!isset($data['schema'])) {
    $errors['schema'] = 'Name is required';
  }

  if (!isset($data['primary'])) {
    $errors['primary'] = 'Primary name is required';
  }

  if (!isset($data['fields'])
    || !is_array($data['fields'])
    || empty($data['fields'])
  ) {
    $errors['fields'] = 'Fields are required';
  }

  //if there are errors
  if (!empty($errors)) {
    return $response->setValidation($errors);
  }

  //----------------------------//
  // 3. Prepare Data
  //load up the storm package
  $storm = $this->package('cradlephp/cradle-storm');

  //the goal is to populate columns
  $columns = [];
  foreach ($data['fields'] as $name => $field) {
    //if no types
    if (!isset($field['types'])) {
      //let's not add it.. (We are not rocket scientists.)
      continue;
    }

    //determine sql serialized schema
    //should be the same for all sql engines
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
  //make a new payload
  $payload = $request->clone(true);

  $payload->setStage([
    'table' => $data['name'],
    'primary' => $data['primary'],
    'columns' => $columns
  ]);

  $this('event')->emit('storm-alter', $payload, $response);

  if ($response->isError()) {
    return;
  }

  $installed = $this('storm')->getTables($data['name'] . '_%');
  $relations = array_keys($data['relations']);

  //determine the relation tables that need to be removed
  foreach ($installed as $relation) {
    //uninstall if it's not in the schema
    if (in_array($relation, $relations)) {
      continue;
    }

    //make a new payload
    $payload = $request->clone(true);
    //drop the relation table
    $payload->setStage('table', $relation);
    //surpress errors
    $this('event')->method('storm-drop', $payload);
  }

  //determine the relation tables that need to be added
  foreach ($data['relations'] as $table => $relation) {
    //install if it's installed
    if (in_array($table, $installed)) {
      continue;
    }

    $payload = $request->clone(true);

    $payload->setStage([
      'table' => $table,
      'primary' => [
        $relation['primary1'],
        $relation['primary2']
      ],
      'drop' => 1
    ]);

    //surpress errors
    $this('event')->method('storm-create', $payload);
  }
});

/**
 * Database create job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('system-store-create', function (RequestInterface $request, ResponseInterface $response) {
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

  if (!isset($data['relations']) || !is_array($data['relations'])) {
    $data['relations'] = [];
  }

  //----------------------------//
  // 2. Validate Data
  $errors = [];

  if (!isset($data['name'])) {
    $errors['name'] = 'Name is required';
  }

  if (!isset($data['primary'])) {
    $errors['primary'] = 'Primary name is required';
  }

  if (!isset($data['fields'])
    || !is_array($data['fields'])
    || empty($data['fields'])
  ) {
    $errors['fields'] = 'Fields are required';
  }

  //if there are errors
  if (!empty($errors)) {
    return $response->setValidation($errors);
  }

  //----------------------------//
  // 3. Prepare Data
  //load up the storm package
  $storm = $this->package('cradlephp/cradle-storm');

  //the goal is to populate columns
  $columns = [];
  foreach ($data['fields'] as $name => $field) {
    //if no types
    if (!isset($field['types'])) {
      //let's not add it.. (We are not rocket scientists.)
      continue;
    }

    //determine sql serialized schema
    //should be the same for all sql engines
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
  //make a new payload
  $payload = $request->clone(true);

  $payload->setStage([
    'table' => $data['name'],
    'primary' => $data['primary'],
    'columns' => $columns
  ]);

  $this('event')->emit('storm-create', $payload, $response);

  if ($response->isError()) {
    return;
  }

  //also create the relations
  foreach ($data['relations'] as $table => $relation) {
    $payload = $request->clone(true);

    $payload->setStage([
      'table' => $table,
      'primary' => [
        $relation['primary1'],
        $relation['primary2']
      ],
      'drop' => 1
    ]);

    //surpress errors
    $this('event')->method('storm-create', $payload);
  }
});

/**
 * Database drop job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('system-store-drop', function (RequestInterface $request, ResponseInterface $response) {
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

  if (!isset($data['restorable'])) {
    $data['restorable'] = false;
  }

  //----------------------------//
  // 2. Validate Data
  $errors = [];

  if (!isset($data['schema'])) {
    $errors['schema'] = 'Schema is required';
  }

  //if there are errors
  if (!empty($errors)) {
    return $response->setValidation($errors);
  }
  //----------------------------//
  // 3. Prepare Data
  //make a new payload
  $payload = $request->clone(true);
  //set the payload
  $payload->setStage(['table' => $data['schema']]);

  //----------------------------//
  // 4. Process Data
  //if it could be restored
  if ($data['restorable']) {
    //just rename it
    $payload->setStage('name', '_' . $data['schema']);
    return $this('event')->emit('storm-rename', $payload, $response);
  }

  $this('event')->emit('storm-drop', $payload, $response);
});

/**
 * Database rename job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('system-store-recover', function (RequestInterface $request, ResponseInterface $response) {
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

  //----------------------------//
  // 2. Validate Data
  $errors = [];

  if (!isset($data['schema'])) {
    $errors['schema'] = 'Schema is required';
  }

  //if there are errors
  if (!empty($errors)) {
    return $response->setValidation($errors);
  }
  //----------------------------//
  // 3. Prepare Data
  //make a new payload
  $payload = $request->clone(true);
  //set the payload
  $payload->setStage([
    'table' => '_' . $data['schema'],
    'name' => $data['schema']
  ]);

  //----------------------------//
  // 4. Process Data
  //just rename it
  return $this('event')->emit('storm-rename', $payload, $response);
});
