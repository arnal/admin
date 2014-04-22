<?php

class Kohana_Controller_Admin_Object extends Controller_Admin_Basic {

  public function check_rights($conf, $item)
  {
    if(isset($conf['access']) AND $conf['access'] == TRUE)
    {
      $cn = 'Model_'.ucfirst($conf['id']);
      $rights = $cn::access_rights();
    }
    else
    {
      $rights = FALSE;
    }
    if($rights)
    {
      foreach($rights['wheres'] as $w)
      {
        if($item instanceOf Kohana_Collection)
        {
          $item->where[] = array($w[0], $w[1], $w[2]);
        }
        else
        {
          $item->where($w[0], $w[1], $w[2]);
        }
      }
    }
    return array($rights, $item, is_array($rights['conf']) ? array_merge($conf, $rights['conf']) : $conf);
  }

  public function check_access($type, $allow_edit=NULL)
  {
    $schema = Arnal::$schema->load($type);
    $is_admin = Auth::instance()->get_user()->is_admin;

    $res = !((isset($schema['admin']) AND $schema['admin'] == TRUE) AND !$is_admin);
    $res = ($res AND ($allow_edit !== FALSE));
    if(!$res)
    {
      Arnal::msg('<strong>Přístup odepřen.</strong> K tomuhle nemáte přístup. Sorry.', 'warning');
    }
    return $res;
  }

  public function access_cities()
  {
    $cities = Auth::instance()->get_user()->allowed_cities;
    //var_dump($cities);
  }

  public function denied_redirect()
  {
    $referrer = $this->request->referrer();
    return $this->redirect($referrer ? $referrer : URL::site(NULL, TRUE));
  }

  public function action_edit()
  {
    $conf = Arnal::$schema->load($this->request->param('type'));

    $current_page = $conf['plural'];
    $current_type = $conf;

    $item = Document::factory(ucfirst($conf['id']));

    // uzivatelska prava nastavena na Modelu
    list($rights, $item, $conf) = $this->check_rights($conf, $item);

    if(!$this->check_access($conf['id'], $conf['allow_edit']))
    {
      return $this->denied_redirect();
    }


    if(isset($rights['lock']))
    {
      foreach($rights['lock'] as $lock)
      {
        $conf['cols'][$lock]['lock'] = TRUE;
      }
    }

    $item->where('id', '=', $this->request->param('id'))->find();

    if(!$item->loaded())
    {
      return $this->redirect(Route::get('admin/object_list')->url(array('type' => $conf['plural'])));
    }

    if(isset($_POST['do']))
    {
      $res = $this->_edit_do(ucfirst($current_type['id']), $item->id, $_POST);
      if($res)
      {
        $this->redirect($res->admin_url());
      }
    }


    error_reporting(E_ALL);
    $view = new View_Admin_Layout('admin/object_edit');

    $view->content->type = $conf;
    $view->content->item = $item;
    $view->content->cancel_url = $this->request->referrer();
    $view->content->cols = $item->render('edit', FALSE, $conf);

    $this->response->body($view->render());
  }

  public function action_delete()
  {
    $conf = Arnal::$schema->load($this->request->param('type'));
    if(!$this->check_access($conf['id'], $conf['allow_delete']))
    {
      return $this->denied_redirect();
    }
  
    $model_class = ucfirst($conf['id']);
    $item = ORM::factory($model_class);

    // uzivatelska prava nastavena na Modelu
    list($rights, $item) = $this->check_rights($conf, $item);

    $item->where('id', '=', $this->request->param('id'))->find();

    if($item->loaded())
    {

      $item_id = $item->id;
      $item_array = $item->as_array();
      $class_expr = explode('_', get_class($item));
      $class = $class_expr[1]; 

      $item->delete();

      Arnal::msg('Smazání objektu proběhlo úspěšně.');
      Arnal::log($class.'.delete', $item_array, $class, $item_id);
    }
    $this->redirect(Route::get('admin/object_list')->uri(array('type' => $conf['plural'])));
  }

  public function action_show()
  {
    $conf = Arnal::$schema->load($this->request->param('type'));
    if(!$this->check_access($conf['id']))
    {
      return $this->denied_redirect();
    }

    $id = $this->request->param('id');
    $item = Document::factory(ucfirst($conf['id']));

    // uzivatelska prava nastavena na Modelu
    list($rights, $item, $conf) = $this->check_rights($conf, $item);

    $item->where('id','=', $id)->find();

    if(!$item->loaded())
    {
      Arnal::msg('Neexistující záznam nebo k němu nemáte přístup.', 'warning');
      return $this->redirect(Route::get('admin/default')->uri());
    }

    // zpracovani ulozeni poznamky
    if(isset($_POST['do']) AND in_array($_POST['do'], array('note_save','note_admin_save')))
    {
      $col = ($_POST['do'] == 'note_admin_save' ? 'note_admin' : 'note');
      $note = strip_tags(addslashes($_POST[$col]));
      $class = ucfirst($conf['id']);

      $item->$col = $note;
      $item->save();

      Arnal::msg('Poznámka úspěšně uložena.');
      Arnal::log($class.'.'.$_POST['do'], $note, $class, $item->id);

      return $this->redirect($item->admin_url());
    }

    // other types -- JSON
    if($this->request->param('format') == 'json')
    {
      print json_encode($item->as_array(), JSON_PRETTY_PRINT);
      return true;
    }

    $view = new View_Admin_Layout('admin/object_show');
    $view->content->cols = $item->render('show');
    $view->content->cancel_url = $this->request->referrer(); 
    $view->content->edit_url = $item->admin_url().'/edit';
    $view->content->delete_url = $item->admin_url().'/delete';
    $view->content->history_url = URL::site(Route::get('admin/default')->uri() . '/' . 'logs'.URL::query(array('otype' => ucfirst($conf['id']), 'oid' => '^'.$item->id.'$')),TRUE);
    $view->content->type = $conf;
    $view->content->item = $item->as_array();
    if(in_array('note', array_keys($item->table_columns())))
    {
      $view->content->note_textarea = DT::factory('Text', $item->note)->input('note', array('style="width: 350px; height: 150px;"'));
    }
    if(Auth::instance()->get_user()->is_admin AND in_array('note_admin', array_keys($item->table_columns())))
    {
      $view->content->note_admin_textarea = DT::factory('Text', $item->note_admin)->input('note_admin', array('style="width: 350px; height: 150px;"'));
    }

    $this->response->body($view->render());
  }

  private function _edit_do($type, $id, $data)
  {
    $conf = Arnal::$schema->load(strtolower($type));
    $item = ORM::factory($type);

    // uzivatelska prava nastavena na Modelu
    list($rights, $item) = $this->check_rights($conf, $item);

    $item->where('id', '=', $id)->find();

    if(!$item->loaded())
    {
      return false;
    }

    foreach($item->table_columns() as $key=>$key_conf)
    {
      if($key == 'id')
      {
        continue;
      }
      $val = $data[$key];

      if(in_array($key, array_keys($item->table_columns())))
      {
        if(isset($conf['cols'][$key]) AND isset($conf['cols'][$key]['readonly']) AND $conf['cols'][$key]['readonly'] == TRUE)
        {
          continue;
        }
        elseif(isset($conf['cols'][$key]) AND isset($conf['cols'][$key]['dt']))
        {
          $dt = DT::factory($conf['cols'][$key]['dt'], $val, array('_col_name' => $key));
          $dt_config = $dt->config();
          if(isset($dt_config['password_mode']) AND $dt_config['password_mode'] == TRUE)
          {
            $dt_val = $dt->to_db();
            if(!empty($dt_val))
            {
              $item->set($key, $dt->to_db());
            }
          }
          else
          {
            $item->set($key, $dt->to_db());
          }
        }
        else
        {
          $item->set($key, $val);
        }
      }
    }

    $ok = $item->save();
    if(!$ok OR !$item->loaded())
    {
      Arnal::msg('Stala se chyba při ukládání do databáze.','warning');
      return FALSE;
    }

    Arnal::msg('Úpravy v pohodě prolezly do databáze.');
    Arnal::log($type.'.edit', $item->as_array(), $type, $item->id);
    return $item;
  }

  private function _create_do($type, $data)
  {
    $conf = Arnal::$schema->load(strtolower($type));
    $item = ORM::factory($type);
    foreach($data as $key=>$val)
    {
      if(in_array($key, array_keys($item->table_columns())))
      {
        if(isset($conf['cols'][$key]) AND isset($conf['cols'][$key]['dt']))
        {
          $dt = DT::factory($conf['cols'][$key]['dt'], $val, array('_col_name' => $key));
          $item->set($key, $dt->to_db());
        }
        else
        {
          $item->set($key, $val);
        }
      }
    }

    if(in_array('created_at', array_keys($item->table_columns())) AND empty($item->created_at))
    {
      $item->created_at = date('Y-m-d H:i:s');
    }

    $ok = $item->save();
    if(!$ok OR !$item->loaded())
    {
      return FALSE;
    }

    // pokud vytvarime uzivatele, musime vytvorit taky napojeni na roli Login
    if($type == 'User')
    {
      $item->add('roles', 1);
    }

    Arnal::msg('Vytvoření objektu proběhlo úspěšně.');
    Arnal::log($type.'.create', $item->as_array(), $type, $item->id);
    return $item;
  }

  public function action_create()
  {
    $conf = Arnal::$schema->load($this->request->param('type'));
    if(!$this->check_access($conf['id'], $conf['allow_create']))
    {
      return $this->denied_redirect();
    }

    $current_page = $conf['plural'];
    $current_type = $conf;

    if(isset($_POST['do']))
    {
      $res = $this->_create_do(ucfirst($current_type['id']), $_POST);
      if($res)
      {
        $this->redirect($res->admin_url());
      }
    }

    $item = Document::factory(ucfirst($current_type['id']));
  
    $view = new View_Admin_Layout('admin/object_create');
    $view->current_page = $current_page;
    $view->content->type = $current_type;
    $view->content->cols = $item->render('edit');
    $view->content->cancel_url = $this->request->referrer(); 
    $this->response->body($view->render());
  }

  public function _list_print($source, $conf)
  {
    $sort = $conf['print_sort'];
    if($sort)
    {
      $source->order_by = array($sort[0], $sort[1], $sort[2], $sort[3]);
    }
    $source->limit = 10000;

    $terms = array();

    $show_cols = $conf['print'];
    $out = "<style>table td { border: 1px solid black; padding: 5px;}</style>";
    $out .= "<table>";
    $out .= "<tr>";

    if(!empty($_GET['term']))
    {
      unset($show_cols[array_search('term',$show_cols)]);
      $term_mode = TRUE;
    }
    else
    {
      $term_mode = FALSE;
    }

    foreach($show_cols as $sc)
    {
      $col = $conf['cols'][$sc];
      $out .= '<th>'.$col['name'].'</th>';
    }
    $out .= "</tr>";
    foreach($source->render('export', array('id', 'rendered')) as $item)
    {
      $terms[$item['cols']['term']] = $item['cols']['term'];
      $out .= "<tr>";
      foreach($show_cols as $sc)
      {
        $col = $item['cols'][$sc];
        $out .= "<td>".$col."</td>";
      }
      $out .= "</tr>";
    }
    $out .= "<table><script>window.print();</script>";

    $view = new View_Jade('admin/print');
    $view->title = $term_mode ? join("", $terms) : FALSE;
    $view->content = $out;
    $this->response->body($view);
  }
  
  public function _list_export($source, $conf)
  {
    $source->limit = 100000;
    $export_type = $_GET['export'];
    $params = FALSE;

    switch($export_type)
    {
      case 'csv':
        $temp = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');
        $i = 0;
        foreach($source->render('export', array('name', 'rendered')) as $item)
        {
          if($i==0)
          {
            // print CSV header
            fputcsv($temp, array_keys($item['cols']));
          }
          fputcsv($temp, array_values($item['cols']));
          $i++;
        }
        rewind($temp);
        $params = array(
          'data' => stream_get_contents($temp),
          'ext' => 'csv',
          'mime' => 'text/csv',
        );
        fclose($temp);
        break;

      case 'json':
        $data = array();
        foreach($source->render('export', array('id', 'raw')) as $item)
        {
          $data[] = $item['cols'];
        }
        $params = array(
          'data' => json_encode($data),
          'ext' => 'json',
          'mime' => 'text/json',
        );
        break;
    }
    if(!$params)
      return FALSE;

    $site_config = Kohana::$config->load('site')->as_array();
    $fn = preg_replace('/^https?:\/\//', '',$site_config['url']).'-'.$conf['name_plural'].'-'.date('Y_m_d-H_i').'.'.$params['ext'];
    $this->response->body($params['data']);
    $this->response->send_file(TRUE, $fn, array('mime_type' => $params['mime']));
  }

  public function action_list()
  {
    $current_page = $this->request->param('type');

    $conf = Arnal::object_config($current_page);
    if(!$this->check_access($conf['id']))
    {
      return $this->denied_redirect();
    }

    $sort = array('id','DESC');
    if(isset($conf['sort']) AND !empty($conf['sort']))
    {
      $sort = $conf['sort'];
    }
    $items = Collection::factory(ucfirst($conf['id']), array(
      'limit' => $this->user()->pref('list:perpage'),
      'order_by' => $sort,
    ));

    // uzivatelska prava nastavena na Modelu
    list($rights, $items, $conf) = $this->check_rights($conf, $items);

    // process actions
    if(isset($_GET['action']))
    {
      $action = $_POST['action'];
      $ids = $_POST['ids'];
      $items->where[] = array('id','IN',$ids);
      $count = 0;
      $object = ucfirst($conf['id']);

      if(is_array($ids) AND count($ids) > 0)
      {
        foreach($items as $it)
        {
          $it->action($action);
          $count++;
        }
        Arnal::msg('Hotovo. Počet upravených záznamů: '.$count, 'success');
      }
      else
      {
        Arnal::msg('Nic se nestalo. Nevybrali jste žádný záznam.', 'warning');
      }
      return $this->redirect($this->request->referrer());
    }

    // process groups
    $items->process_groups(isset($_GET['filter']) ? $_GET['filter'] : NULL);

    // process filters
    $items->process_filters($_GET);

    // handle print
    if(isset($_GET['print']) AND $_GET['print'] == '1')
    {
      return $this->_list_print($items, $conf);
    }

    // handle export
    if(isset($_GET['export']) AND in_array($_GET['export'], array('csv','json')))
    {
      return $this->_list_export($items, $conf);
    }

    // create view
    $view = new View_Admin_Layout('admin/object_list');
    $view->current_page = $current_page;

    $view->content->count = $items->count_all();
    $view->content->pagination = $items->pagination();
    $view->content->items = array('arr' => $items->render('table'));

    /*$view->content->buttons = array(
      array('url' => '#', 'text' => 'Potvrdit', 'class' => 'btn-primary'),
      array('url' => '#', 'text' => 'Zamitnout', 'class' => ''),
    );*/

    // vytvoreni specialnich inputu do Filtru
    if(isset($conf['wheres']) AND is_array($conf['wheres']))
    {
      foreach($conf['wheres'] as $key=>$c)
      {
        if($items->current_group != 'all' AND (isset($c['show_group']) AND $c['show_group'] == FALSE))
        {
          unset($conf['wheres'][$key]);
          continue;
        }
        if(isset($c['dt']))
        {
          $style = NULL;
          $dt = DT::factory($c['dt'], NULL, array('allow_null' => TRUE, 'null_title' => 'vše' ));
          if($dt instanceOf DT_Enum)  
          { 
            if(count($dt->possible_values()) > 3)
            {
              $dt = DT::factory($c['dt'], NULL, array('allow_null' => TRUE, 'display_type' => 'select'));
            }
          }
          if(array_key_exists($c['code'], $items->filters_active))
          {
            $style = 'background-color: #C2EDD1;';
            $dt->set($items->filters_active[$c['code']]);
          }
          $conf['wheres'][$key]['where_input'] = $dt->input($c['code'], array('style' => 'width: 160px;'.$style));
        }
        elseif(isset($c['fk']))
        {
          $style = NULL;
          $col = ORM::factory($c['fk']);
          $arr = array();
          foreach($col->find_all() as $k)
          {
            $arr[' '.$k->id] = $k->title();
          }

          $dt = DT::factory('Enum', NULL, array('possible_values' => $arr));
          if(array_key_exists($c['code'], $items->filters_active))
          {
            $style = 'background-color: #C2EDD1;';
            $dt->set($items->filters_active[$c['code']]);
          }
          $conf['wheres'][$key]['where_input'] = $dt->input($c['code'], array('style' => 'width: 160px; '.$style));
          //var_dump($conf['wheres'][$key]['where_input']):
        }
      }
    }

    $buttons = array();
    if(isset($conf['actions']))
    {
      foreach($conf['actions'] as $action_name => $action_obj)
      {
        $buttons[] = array(
          'text' => $action_obj['text'], 
          'class' => $action_obj['class'], 
          'id' => $action_name, 
          'onclick' => $action_obj['onclick'],
        );
      }
    }
    // basic buttons
    if(isset($conf['allow_delete']) AND $conf['allow_delete'] == TRUE)
    {
      $buttons[] = array('text' => 'Smazat', 'class' => 'btn-danger', 'id' => 'delete', 'onclick' => 'return confirm("Opravdu chcete toto smazat?");');
    }
    if(Auth::instance()->get_user()->is_admin)
    {
      $diff_str = 'Porovnat '.(($conf['id'] == 'log') ? ' logy' : '');
      $buttons[] = array('text' => $diff_str, 'class' => '', 'id' => 'diff', 'onclick' => 'return objectdiff()');
    }
    $view->content->buttons = $buttons;
    $view->content->has_actions = count($buttons) > 0;
    $view->content->current_group = $items->current_group;
    $view->content->filters_active = $items->filters_active;
    $view->content->wheres_active = count($items->filters_active) > 0;
    $view->content->wheres_cancel_url = URL::site($conf['plural'].URL::query(array('filter' => $items->current_group), FALSE));
    $view->content->export_url_csv = $this->request->uri().URL::query(array('export' => 'csv'));
    $view->content->export_url_json = $this->request->uri().URL::query(array('export' => 'json'));
    $view->content->print_url = $this->request->uri().URL::query(array('print' => '1'));
    $view->content->config = $conf;

    $this->response->body($view->render());
  }
}
