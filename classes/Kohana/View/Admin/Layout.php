<?php

class Kohana_View_Admin_Layout extends Kohana_View_Jade_Layout {

  var $layout_template = 'admin/layout';
  var $current_page = NULL;

  public function before()
  {
    // twitter bootstrap
    $this->vars['css'] = Twitterbootstrap::css();
    $this->vars['js'] = Twitterbootstrap::js();

    // jsondiffpatch -- https://github.com/benjamine/JsonDiffPatch
    $this->vars['js'][] = URL::site('js/jsondiffpatch.js');
    $this->vars['js'][] = URL::site('js/bootstrap-datepicker.js');
    $this->vars['js'][] = URL::site('js/locales/bootstrap-datepicker.cs.js');
    $this->vars['css'][] = URL::site('css/jsondiffpatch.css');
    $this->vars['css'][] = URL::site('css/datepicker.css');
    
    $user = Auth::instance()->get_user();

    $this->vars['objects'] = Namlouvani::objects();
    $this->vars['username'] = $user->username;
    $this->vars['admin'] = $user->is_admin;
    $this->vars['admin_console'] = $user->pref('admin:console');
    
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
