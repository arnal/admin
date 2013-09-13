<?php defined( 'SYSPATH') or die( 'No direct script access.');

Route::set('object_list', '<type>',
  array(
    'type' => Arnal::objects_plural_route(),
  ))
  ->defaults(array(
    'controller' => 'admin_object',
    'action'     => 'list',
  ));

Route::set('admin/object', '<type>/<id>(.<format>)(/<action>)',
  array(
    'type' => Arnal::objects_route(),
    'id' => '\d+',
    'format' => '(json)',
    'action' => '(show|delete|edit)',
  ))
  ->defaults(array(
    'controller' => 'admin_object',
    'action'     => 'show',
  ));

Route::set('admin/object_new', '<type>/create',
  array(
    'type' => Arnal::objects_route(),
  ))
  ->defaults(array(
    'controller' => 'admin_object',
    'action'     => 'create',
  ));

Route::set('admin/login', 'login')
  ->defaults(array(
    'controller' => 'admin_login',
    'action'     => 'index',
  ));

Route::set('admin/prefs', 'prefs')
  ->defaults(array(
    'controller' => 'admin_admin',
    'action'     => 'prefs',
  ));

Route::set('admin/logout', 'logout')
  ->defaults(array(
    'controller' => 'admin_login',
    'action'     => 'logout',
  ));

Route::set('admin/default', '(<controller>(/<action>(/<id>)))')
  ->defaults(array(
    'controller' => 'admin_index',
    'action'     => 'index',
  ));

Route::set('admin/ajx', 'ajx')
  ->defaults(array(
    'controller' => 'ajx',
    'action'     => 'index',
  ));

