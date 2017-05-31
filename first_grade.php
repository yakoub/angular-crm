<?php
header('Content-Type: application/json; charset=utf-8');
include $_SERVER['DOCUMENT_ROOT'] . '/sql/dal.first_grade.php';
include $_SERVER['DOCUMENT_ROOT'] . '/site/templates/first_grade/process.util.php';

if ($_GET['op'] == 'save') {
  $postdata = file_get_contents('php://input');
  $postdata = (array) json_decode($postdata);
  $postdata['requester'] = $postdata['name'];
  try {
    switch ($postdata['form_id']) {
      case 'video':
        FirstGradeForm\video_mail_done($postdata);
        Dal\updateVideo($postdata);
        break;
      case 'brochure':
        Dal\updateBrochure($postdata);
        break;
    }
    http_response_code(200);
    print json_encode($postdata);
  }
  catch (Exception $e) {
    http_response_code(500);
    $m = $e->getMessage();
    print json_encode($m);
  }
}
else {
  $page = isset($_GET['page']) ? $_GET['page'] : 0;
  if ($_GET['op'] == 'list_videos') {
    list($requests, $pages) = Dal\listVideos($page);
  }
  else {
    list($requests, $pages) = Dal\listBrochures($page);
  }

  $requests_indexed = array();
  foreach ($requests as $request) {
    $requests_indexed[$request->id] = $request;
  }
  $data = array('requests' => $requests_indexed, 'pages' => $pages, 'page' => intval($page));
  print json_encode($data);
}
