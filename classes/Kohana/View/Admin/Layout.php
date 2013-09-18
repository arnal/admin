<?php

class Kohana_View_Admin_Layout extends Kohana_View_Jade_Layout {

  var $layout_template = 'admin/layout';
  var $current_page = NULL;
  var $site_config;

  public function before()
  {
    $user = Auth::instance()->get_user();

    $this->site_config = Kohana::$config->load('site')->as_array();

    // twitter bootstrap
    $this->vars['css'] = Twitterbootstrap::css();
    $this->vars['js'] = Twitterbootstrap::js();

    // jsondiffpatch -- https://github.com/benjamine/JsonDiffPatch
    $this->vars['js'][] = URL::site('js/jsondiffpatch.js');
    $this->vars['js'][] = URL::site('js/bootstrap-datepicker.js');
    $this->vars['js'][] = URL::site('js/locales/bootstrap-datepicker.cs.js');
    $this->vars['css'][] = URL::site('css/jsondiffpatch.css');
    $this->vars['css'][] = URL::site('css/datepicker.css');

    $this->vars['objects'] = Arnal::$schema->load_all();
    $this->vars['menu'] = $this->menu();

    $this->vars['username'] = $user->username;
    $this->vars['admin'] = $user->is_admin;
    $this->vars['admin_console'] = $user->pref('admin:console');
    $this->vars['site_config'] = $this->site_config;

    // set subtemplate basic variables
    $this->content->site_config = $this->site_config;
    
    if(isset($_SESSION['msg']))
    {
      $this->vars['msg'] = $_SESSION['msg']; 
      unset($_SESSION['msg']);
    }
    else
    {
      $this->vars['msg'] = FALSE;
    }
  }

  public function _menuitem_schema($schema_name)
  {
    $schema = Arnal::$schema->load($schema_name);
    return array(
      'name' => $schema['name_plural'],
      'code' => $schema['plural'],
      'url' => $schema['home_url'],
      'admin' => $schema['admin'],
    );
  }

  public function menu(array $sections = NULL)
  {
    if($sections === NULL)
    {
      $sections = Kohana::$config->load('admin.sections');
    }

    $menu = array();
    foreach($sections as $item)
    {
      $cur = array();

      if(isset($item['type']) AND $item['type'] === 'list')
      {
        $cur = $item;
        $cur['sections'] = $this->menu($item['sections']);
      }
      else if(isset($item['schema']))
      {
        $cur = $this->_menuitem_schema($item['schema']);
      }
      else
      {
        $cur = $item;
      }

      if(empty($cur['url']))
      {
        $cur['url'] = URL::site('');
      }

      $menu[] = $cur;
    }
    return $menu;
  }

  public function before_render()
  {
    if(Auth::instance()->get_user()->pref('admin:profile'))
    {
      $this->vars['profiler'] = '@@@PROFILER@@@';
    }
    $this->vars['current_page'] = $this->current_page;
  }

  public function render($template=NULL)
  {
    $out = parent::render($template);
    $out = str_replace('@@@PROFILER@@@', View::factory('profiler/stats'), $out);
    return $out;
  }
}
