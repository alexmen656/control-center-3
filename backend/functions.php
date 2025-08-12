<?php
ini_set('display_errors', true);

function query($sql)
{
   global $con;
   return mysqli_query($con, $sql);
}

function escape_string($string)
{
   global $con;
   return mysqli_real_escape_string($con, $string);
}

function fetch_assoc($array)
{
   return mysqli_fetch_assoc($array);
}

function hashPassword($password)
{
   $hashFormat = "$2y$10$";
   $salt = "1PxC706HjqK9R5Cv23HFG0";
   $hasFormat_salt = $hashFormat . $salt;
   $password = crypt($password, $hasFormat_salt);
   return $password;
}

function generateRandomString($length = 10)
{
   $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
   $charactersLength = strlen($characters);
   $randomString = '';
   for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
   }
   return $randomString;
}

function alert($color, $msg)
{
   return '<div class="alert alert-' . $color . '" role="alert">
   ' . $msg . '
 </div>';
}

function createFile($location, $content, $permission)
{
   file_put_contents($location, $content, $permission);
   chmod($location, $permission);
}

function createFolder($location, $permission)
{
   mkdir($location, $permission);
   chmod($location, $permission);
}

function getRequestHeaders()
{
   $headers = array();
   foreach ($_SERVER as $key => $value) {
      if (substr($key, 0, 5) <> 'HTTP_') {
         continue;
      }
      $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
      $headers[$header] = $value;
   }
   return $headers;
}

//new

function randomNumber()
{
    $rand = rand(100000, 999999);
    return $rand;
}

function echoJson($json)
{
    return json_encode($json, JSON_PRETTY_PRINT);
}

function getProjectID(string $projectLink): string
{
    $project = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectLink'"));
    if (!$project) {
        throw new Exception("Project not found");
    }
    return $project['projectID'];
}

function showJSON($json){
    echo json_encode($json, JSON_PRETTY_PRINT);
}

function checkUserProjectPermission($userID, $projectID)
{
    $check = query("SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'");
    return mysqli_num_rows($check) == 1;
}