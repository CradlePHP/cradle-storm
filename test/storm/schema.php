<?php

use PHPUnit\Framework\TestCase;

use Cradle\Framework\FrameworkHandler;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-07-27 at 02:11:02.
 */
class Cradle_System_Storm_Event_Schema_Test extends TestCase
{
  protected $object;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    //this is the OOP version of cradle
    $this->object = new FrameworkHandler;
    $testRoot = dirname(__DIR__);
    $packageRoot = dirname($testRoot);

    //now register storm
    $this->object->register('cradlephp/cradle-storm', $packageRoot);

    $this->object->package('storm')
      ->loadPDO(include $testRoot . '/assets/mysql.php');
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown()
  {
  }

  /**
   */
  public function testStormCreate()
  {
    $cradle = $this->object;
    $payload = $cradle->makePayload();
    $payload['request']->setStage([
      'drop' => true,
      'table' => 'foo',
      'primary' => 'foo_id',
      'columns' => [
        'foo_title' => [
          'type' => 'varchar',
          'length' => 255,
          'required' => true,
          'index' => true
        ],
        'foo_slug' => [
          'type' => 'varchar',
          'length' => 255,
          'required' => true,
          'unique' => true
        ],
        'foo_detail' => [
          'type' => 'text',
          'null' => true
        ],
        'foo_amount' => [
          'type' => 'int',
          'length' => 5,
          'attribute' => 'unsigned',
          'default' => 0,
          'index' => true
        ]
      ]
    ]);

    $cradle('event')->method(
      'storm-create',
      $payload['request'],
      $payload['response']
    );

    $this->assertTrue(!$payload['response']->isError());

    $table = $cradle('storm')->getTables('foo');
    $this->assertTrue(!empty($table));
  }

  /**
   */
  public function testStormAlter()
  {
    $cradle = $this->object;
    $payload = $cradle->makePayload();

    $payload['request']->setStage([
      'table' => 'foo',
      'columns' => [
        'foo_title' => [
          'type' => 'varchar',
          'length' => 254,
          'required' => true,
          'index' => true
        ],
        'foo_detail' => [
          'type' => 'text'
        ],
        'foo_amount' => [
          'type' => 'int',
          'length' => 6,
          'default' => 0,
          'index' => true
        ]
      ]
    ]);

    $cradle('event')->method(
      'storm-alter',
      $payload['request'],
      $payload['response']
    );

    $this->assertTrue(!$payload['response']->isError());
  }
}
