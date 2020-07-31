<?php //-->

use Cradle\IO\Request\RequestInterface;
use Cradle\IO\Response\ResponseInterface;

/**
 * Database insert Job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('system-store-insert', function (RequestInterface $request, ResponseInterface $response) {
  //just relay
  $this('event')->emit('storm-insert', $request, $response);
});

/**
 * Database delete Job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('system-store-delete', function (RequestInterface $request, ResponseInterface $response) {
  //just relay
  $this('event')->emit('storm-delete', $request, $response);
});

/**
 * Database search Job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('system-store-search', function (RequestInterface $request, ResponseInterface $response) {
  //just relay
  $this('event')->emit('storm-search', $request, $response);
});

/**
 * Database update Job
 *
 * @param Request $request
 * @param Response $response
 */
$this('event')->on('system-store-update', function (RequestInterface $request, ResponseInterface $response) {
  //just relay
  $this('event')->emit('storm-update', $request, $response);
});
