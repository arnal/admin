<?php

class Kohana_Controller_Admin_Login extends Controller_Admin_Basic {

  public function action_logout()
  {
    Auth::instance()->logout();
    $this->redirect('/login');
  }

  public function action_index()
  {
    session_start();
    /*$pass = '12opicek';
    $reg = array('username'=>'tree', 'password'=>$pass, 'email'=>'jan.stransky@arnal.cz', 'password_confirm' =>$pass);
    $user = ORM::factory('User')->create_user($reg, array('username', 'password', 'email'));
    $user->save();
  var_dump($user);
exit;*/


    $post = $this->request->post();
    if(isset($_GET['do']))
    {
      try {
      $success = Auth::instance()->login($post['username'], $post['password'], TRUE);
      } catch (Exception $e) {
        $success = FALSE;
      }

      if($success)
      {
        Arnal::msg('Vítejte <strong>'.$post['username'].'</strong>, byl jste úspěšně nalogován jako '.(Auth::instance()->get_user()->is_admin ? 'administrátor' : 'moderátor').'.','success');
          
        $user = Auth::instance()->get_user();
        Arnal::log('User.login', array(), 'User', $user->id);
        return $this->redirect(isset($_GET['r']) ? urldecode($_GET['r']) : '/');
      }
      else
      {
        Arnal::msg('<strong>Špatný login</strong>. Neplatné přihlašovací údaje.','error');
        return $this->redirect('/login');
      }
    }

    $view = View::factory('admin/login');
    $view->site_config = Kohana::$config->load('site')->as_array();

    if(isset($_SESSION['msg']))
    {
      $view->msg = $_SESSION['msg'];
      unset($_SESSION['msg']);
    }
    $this->response->body($view);
  }
}
