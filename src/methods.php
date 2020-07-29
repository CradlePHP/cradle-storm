<?php //-->

use Cradle\Package\System\Fieldset;

$this->package('/module/cradle-system-storm')

  ->addMethod('formatField', function ($value, $type) {
    switch ($type) {
      case 'file':
      case 'image':
      case 'filelist':
      case 'imagelist':
        return $this->upload($value);
      case 'created':
      case 'updated':
      case 'datetime':
        $value = $value === 'NOW()' ? date('Y-m-d H:i:s'): $value;
        return trim($value) ? date('Y-m-d H:i:s', strtotime($value)): null;
      case 'date':
        $value = $value === 'NOW()' ? date('Y-m-d H:i:s'): $value;
        return trim($value) ? date('Y-m-d', strtotime($value)): null;
      case 'time':
        $value = $value === 'NOW()' ? date('Y-m-d H:i:s'): $value;
        return trim($value) ? date('H:i:s', strtotime($value)): null;
      case 'password':
        return password_hash($value, PASSWORD_DEFAULT);
      case 'md5':
        return md5($value);
      case 'sha1':
        return sha1($value);
      case 'active':
      case 'checkbox':
      case 'switch':
        return (int) !!$value;
      case 'uuid':
      case 'token':
        return md5(uniqid());
        break;
      case 'number':
      case 'small':
        return is_numeric($value)? $value: null;
      case 'textarea':
      case 'wysiwyg':
      case 'code':
        //fix for textarea in textarea
        return str_replace('<\/textarea>', '</textarea>', $value);
      case 'latlng':
        $value = is_array($value)? $value: [0, 0];
        $value[0] = is_numeric($value[0]) ? $value[0]: 0;
        $value[1] = is_numeric($value[1]) ? $value[1]: 0;
        $value[0] = sprintf('%.8F', $value[0]);
        $value[1] = sprintf('%.8F', $value[1]);
        return $value;
    }

    return $value;
  })

  ->addMethod('formatValue', function ($value, array $field) {
    if ($field['field']['type'] !== 'fieldset') {
      return $this->formatField($value, $field['field']['type']);
    }

    $fieldset = Fieldset::load($field['field']['parameters']);
    $multiple = isset($field['field']['attributes']['data-multiple'])
      && !$field['field']['attributes']['data-multiple'];

    if (!$multiple) {
      //format single data
      return $this->formatData($value, $fieldset);
    }

    //case for multiple
    foreach ($value as $index => $row) {
      $value[$index] = $this->formatData($row, $fieldset);
    }

    return $value;
  })

  ->addMethod('formatData', function (array $data, Fieldset $fieldset) {
    $fields = $fieldset->getFields();
    $table = $fieldset->getName();

    foreach ($fields as $key => $field) {
      //if there's no data
      if (!isset($data[$key])) {
        //no need to format
        continue;
      }

      $value = $data[$key];
      if (isset($data[$key]['value'], $data[$key]['bind'])) {
        $data[$key]['value'] = $this->formatValue($data[$key]['value'], $field);
        continue;
      }

      $data[$key] = $this->formatValue($data[$key], $field);
    }

    return $data;
  })

  ->addMethod('flattenData', function (array $data) {
    foreach ($data as $name => $value) {
      if (isset($value['value'], $value['bind'])
        && (is_array($value['value']) || is_object($value['value']))
      ) {
        $data[$name]['value'] = json_encode($value['value'], JSON_NUMERIC_CHECK);
        continue;
      }

      if (is_array($value) || is_object($value)) {
        $data[$name] = json_encode($value, JSON_NUMERIC_CHECK);
      }
    }

    return $data;
  })

  ->addMethod('expandData', function (array $data, Fieldset $fieldset) {
    $fields = $fieldset->getFields();
    $table = $fieldset->getName();

    $jsonFields = [
      'multiselect',
      'checkboxes',
      'filelist',
      'imagelist',
      'tag',
      'textlist',
      'textarealist',
      'wysiwyglist',
      'meta',
      'multirange',
      'rawjson',
      'fieldset',
      'table',
      'latlng'
    ];

    foreach ($fields as $field) {
      $name = $table . '_' . $field['name'];
      //if there's no data
      if (!isset($data[$name])
        || is_array($data[$name])
        || is_object($data[$name])
        || !in_array($field['field']['type'], $jsonFields)
      ) {
        //no need to format
        continue;
      }

      $data[$name] = json_decode($data[$name], true);
    }

    return $data;
  })

  ->addMethod('upload', function ($data) {
    $results = cradle()->method('file-upload', [
      'data' => $data
    ]);

    if ($results) {
      return $results;
    }

    return $data;
  })

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
