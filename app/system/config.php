<?php

$config = array();

$config['db'] = [
  'host' => '127.0.0.1',
  'name' => 'revisionary_app',
  'user' => 'root',
  'pass' => 'root'
];

$config['default_language'] = 'en';

define('domain' , 'revisionaryapp.com');
define('dir', realpath('.'));
define('controller', dir . '/app/controller');
define('view' , dir . '/app/view');
define('cache' , dir . '/assets/cache');
define('ssl' , (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? true : false);
define('secure_url', "https://secure." . domain);
define('unsecure_url', "http://new." . domain);
define('url', ssl ? secure_url : unsecure_url);