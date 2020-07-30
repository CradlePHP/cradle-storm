<?php //-->

/**
 * Database insert Job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('storm-insert', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Set the Resources
  if (!$request->meta('storm-insert')) {
    //make the resource
    $request->meta('storm-insert', $this('storm')->insert());
  }

  //----------------------------//
  // 2. Get Data
  $table = $request->getStage('table');
  //eg. [[product_title => ['value' => 'Some Title', 'bind' => true]]]
  $rows = $request->getStage('rows');

  if (!is_array($rows) && is_array($request->getStage('data'))) {
    $rows = [$request->getStage('data')];
  } else if (!is_array($rows)) {
    $rows = [];
  }

  //----------------------------//
  // 3. Validate Data
  $errors = [];
  //we need at least a table
  if (!trim($table)) {
    $errors['table'] = 'Table is required';
  }

  if (empty($rows)) {
    $errors['rows'] = 'Empty rows';
  } else {
    //all rows should be an array (hash)
    foreach ($rows as $row) {
      if (!is_array($row)) {
        $errors['rows'] = 'One or more rows are invalid';
        break;
      }

      foreach ($row as $value) {
        if (!is_scalar($value) && !isset($value['value'], $value['bind'])) {
          $errors['rows'] = 'One or more rows are invalid';
          break;
        }
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
  $resource = $request->meta('storm-insert')->setTable($table);

  //----------------------------//
  // 5. Process Data
  foreach ($rows as $index => $row) {
    //remove columns that are not in this table
    $row = $this('storm')->getValidData($table, $row);
    //loop through each key
    foreach ($row as $key => $value) {
      if (is_scalar($value)) {
        $resource->set($key, $value, true, $index);
      } else if (is_array($value)) {
        $resource->set($key, $value['value'], $value['bind'], $index);
      }
    }
  }

  $resource->query();
  $id = $resource->getDatabase()->getLastInsertedId();
  $response->setError(false)->setResults($id);
});

/**
 * Database delete Job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('storm-delete', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Set the Resources
  if (!$request->meta('storm-remove')) {
    //make the resource
    $request->meta('storm-remove', $this('storm')->remove());
  }

  //----------------------------//
  // 2. Get Data
  $table = $request->getStage('table');
  //eg. joins = [['type' => 'inner', 'table' => 'product', 'where' => 'product_id']]
  $joins = $request->getStage('joins');
  //eg. filters = [['where' => 'product_id =%s', 'binds' => [1]]]
  $filters = $request->getStage('filters');

  if (!is_array($joins)) {
    $joins = [];
  }

  if (!is_array($filters)) {
    $filters = [];
  }

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
  // 3. Prepare Data
  $resource = $request->meta('storm-remove')->setTable($table);

  $validJoinTypes = ['inner', 'left', 'right', 'outer'];
  foreach($joins as $join) {
    if (!isset($join['type'], $join['table'], $join['where'])
      || !in_array($join['type'], $validJoinTypes)
    ) {
      continue;
    }

    $link = 'On';
    if (preg_match('^[a-zA-Z0-9_]+$', $join['where'])) {
      $link = 'Using';
    }

    $method = sprintf('%sJoin%s', $join['type'], $link);
    $resource->$method($join['table'], $join['where']);
  }

  foreach($filters as $filter) {
    if (!isset($filter['where'])) {
      continue;
    }

    if (!isset($filter['binds']) || !is_array($filter['binds'])) {
      $filter['binds'] = [];
    }

    $binds = $filter['binds'];

    $resource->addFilter($filter['where'], ...$binds);
  }

  //----------------------------//
  // 5. Process Data
  $results = $resource->query();
  $response->setError(false)->setResults($results);
});

/**
 * Database search Job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('storm-search', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Set the Resources
  if (!$request->meta('storm-search')) {
    //make the resource
    $request->meta('storm-search', $this('storm')->search());
  }

  //----------------------------//
  // 2. Get Data
  $table = $request->getStage('table');
  $columns = $request->getStage('columns');
  //eg. joins = [['type' => 'inner', 'table' => 'product', 'where' => 'product_id']]
  $joins = $request->getStage('joins');
  //eg. filters = [['where' => 'product_id =%s', 'binds' => [1]]]
  $filters = $request->getStage('filters');
  //eg. group = ['product_id']
  $group = $request->getStage('group');
  //eg. having = [['where' => 'product_id =%s', 'binds' => [1]]]
  $having = $request->getStage('having');
  //eg. sort = ['product_id' => 'ASC']
  $sort = $request->getStage('sort');

  if (!$columns) {
    $columns = '*';
  }

  if (!is_array($joins)) {
    $joins = [];
  }

  if (!is_array($filters)) {
    $filters = [];
  }

  if (!is_array($group)) {
    $group = [];
  }

  if (!is_array($having)) {
    $having = [];
  }

  if (!is_array($sort)) {
    $sort = [];
  }

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
  $resource = $request
    ->meta('storm-search')
    ->setColumns($columns)
    ->setTable($table);

  $resource->from($table);

  if (!empty($columns)) {
    $resource->setColumns($columns);
  }

  $validJoinTypes = ['inner', 'left', 'right', 'outer'];
  foreach($joins as $join) {
    if (!isset($join['type'], $join['table'], $join['where'])
      || !in_array($join['type'], $validJoinTypes)
    ) {
      continue;
    }

    $link = 'On';
    if (preg_match('^[a-zA-Z0-9_]+$', $join['where'])) {
      $link = 'Using';
    }

    $method = sprintf('%sJoin%s', $join['type'], $link);
    $resource->$method($join['table'], $join['where']);
  }

  foreach($filters as $filter) {
    if (!isset($filter['where'])) {
      continue;
    }

    if (!isset($filter['binds']) || !is_array($filter['binds'])) {
      $filter['binds'] = [];
    }

    $binds = $filter['binds'];

    $resource->addFilter($filter['where'], ...$binds);
  }

  if (!empty($group)) {
    $resource->groupBy($group);
  }

  if (!empty($having)) {
    $resource->having($having);
  }

  if (!empty($sort)) {
    foreach ($sort as $column => $direction) {
      $resource->addSort($column, $direction);
    }
  }

  if (is_numeric($request->getStage('start'))) {
    $resource->setStart($request->getStage('start'));
  }

  if (is_numeric($request->getStage('range'))) {
    $resource->setRange($request->getStage('range'));
  }

  //----------------------------//
  // 5. Process Data
  $results = $resource->getRows();
  if ($request->hasStage('with_total')) {
    $results = [
      'rows' => $results,
      'total' => $resource->getTotal()
    ];
  }

  $response->setError(false)->setResults($results);
});

/**
 * Database update Job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('storm-update', function($request, $response) {
  //----------------------------//
  // 0. Abort on Errors
  if ($response->isError() || $response->hasResults()) {
    return;
  }

  //----------------------------//
  // 1. Set the Resources
  if (!$request->meta('storm-update')) {
    //make the resource
    $request->meta('storm-update', $this('storm')->update());
  }

  //----------------------------//
  // 2. Get Data
  $table = $request->getStage('table');
  //eg. data = [product_title => ['value' => 'Some Title', 'bind' => true]]
  $data = $request->getStage('data');
  //eg. joins = [['type' => 'inner', 'table' => 'product', 'where' => 'product_id']]
  $joins = $request->getStage('joins');
  //eg. filters = [['where' => 'product_id =%s', 'binds' => [1]]]
  $filters = $request->getStage('filters');

  if (!is_array($joins)) {
    $joins = [];
  }

  if (!is_array($filters)) {
    $filters = [];
  }

  if (!is_array($data)) {
    $data = [];
  }

  //----------------------------//
  // 3. Validate Data
  $errors = [];
  //we need at least a table
  if (!trim($table)) {
    $errors['table'] = 'Table is required';
  }

  if(!is_array($data) || empty($data)) {
    $errors['data'] = 'Data is required';
  }

  foreach ($data as $value) {
    if (!is_scalar($value) && !isset($value['value'], $value['bind'])) {
      $errors['data'] = 'Data format is invalid';
      break;
    }
  }

  if (!empty($errors)) {
    return $response
      ->setError(true, 'Invalid Parameters')
      ->setValidation($errors);
  }

  //----------------------------//
  // 4. Prepare Data
  $resource = $request->meta('storm-update')->setTable($table);

  $validJoinTypes = ['inner', 'left', 'right', 'outer'];
  foreach($joins as $join) {
    if (!isset($join['type'], $join['table'], $join['where'])
      || !in_array($join['type'], $validJoinTypes)
    ) {
      continue;
    }

    $link = 'On';
    if (preg_match('^[a-zA-Z0-9_]+$', $join['where'])) {
      $link = 'Using';
    }

    $method = sprintf('%sJoin%s', $join['type'], $link);
    $resource->$method($join['table'], $join['where']);
  }

  foreach($filters as $filter) {
    if (!isset($filter['where'])) {
      continue;
    }

    if (!isset($filter['binds']) || !is_array($filter['binds'])) {
      $filter['binds'] = [];
    }

    $binds = $filter['binds'];

    $resource->addFilter($filter['where'], ...$binds);
  }

  //remove columns that are not in this table
  $data = $this('storm')->getValidData($table, $data);
  //loop through each key
  foreach ($data as $key => $value) {
    if (is_scalar($value)) {
      $resource->set($key, $value, true);
    } else if (is_array($value)) {
      $resource->set($key, $value['value'], $value['bind']);
    }
  }

  //----------------------------//
  // 5. Process Data
  $results = $resource->query();
  $response->setError(false)->setResults($results);
});
