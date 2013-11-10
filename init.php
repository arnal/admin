<?php defined( 'SYSPATH') or die( 'No direct script access.');

$admin_prefix = Kohana::$config->load('admin')->get('url_prefix');

Route::set('admin/object_list', $admin_prefix.'<type>',
  array(
    'type' => Arnal::objects_plural_route(),
  ))
  ->defaults(array(
    'controller' => 'admin_object',
    'action'     => 'list',
  ));

Route::set('admin/object', $admin_prefix.'<type>/<id>(.<format>)(/<action>)',
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

Route::set('admin/object_new', $admin_prefix.'<type>/create',
  array(
    'type' => Arnal::objects_route(),
  ))
  ->defaults(array(
    'controller' => 'admin_object',
    'action'     => 'create',
  ));

Route::set('admin/login', $admin_prefix.'login')
  ->defaults(array(
    'controller' => 'admin_login',
    'action'     => 'index',
  ));

Route::set('admin/prefs', $admin_prefix.'prefs')
  ->defaults(array(
    'controller' => 'admin_admin',
    'action'     => 'prefs',
  ));

Route::set('admin/logout', $admin_prefix.'logout')
  ->defaults(array(
    'controller' => 'admin_login',
    'action'     => 'logout',
  ));

Route::set('admin/about', 'about')
  ->defaults(array(
    'controller' => 'admin_index',
    'action'     => 'about',
  ));

if(substr($admin_prefix, mb_strlen($admin_prefix)-1, 1) == "/")
{
  $index_route = rtrim($admin_prefix, '/').'(/<controller>(/<action>(/<id>)))';
} 
else 
{
  $index_route = $admin_prefix.'(<controller>(/<action>(/<id>)))';
}

Route::set('admin/default', $index_route)
->defaults(array(
  'controller' => 'admin_index',
  'action'     => 'index',
));

Route::set('admin/ajx', 'ajx')
  ->defaults(array(
    'controller' => 'ajx',
    'action'     => 'index',
  ));

