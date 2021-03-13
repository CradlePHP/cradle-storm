<?php //-->

use Cradle\Storm\SqlException;

use Cradle\IO\Request\RequestInterface;
use Cradle\IO\Response\ResponseInterface;

/**
 * Database alter job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('storm-alter', function (RequestInterface $request, ResponseInterface $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Get Data
  $table = $request->getStage('table');
  $primary = $request->getStage('primary');
  $columns = $request->getStage('columns');

  //make sure primary is an array
  if (!is_array($primary)) {
    $primary = [$primary];
  }

  //----------------------------//
  // 2. Validate Data
  $errors = [];
  //we need at least a table
  if (!trim($table)) {
    $errors['table'] = 'Table is required';
  }

  if (empty($columns)) {
    $errors['columns'] = 'Empty columns';
  } else {
    //all columns should be an array (hash)
    foreach ($columns as $name => $column) {
      if (!is_string($name) || !isset($column['type'])) {
        $errors['columns'] = 'One or more rows are invalid';
        break;
      }
    }
  }

  if (!empty($errors)) {
    return $response
      ->setError(true, 'Invalid Parameters')
      ->setValidation($errors);
  }

  //----------------------------//
  // 3. Prepare Data
  $resource = $this('storm');
  //if the table doesnt exist
  if (empty($resource->getTables($table))){
    //create it instead
    return $this('event')->emit('storm-create', $request, $response);
  }

  //we need the original schema to compare
  $original = [
    'columns' => $resource->getColumns($table),
    'primary' => []
  ];

  $primaries = $resource->getColumns($table, "`Key` = 'PRI'");

  foreach ($primaries as $column) {
    $original['primary'][] = $column['Field'];
  }

  //determine the create schema
  $query = $resource->getAlterQuery($table);

  //remove or change fields
  $exists = [];
  foreach ($original['columns'] as $current) {
    //don't do primary
    if (in_array($current['Field'], $original['primary'])) {
      continue;
    }

    $exists[] = $name = $current['Field'];

    //if there is no field in the data
    if (!isset($columns[$name])) {
      $query->removeField($name);
      continue;
    }

    $column = $columns[$name];

    $attributes = ['type' => $column['type']];

    if (isset($column['length'])) {
      $attributes['type'] .= '(' . $column['length'] . ')';
    }

    if (isset($column['default']) && strlen($column['default'])) {
      $attributes['default'] = $column['default'];
    } else if (!isset($column['required']) || !$column['required']) {
      $attributes['null'] = true;
    }

    if (isset($column['required']) && $column['required']) {
      $attributes['null'] = false;
    }

    if (isset($column['attribute']) && $column['attribute']) {
      $attributes['attribute'] = $column['attribute'];
    }

    $default = null;
    if (isset($attributes['default'])) {
      $default = $attributes['default'];
    }

    //if all matches
    if ($attributes['type'] === $current['Type']
      && $attributes['null'] == ($current['Null'] === 'YES')
      && $default === $current['Default']
    ) {
      continue;
    }

    //do the alter
    $query->changeField($name, $attributes);
  }

  //add fields
  foreach ($columns as $name => $column) {
    if (in_array($name, $exists)) {
      continue;
    }

    $attributes = ['type' => $column['type']];

    if (isset($column['length'])) {
      $attributes['type'] .= '(' . $column['length'] . ')';
    }

    if (isset($column['default']) && strlen($column['default'])) {
      $attributes['default'] = $column['default'];
    } else if (!isset($column['required']) || !$column['required']) {
      $attributes['null'] = true;
    }

    if (isset($column['required']) && $column['required']) {
      $attributes['null'] = false;
    }

    if (isset($column['attribute']) && $column['attribute']) {
      $attributes['attribute'] = $column['attribute'];
    }

    $query->addField($name, $attributes);

    if (isset($column['index']) && $column['index']) {
      $query->addKey($name, [$name]);
    }

    if (isset($column['unique']) && $column['unique']) {
      $query->addUniqueKey($name, [$name]);
    }

    if (isset($column['primary']) && $column['primary']) {
      $query->addPrimaryKey($name);
    }
  }

  //----------------------------//
  // 4. Process Data
  try {
    $resource->query((string) $query);
  } catch (SqlException $e) {
    return $response->setError(true, $e->getMessage());
  }

  $response->setError(false);
});

/**
 * Database create job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('storm-create', function (RequestInterface $request, ResponseInterface $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Get Data
  $table = $request->getStage('table');
  $primary = $request->getStage('primary');
  $columns = $request->getStage('columns');

  //make sure primary is an array
  if (!is_array($primary)) {
    $primary = [$primary];
  }

  //make sure columns is an array
  if (!is_array($columns)) {
    $columns = [];
  }

  //----------------------------//
  // 2. Validate Data
  $errors = [];
  //we need at least a table
  if (!trim($table)) {
    $errors['table'] = 'Table is required';
  }

  if (empty($columns) && empty($primary)) {
    $errors['columns'] = 'Empty columns';
  } else {
    //all columns should be an array (hash)
    foreach ($columns as $name => $column) {
      if (!is_string($name) || !isset($column['type'])) {
        $errors['columns'] = 'One or more rows are invalid';
        break;
      }
    }
  }

  if (!empty($errors)) {
    return $response
      ->setError(true, 'Invalid Parameters')
      ->setValidation($errors);
  }

  //----------------------------//
  // 3. Prepare Data
  $resource = $this('storm');

  //determine the create schema
  $query = $resource->getCreateQuery($table);

  foreach ($primary as $column) {
    if (!trim($column)) {
      continue;
    }

    $query
      ->addPrimaryKey($column)
      ->addField($column, [
        'type' => 'int(10)',
        'null' => false,
        'attribute' => 'UNSIGNED',
        'auto_increment' => count($primary) === 1,
      ]);
  }

  foreach ($columns as $name => $column) {
    $attributes = ['type' => $column['type']];

    if (isset($column['length'])) {
      $attributes['type'] .= '(' . $column['length'] . ')';
    }

    if (isset($column['default']) && strlen($column['default'])) {
      $attributes['default'] = $column['default'];
    } else if (!isset($column['required']) || !$column['required']) {
      $attributes['null'] = true;
    }

    if (isset($column['required']) && $column['required']) {
      $attributes['null'] = false;
    }

    if (isset($column['attribute']) && $column['attribute']) {
      $attributes['attribute'] = $column['attribute'];
    }

    $query->addField($name, $attributes);

    if (isset($column['index'])
      && $column['index']
      && $attributes['type'] !== 'TEXT'
      && $attributes['type'] !== 'BLOB'
    ) {
      $query->addKey($name, [$name]);
    }

    if (isset($column['unique'])
        && $column['unique']
        && $attributes['type'] !== 'TEXT'
        && $attributes['type'] !== 'BLOB'
    ) {
      $query->addUniqueKey($name, [$name]);
    }

    if (isset($column['primary'])
      && $column['primary']
      && $attributes['type'] !== 'TEXT'
      && $attributes['type'] !== 'BLOB'
    ) {
      $query->addPrimaryKey($name);
    }
  }

  //----------------------------//
  // 4. Process Data
  if ($request->getStage('drop')) {
    $this('event')->emit('storm-drop', $request, $response);
    if ($response->isError()) {
      return;
    }
  } else if (!empty($resource->getTables($table))){
    return $response->setError(true, 'Table exists');
  }

  try {
    $resource->query((string) $query);
  } catch (SqlException $e) {
    return $response->setError(true, $e->getMessage());
  }

  $response->setError(false);
});

/**
 * Database drop job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('storm-drop', function (RequestInterface $request, ResponseInterface $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Get Data
  $table = $request->getStage('table');

  //----------------------------//
  // 2. Validate Data
  $errors = [];
  //we need at least a table
  if (!trim($table)) {
    $errors['table'] = 'Table is required';
  }

  if (!empty($errors)) {
    return $response
      ->setError(true, 'Invalid Parameters')
      ->setValidation($errors);
  }

  //----------------------------//
  // 3. Prepare Data
  $resource = $this('storm');
  $query = 'DROP TABLE IF EXISTS ' . $table . ';';

  //----------------------------//
  // 4. Process Data
  try {
    $resource->query($query);
  } catch (SqlException $e) {
    return $response->setError(true, $e->getMessage());
  }

  $response->setError(false);
});

/**
 * Database rename job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('storm-rename', function (RequestInterface $request, ResponseInterface $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Get Data
  $table = $request->getStage('table');
  $name = $request->getStage('name');

  //----------------------------//
  // 2. Validate Data
  $errors = [];
  //we need at least a table
  if (!trim($table)) {
    $errors['table'] = 'Table is required';
  }

  if (!trim($name)) {
    $errors['name'] = 'Name is required';
  }

  if (!empty($errors)) {
    return $response
      ->setError(true, 'Invalid Parameters')
      ->setValidation($errors);
  }

  //----------------------------//
  // 3. Prepare Data
  $resource = $this('storm');
  $query = 'RENAME TABLE ' . $table . ' TO ' . $name . ';';

  //----------------------------//
  // 4. Process Data
  try {
    $resource->query($query);
  } catch (SqlException $e) {
    return $response->setError(true, $e->getMessage());
  }

  $response->setError(false);
});
