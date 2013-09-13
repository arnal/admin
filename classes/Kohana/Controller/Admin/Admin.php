<?php

class Kohana_Controller_Admin_Admin extends Controller_Admin_Basic {

  public function action_prefs()
  {
    $prefs = Kohana::$config->load('userprefs')->as_array();
    $user = Auth::instance()->get_user();

    // zmena
    if(isset($_POST) AND isset($_GET['update']) AND $_GET['update'])
    {
      $config = array();
      foreach($prefs as $cat_id => $cat)
      {
        foreach($cat['items'] as $pref_id => $pref)
        {
          $pref_rid = join(':',array($cat_id, $pref_id));
          if(isset($_POST[$pref_rid]) AND ($_POST[$pref_rid] != $pref['default'] OR $user->pref($pref_rid) != NULL))
          {
            $val = $_POST[$pref_rid];
            if($val != '')
            {
              $config[$pref_rid] = $val;
            }
          }
        }
      }
      if(count($config) > 0)
      {
        $user->prefs = json_encode($config);
        $user->save();
      } 

      Arnal::msg('Vaše nastavení bylo uloženo.');
      $this->redirect('prefs');
      return;
    }

    $view = new View_Admin_Layout('admin/prefs');
    $view->content->prefs = $prefs;
    $view->content->user_prefs = $user->prefs();

    $this->response->body($view->render());
  }

}


