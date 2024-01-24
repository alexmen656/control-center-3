<?php
include 'head.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
$headers = getRequestHeaders();

if ($headers['Authorization']) {
  $token = escape_string($headers['Authorization']);
  $userData = query("SELECT * FROM control_center_users WHERE loginToken='$token'");

  if (mysqli_num_rows($userData) == 1) {
    $userData = fetch_assoc($userData);
    $userID = $userData['userID'];

    if (isset($_POST['getComponentsByProject']) && isset($_POST['project'])) {
      $projectName = escape_string($_POST['project']);
      $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];

      $components = query("SELECT * FROM project_components WHERE projectID='$projectID' ORDER BY `project_components`.`id` ASC");
      $i = 0;
      foreach ($components as $c) {
        $json[$i]['name'] = $c['name'];
        $json[$i]['code'] = $c['code'];
        $i++;
      }
      echo echoJSON($json);
    } elseif (isset($_POST['getCoponent']) && isset($_POST['name']) && isset($_POST['project'])) {

      $projectName = escape_string($_POST['project']);
      $name = escape_string($_POST['name']);
      $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
      $components = query("SELECT * FROM project_components WHERE projectID='$projectID' AND code='$name' ORDER BY `project_components`.`id` ASC");
      foreach ($components as $c) {
        if ($c['type'] == "script" || $c['type'] == "menu") {
          $content = file_get_contents("/www/" . $projectName . "/" . $c['file']);
          if($c['type'] == "menu"){
            if(empty($content)){
              $content = "{'content': [], 'style': {'nav1': '', 'nav2': '', 'par1': '', 'par2': '', 'logo': ''}}";
            }
            $content = json_decode($content);

          }
        } elseif ($c['type'] == "audio" || $c['type'] == "video" || $c['type'] == "image") {
          $content = $projectName . "/" . $c['file'];
        }
        $last_change_by = $c['last_change_by'];
        $query = query("SELECT * FROM control_center_users WHERE userID='$last_change_by'");
        if (mysqli_num_rows($query) == 1) {
          $json['last_change'] = $c['last_change'];
          $uD = fetch_assoc($query);
          $json['last_change_by'] = $uD['firstname'] . " " . $uD['lastname'];
        }

      }
      $json['createdOn'] = $c['createdOn'];
      $json['name'] = $c['name'];
      $json['type'] = $c['type'];
      $json['content'] = $content;
      echo echoJSON($json);
      
    } elseif (isset($_POST['deleteComponent']) && isset($_POST['name']) && isset($_POST['project'])) {


      $projectName = escape_string($_POST['project']);
      $name = escape_string($_POST['name']);
      $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
      $component = query("SELECT * FROM project_components WHERE projectID='$projectID' AND code='$name' ORDER BY `project_components`.`id` ASC");

      if (mysqli_num_rows($component) != 1) {
        echo "error 1";
      } else {
        $component = fetch_assoc($component);
        $delete = query("DELETE FROM project_components WHERE projectID='$projectID' AND code='$name'");
        if ($delete) {
          if (unlink("/www/" . $projectName . "/" . $component['file'])) {
            echo "component/s successful deleted";
          }
        }
      }

    } elseif (isset($_POST['updateHTML']) && isset($_POST['name']) && isset($_POST['project']) && isset($_POST['html'])) {
      $projectName = escape_string($_POST['project']);
      $name = escape_string($_POST['name']);
      $html = $_POST['html'];

      $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
      $component = query("SELECT * FROM project_components WHERE projectID='$projectID' AND code='$name'");

      if (mysqli_num_rows($component) == 1) {
        $filename = fetch_assoc($component)['file'];
        $location = "/www/" . $projectName . "/" . $filename;
        $oldHTML = file_get_contents($location);

        if ($oldHTML != $html) {
          unlink($location);
          file_put_contents($location, $html, 0777);
          chmod($location, 0777);

          if (query("UPDATE project_components SET last_change=NOW(), last_change_by='$userID' WHERE projectID='$projectID' AND code='$name'")) { //last_change=NOW() AND 
            echo "Success";
          }
        } else {
          echo "0 change!";
        }

      } else {
        echo "error 1";
      }

    } elseif (isset($_POST['newComponent']) && isset($_POST['name']) && isset($_POST['code']) && isset($_POST['project'])) { // && isset($_POST['html']))
      $projectName = escape_string($_POST['project']);
      $name = escape_string($_POST['name']);
      $code = escape_string($_POST['code']);
      $type = "script";
      $icon = "code-slash-outline";

      if (isset($_FILES["files"])) {
        $type = "image";
        $icon = "image-outline";
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
          $name2 = $_FILES['files']['name'][$key];
          $file_info = pathinfo($name2, PATHINFO_EXTENSION);
          $fileName = $code . "." . $file_info;
          $file_destination = '/www/' . $projectName . '/' . $fileName;
          move_uploaded_file($tmp_name, $file_destination);
        }

      } elseif ($_POST['type'] == 'menu') {
        $type = "menu";
        $icon = "menu-outline";
        $fileName = str_replace(" ", "-", strtolower($code)) . ".json";
      } else {
        $fileName = str_replace(" ", "-", strtolower($code)) . ".php";
      }

      $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
      $insert = query("INSERT INTO project_components VALUES(0, '$fileName', '$type', '$name', '$code', NOW(), NOW(), '$userID', '1234567890', '$projectID')");
      $url = "project/" . $projectName . "/components/" . $code;
      query("INSERT INTO control_center_pages VALUES (0, '$url', 'true', '$icon', '$name', '', 0)");
      if($type == "menu"){
        $url2=$url."/config";
        $name2=$name." settings";
        query("INSERT INTO control_center_pages VALUES (0, '$url2', 'true', 'cog-outline', '$name2', '', 0)");
      }
      if ($insert) {
        $location = "/www/" . $projectName . "/" . $fileName;
        file_put_contents($location, "", 0777);
        chmod($location, 0777);
      } else {
        echo "error 1";
      }
    }
  }
}