<?php 
include 'head.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);


if(isset($_POST['getComponentsByProject']) && isset($_POST['project'])){
    //echo 1;
    $projectName = escape_string($_POST['project']);
    //echo $projectName;
    $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
  //  echo $projectID
    $components = query("SELECT * FROM project_components WHERE projectID='$projectID' ORDER BY `project_components`.`id` ASC"); 
    $i = 0;
    foreach($components as $c){
        $json[$i]['name'] = $c['name'];
        $json[$i]['code'] = $c['code'];
        $i++;
    }
    echo echoJSON($json);
}elseif(isset($_POST['getCoponent']) && isset($_POST['name']) && isset($_POST['project'])){
       // echo 1;
       $projectName = escape_string($_POST['project']);
       $name = escape_string($_POST['name']);
       //echo $projectName;
       $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
    //  echo $projectID." ".$name;
       $components = query("SELECT * FROM project_components WHERE projectID='$projectID' AND code='$name' ORDER BY `project_components`.`id` ASC"); 
       foreach($components as $c){
        if($c['type'] == "script"){
               $content = file_get_contents("/www/".$projectName."/".$c['file']);
        }elseif($c['type'] == "audio" || $c['type'] == "video" || $c['type'] == "image"){
          $content = $projectName."/".$c['file'];     
        }
       

           $json['name'] = $c['name'];
           $json['type'] = $c['type'];
           $json['content'] = $content;
       }
       echo echoJSON($json);
}elseif(isset($_POST['deleteComponent']) && isset($_POST['name']) && isset($_POST['project'])){


  $projectName = escape_string($_POST['project']);
  $name = escape_string($_POST['name']);
  $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
  $component = query("SELECT * FROM project_components WHERE projectID='$projectID' AND code='$name' ORDER BY `project_components`.`id` ASC"); 

  if(mysqli_num_rows($component) != 1){
    echo "error 1";
  }else{
    $component = fetch_assoc($component);
    $delete = query("DELETE FROM project_components WHERE projectID='$projectID' AND code='$name'"); 
    if($delete){
      if(unlink("/www/".$projectName."/".$component['file'])){
        echo "component/s successful deleted";
      }   
    }
  }
 

}elseif(isset($_POST['updateHTML']) && isset($_POST['name']) && isset($_POST['project']) && isset($_POST['html'])){
  // echo 1;
  $projectName = escape_string($_POST['project']);
  $name = escape_string($_POST['name']);
  $html = $_POST['html'];//escape_string(
echo 1;
  //echo $projectName;
  $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
//  echo $projectID." ".$name;
  $component = query("SELECT * FROM project_components WHERE projectID='$projectID' AND code='$name'"); 
  if(mysqli_num_rows($component)==1){
echo 2;

    $filename = fetch_assoc($component)['file'];
    $location = "/www/".$projectName."/".$filename;
    echo $location;
    echo file_get_contents($location);
    echo $html." <== Hier";
    unlink($location);
    //chmod($location, 0777);
    file_put_contents($location,$html, 0777);
    chmod($location, 0777);

  }else{
    echo "error 1";
  }
  
  //echo echoJSON($json);
}elseif(isset($_POST['newComponent']) && isset($_POST['name']) && isset($_POST['code']) && isset($_POST['project'])){// && isset($_POST['html']))
  //echo 2;
  $projectName = escape_string($_POST['project']);
  $name = escape_string($_POST['name']);
  $code = escape_string($_POST['code']);


  if(isset($_FILES["files"])){
    foreach($_FILES['files']['tmp_name'] as $key => $tmp_name){
      $name2 = $_FILES['files']['name'][$key];
      $file_info = pathinfo($name2, PATHINFO_EXTENSION);
      $fileName = $code.".".$file_info;

      $file_destination = '/www/'.$projectName.'/' . $fileName;
      move_uploaded_file($tmp_name, $file_destination);
      echo $fileName;
    }

  }else{
    $fileName = str_replace(" ","-",strtolower($code)).".php";
  }
  

  //$html = $_POST['html'];//escape_string(


  $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
  $insert = query("INSERT INTO project_components VALUES(0, '$fileName', 'script', '$name', '$code', '1234567890', '$projectID')"); 
  $url = "project/".$projectName."/components/".$code;
  query("INSERT INTO control_center_pages VALUES (0, '$url', '', '$name', '', 0)");
  if($insert){
    $location = "/www/".$projectName."/".$fileName;
    file_put_contents($location,"", 0777);
    chmod($location, 0777);
  }else{
    echo "error 1";
  }

}