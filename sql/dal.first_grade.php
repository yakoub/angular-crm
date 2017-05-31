<?php
namespace Dal;

function Connection() {
  static $dbh = NULL;

  if (!$dbh) {
    $bac_db = include __DIR__ . '/../api/database.php';
    $dsn = "mysql:host={$bac_db['host']};dbname={$bac_db['name']};charset=utf8";
    $options = array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION);
    $dbh = new \PDO($dsn, $bac_db['username'], $bac_db['password'], $options);
  }
  return $dbh;
}

function createRequest($data) {
  $dbh = Connection();
  $sql = <<<SQL
  insert into request
  (name, mail, newsletter)
  values (:name, :mail, :newsletter)
SQL;
  $sth = $dbh->prepare($sql);
  $sth->bindParam(':name', $data['requester'], \PDO::PARAM_STR);
  $sth->bindParam(':mail', $data['mail'], \PDO::PARAM_STR);
  $sth->bindValue(':newsletter', isset($data['newsletter']), \PDO::PARAM_INT);
  if (!$sth->execute()) {
    throw new \Exception('Insert failed');
  }
  $id = $dbh->lastInsertId();
  return $id;
}

function createVideo($data) {
  $id = createRequest($data);

  $dbh = Connection();
  $sql = <<<SQL
  insert into first_grade (request_id, child_name, gender, image, greeting) 
  values (:request, :child_name, :gender, :image, :greeting)
SQL;
  $sth = $dbh->prepare($sql);
  $sth->bindValue(':request', $id, \PDO::PARAM_INT);
  $sth->bindParam(':child_name', $data['child_name'], \PDO::PARAM_STR);
  $sth->bindParam(':gender', $data['gender'], \PDO::PARAM_INT);
  $sth->bindParam(':image', $data['image'], \PDO::PARAM_STR);
  $sth->bindParam(':greeting', $data['greeting'], \PDO::PARAM_STR);
  if (!$sth->execute()) {
    throw new \Exception('Insert failed');
  }

  $sql = <<<SQL
  insert into first_grade_admin (request_id, video_done) 
  values (:request, 0)
SQL;
  $sth = $dbh->prepare($sql);
  $sth->bindValue(':request', $id, \PDO::PARAM_INT);
  if (!$sth->execute()) {
    throw new \Exception('Insert failed');
  }
}

function createBrochure($data) {
  $id = createRequest($data);

  $dbh = Connection();
  $sql = <<<SQL
  insert into first_grade_brochure (request_id, address, phone, sent) 
  values (:request, :address, :phone, 0)
SQL;
  $sth = $dbh->prepare($sql);
  $sth->bindValue(':request', $id, \PDO::PARAM_INT);
  $sth->bindParam(':address', $data['address'], \PDO::PARAM_STR);
  $sth->bindParam(':phone', $data['phone'], \PDO::PARAM_STR);
  if (!$sth->execute()) {
    throw new \Exception('Insert failed');
  }
}


function updateRequest($data) {
  $dbh = Connection();
  $sth = $dbh->prepare('update request set name=:name, mail=:mail where id=:id');
  $sth->bindParam(':name', $data['requester'], \PDO::PARAM_STR);
  $sth->bindParam(':mail', $data['mail'], \PDO::PARAM_STR);
  $data['id'] = intval($data['id']);
  $sth->bindParam(':id', $data['id'], \PDO::PARAM_INT);
  if (!$sth->execute()) {
    throw new \Exception('Update failed');
  }
}

function updateVideo($data) {
  updateRequest($data);
  $dbh = Connection();
  
  $sql = <<<SQL
  update first_grade set 
    child_name = :child_name, 
    gender = :gender,
    greeting = :greeting
  where request_id = :id
SQL;
  $sth = $dbh->prepare($sql);
  $sth->bindParam(':child_name', $data['child_name'], \PDO::PARAM_STR);
  $sth->bindParam(':gender', $data['gender'], \PDO::PARAM_INT);
  $sth->bindParam(':greeting', $data['greeting'], \PDO::PARAM_STR);
  $sth->bindParam(':id', $data['id'], \PDO::PARAM_INT);

  if (!$sth->execute()) {
    throw new \Exception('Update failed');
  }
 
  $sql = <<<SQL
  update first_grade_admin set 
    video_done = :video_done,
    go_live_url = :go_live_url,
    video_uploaded = :video_uploaded,
    notified = :notified
  where request_id = :id
SQL;
  $sth = $dbh->prepare($sql);
  $sth->bindParam(':video_done', $data['video_done'], \PDO::PARAM_INT);
  $sth->bindParam(':go_live_url', $data['go_live_url'], \PDO::PARAM_STR);
  $sth->bindParam(':video_uploaded', $data['video_uploaded'], \PDO::PARAM_INT);
  $sth->bindParam(':notified', $data['notified'], \PDO::PARAM_STR);
  $sth->bindParam(':id', $data['id'], \PDO::PARAM_INT);

  if (!$sth->execute()) {
    throw new \Exception('Update failed');
  }

}

function updateBrochure($data) {
  updateRequest($data);
  $dbh = Connection();
  $sql = <<<SQL
  update first_grade_brochure set 
    address = :address,
    phone = :phone,
    sent = :sent
  where request_id = :id
SQL;
  $sth = $dbh->prepare($sql);
  $sth->bindParam(':address', $data['address'], \PDO::PARAM_STR);
  $sth->bindParam(':phone', $data['phone'], \PDO::PARAM_STR);
  $sth->bindParam(':sent', $data['sent'], \PDO::PARAM_INT);
  $sth->bindParam(':id', $data['id'], \PDO::PARAM_INT);

  if (!$sth->execute()) {
    throw new \Exception('Update failed');
  }
}

class FirstGrade {
  
  public function __set($name, $value) {
    switch ($name) {
      case 'request_id':
      case 'id':
        $value = intval($value);
        break;
      case 'gender':
      case 'sent':
      case 'video_done':
      case 'video_uploaded':
      case 'newsletter':
        $value = ($value == '1');
        break;
      case 'image':
        $uploaddir = '/content/upload/first_grade/';
        $value = $uploaddir . $value;
        break;
    }
    $this->$name = $value;
  }
};

const LIMIT = 20;

function GetConditions(&$sql) {
  $params = array();
  //$_GET = array('email' => 'sting606@gmail.com', 'uploaded' => 0, 'notified' => 0);
  if ($_GET) {
    foreach ($_GET as $key => $val) {
      switch ($key) {
        case 'email':
          if ($val) {
            $params[] = array(
              'name' => 'mail',
              'val' => $val,
              'type' => \PDO::PARAM_STR,
            );
          }
          break;
        case 'child':
          if ($val) {
            $params[] = array(
              'name' => 'child_name',
              'val' => $val,
              'type' => \PDO::PARAM_STR,
            );
          }
          break;
        case 'done':
          if ($val) {
            $params[] = array(
              'name' => 'video_done',
              'val' => $val == 'true',
              'type' => \PDO::PARAM_INT,
            );
          }
          break;
        case 'uploaded':
          if ($val) {
            $params[] = array(
              'name' => 'video_uploaded',
              'val' => $val == 'true',
              'type' => \PDO::PARAM_INT,
            );
          }
          break;
        case 'notified':
          if ($val) {
            $params[] = $val == 'true' ? 'notified is not null' : 'notified is null';
          }
          break;
      }
    }
  }
  if ($params) {
    $sql .= ' where ';
    $first_p = array_shift($params);
    if ($first_p) {
      if (is_array($first_p)) {
        $sql .= "{$first_p['name']} = :{$first_p['name']}";
      }
      else {
        $sql .= $first_p;
      }
    }
    foreach ($params as $param) {
      if (is_array($param)) {
        $sql .= " and {$param['name']} = :{$param['name']}";
      }
      else {
        $sql .= " and $param";
      }
    }
    array_unshift($params, $first_p);
  }
  return $params;
}

function listVideos($page = 0) {
  $dbh = Connection();
  $sql_base = <<<SQL
  from request r 
  inner join first_grade f on r.id = f.request_id
  inner join first_grade_admin fa on r.id = fa.request_id
SQL;
  $params = GetConditions($sql_base);

  $sql = 'select count(*) ' . $sql_base;
  $sth = $dbh->prepare($sql);
  foreach ($params as $p) {
    if (is_array($p)) {
      $sth->bindParam(':' . $p['name'], $p['val'], $p['type']);
    }
  }
  $sth->execute();
  $total = $sth->fetchColumn(0);
  $pages = (int) ceil($total / LIMIT);

  $limit = LIMIT;
  $offset = $limit * $page;
  $sql = "select * $sql_base limit $limit offset $offset";
  $sth = $dbh->prepare($sql);
  foreach ($params as $p) {
    if (is_array($p)) {
      $sth->bindParam(':' . $p['name'], $p['val'], $p['type']);
    }
  }
  if ($sth->execute()) {
    $requests = $sth->fetchAll(\PDO::FETCH_CLASS, 'Dal\\FirstGrade');
    return array($requests, $pages);
  }
  return array(array(), $pages);
}

function listBrochures($page = 0) {
  $dbh = Connection();
  $sql_base = <<<SQL
  from request r 
  inner join first_grade_brochure f on r.id = f.request_id
SQL;
  $params = GetConditions($sql_base);

  $sql = 'select count(*) ' . $sql_base;
  $sth = $dbh->prepare($sql);
  foreach ($params as $p) {
    if (is_array($p)) {
      $sth->bindParam(':' . $p['name'], $p['val'], $p['type']);
    }
  }
  $sth->execute();
  $total = $sth->fetchColumn(0);
  $pages = (int) ceil($total / LIMIT);

  $limit = LIMIT;
  $offset = $limit * $page;
  $sql = "select * $sql_base limit $limit offset $offset";
  $sth = $dbh->prepare($sql);
  foreach ($params as $p) {
    if (is_array($p)) {
      $sth->bindParam(':' . $p['name'], $p['val'], $p['type']);
    }
  }

  if ($sth->execute()) {
    $requests = $sth->fetchAll(\PDO::FETCH_CLASS, 'Dal\\FirstGrade');
    return array($requests, $pages);
  }
  return array(array(), $pages);
}

function videoPage($id) {
  $dbh = Connection();
  $sql = <<<SQL
  select * from request r
  inner join first_grade f on r.id = f.request_id
  inner join first_grade_admin fa on r.id = fa.request_id
  where r.id = :id
SQL;
  $sth = $dbh->prepare($sql);
  $sth->bindParam(':id', $id, \PDO::PARAM_INT);
  $page = FALSE;
  if ($sth->execute()) {
    $page = $sth->fetchObject('Dal\\FirstGrade');
  }
  return $page;
}
