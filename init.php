<?php defined( 'SYSPATH') or die( 'No direct script access.');

Route::set('object_list', '<type>',
  array(
    'type' => Namlouvani::objects_plural_route(),
  ))
  ->defaults(array(
    'controller' => 'admin_object',
    'action'     => 'list',
  ));

Route::set('object', '<type>/<id>(.<format>)(/<action>)',
  array(
    'type' => Namlouvani::objects_route(),
    'id' => '\d+',
    'format' => '(json)',
    'action' => '(show|delete|edit)',
  ))
  ->defaults(array(
    'controller' => 'admin_object',
    'action'     => 'show',
  ));

Route::set('object_new', '<type>/create',
  array(
    'type' => Namlouvani::objects_route(),
  ))
  ->defaults(array(
    'controller' => 'admin_object',
    'action'     => 'create',
  ));

Route::set('login', 'login')
  ->defaults(array(
    'controller' => 'admin_login',
    'action'     => 'index',
  ));

Route::set('prefs', 'prefs')
  ->defaults(array(
    'controller' => 'admin_admin',
    'action'     => 'prefs',
  ));

Route::set('logout', 'logout')
  ->defaults(array(
    'controller' => 'admin_login',
    'action'     => 'logout',
  ));

Route::set('default', '(<controller>(/<action>(/<id>)))')
  ->defaults(array(
    'controller' => 'admin_welcome',
    'action'     => 'index',
  ));

Route::set('ajx', 'ajx')
  ->defaults(array(
    'controller' => 'ajx',
    'action'     => 'index',
  ));

