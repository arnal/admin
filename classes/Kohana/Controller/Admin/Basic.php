<?php

class Kohana_Controller_Admin_Basic extends Controller {

  public function before()
  {
    //Auth::instance()->auto_login(TRUE);

    if($this->request->uri() != 'login' AND !Auth::instance()->logged_in())
    {
      $this->redirect('/login?r='.urlencode($this->request->uri()));
    }
  } 

  public function user()
  {
    return Auth::instance()->get_user();
  }

}
