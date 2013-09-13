<?php

class Kohana_Controller_Admin_Welcome extends Controller_Admin_Basic {

  public function action_index()
  {
    $view = new View_Admin_Layout('hp');
    $view->current_page = 'hp';
    $view->content->stats = Namlouvani::statistics();
    $this->response->body($view->render());
  }

}
