<?php //-->

use PDO as Resource;
use Cradle\Storm\SqlFactory;
use Cradle\Storm\SqlException;

/**
 * Database alter job
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('storm-alter', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Set the Resources
  if (!$request->meta('mysql')) {
    //get the name
    $dbname = $request->getStage('dbname');
    if (!$dbname) {
      $dbname = 'main';
    }

    //get the config
    $config = $this->package('global')->config('services', 'mysql-' . $dbname);

    //if no config
    if (!$config || !isset($config['active']) || !$config['active']) {
      //do nothing as a fallback
      return;
    }

    //make the resource
    $request->meta('mysql', new Resource($config));
  }

  if (!$request->meta('storm')) {
    //make the resource
    $request->meta('storm', SqlFactory::load($request->meta('mysql')));
  }

  //----------------------------//
  // 2. Get Data
  $table = $request->getStage('table');
  $primary = $request->getStage('primary');
  $columns = $request->getStage('columns');

  //make sure primary is an array
  if (!is_array($primary)) {
    $primary = [$primary];
  }

  //----------------------------//
  // 3. Validate Data
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
  // 4. Prepare Data
  $resource = $request->meta('storm');
  //if the table doesnt exist
  if (empty($resource->getTables($table))){
    //return error
    return $response->setError(true, 'Table does not exist');
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
  // 5. Process Data
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
$this->on('storm-create', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Set the Resources
  if (!$request->meta('mysql')) {
    //get the name
    $dbname = $request->getStage('dbname');
    if (!$dbname) {
      $dbname = 'main';
    }

    //get the config
    $config = $this->package('global')->config('services', 'mysql-' . $dbname);

    //if no config
    if (!$config || !isset($config['active']) || !$config['active']) {
      //do nothing as a fallback
      return;
    }

    //make the resource
    $request->meta('mysql', new Resource($config));
  }

  if (!$request->meta('storm')) {
    //make the resource
    $request->meta('storm', SqlFactory::load($request->meta('mysql')));
  }

  //----------------------------//
  // 2. Get Data
  $table = $request->getStage('table');
  $primary = $request->getStage('primary');
  $columns = $request->getStage('columns');

  //make sure primary is an array
  if (!is_array($primary)) {
    $primary = [$primary];
  }

  //----------------------------//
  // 3. Validate Data
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
  // 4. Prepare Data
  $resource = $request->meta('storm');

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
        'auto_increment' => true,
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
  // 5. Process Data
  if ($request->getStage('drop')) {
    $this->trigger('storm-drop', $request, $response);
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
$this->on('storm-drop', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Set the Resources
  if (!$request->meta('mysql')) {
    //get the name
    $dbname = $request->getStage('dbname');
    if (!$dbname) {
      $dbname = 'main';
    }

    //get the config
    $config = $this->package('global')->config('services', 'mysql-' . $dbname);

    //if no config
    if (!$config || !isset($config['active']) || !$config['active']) {
      //do nothing as a fallback
      return;
    }

    //make the resource
    $request->meta('mysql', new Resource($config));
  }

  if (!$request->meta('storm')) {
    //make the resource
    $request->meta('storm', SqlFactory::load($request->meta('mysql')));
  }

  //----------------------------//
  // 2. Get Data
  $table = $request->getStage('table');

  //----------------------------//
  // 3. Validate Data
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
  // 4. Prepare Data
  $resource = $request->meta('storm');
  $query = 'DROP TABLE IF EXISTS ' . $table . ';';

  //----------------------------//
  // 5. Process Data
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
$this->on('storm-rename', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Set the Resources
  if (!$request->meta('mysql')) {
    //get the name
    $dbname = $request->getStage('dbname');
    if (!$dbname) {
      $dbname = 'main';
    }

    //get the config
    $config = $this->package('global')->config('services', 'mysql-' . $dbname);

    //if no config
    if (!$config || !isset($config['active']) || !$config['active']) {
      //do nothing as a fallback
      return;
    }

    //make the resource
    $request->meta('mysql', new Resource($config));
  }

  if (!$request->meta('storm')) {
    //make the resource
    $request->meta('storm', SqlFactory::load($request->meta('mysql')));
  }

  //----------------------------//
  // 2. Get Data
  $table = $request->getStage('table');
  $name = $request->getStage('name');

  //----------------------------//
  // 3. Validate Data
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
  // 4. Prepare Data
  $resource = $request->meta('storm');
  $query = 'RENAME TABLE ' . $table . ' TO ' . $name . ';';

  //----------------------------//
  // 5. Process Data
  try {
    $resource->query($query);
  } catch (SqlException $e) {
    return $response->setError(true, $e->getMessage());
  }

  $response->setError(false);
});
