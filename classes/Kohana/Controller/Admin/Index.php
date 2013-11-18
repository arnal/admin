<?php

class Kohana_Controller_Admin_Index extends Controller_Admin_Basic {

  public function action_index()
  {
    $view = new View_Admin_Layout('admin/index');
    $view->current_page = 'hp';
    $view->content->stats = NULL;
    $this->response->body($view->render());
  }

  public function action_about()
  {
    $view = new View_Admin_Layout('admin/about');
    $view->current_page = 'about';

    $author = Kohana::$config->load('site.author');
    $view->content->author = $author['name'].' &lt;'.HTML::mailto($author['email'], $author['email']).'&gt;';

    $this->response->body($view->render());
  }
}
