<?php

class Kohana_Controller_Admin_Index extends Controller_Admin_Basic {

  public function action_index()
  {
    $view = new View_Admin_Layout('admin/index');
    $view->current_page = 'hp';
    $view->content->stats = Namlouvani::statistics();
    $this->response->body($view->render());
  }

}