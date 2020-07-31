<?php //-->

use Cradle\Package\System\Fieldset;

$this->package('cradlephp/cradle-storm')

  ->addMethod('getFieldSchema', function (array $field) {
    $schemas = [
      'json' => ['type' => 'JSON'],
      'string' => ['type' => 'VARCHAR', 'length' => 255],
      'text' => ['type' => 'TEXT'],
      'date' => ['type' => 'date'],
      'time' => ['type' => 'time'],
      'datetime' => ['type' => 'datetime'],
      'created' => ['type' => 'datetime'],
      'updated' => ['type' => 'datetime'],
      'week' => ['type' => 'INT', 'length' => 2, 'attribute' => 'unsigned'],
      'month' => ['type' => 'INT', 'length' => 2, 'attribute' => 'unsigned'],
      'active' => ['type' => 'INT', 'length' => 1, 'attribute' => 'unsigned'],
      'bool' => ['type' => 'INT', 'length' => 1, 'attribute' => 'unsigned'],
      'small' => ['type' => 'INT', 'length' => 1],
      'price' => ['type' => 'FLOAT', 'length' => '10,2']
    ];

    foreach ($schemas as $type => $schema) {
      if (in_array($type, $field['types'])) {
        return $schema;
      }
    }

    if (in_array('number', $field['types'])) {
      $length = [0, 0];

      $unsigned = isset($field['field']['attributes']['min'])
        && is_numeric($field['field']['attributes']['min'])
        && $field['field']['attributes']['min'] >= 0;

      foreach(['min', 'max', 'step'] as $attribute) {
        if (isset($field['field']['attributes'][$attribute])
          && is_numeric($field['field']['attributes'][$attribute])
        ) {
          $numbers = explode('.', (string) $field['field']['attributes'][$attribute]);
          if (strlen($numbers[0]) > $length[0]) {
            $length[0] = strlen($numbers[0]);
          }

          if (strlen($numbers[1]) > $length[1]) {
            $length[1] = strlen($numbers[1]);
          }
        }
      }

      if (!$length[0]) {
        $length[0] = 10;
      }

      if (!$length[1]) {
        if (in_array('float', $field['types'])) {
          $length[1] = 10;
        } else {
          unset($length[1]);
        }
      }

      if (count($length) == 2) {
        $schema = ['type' => 'FLOAT', 'length' => implode(',', $length)];
      } else {
        $schema = ['type' => 'INT', 'length' => (int) $length[0]];
      }

      if ($unsigned) {
        $schema['attribute'] = 'unsigned';
      }

      return $schema;
    }

    return ['type' => 'VARCHAR', 'length' => 255];
  })

;
