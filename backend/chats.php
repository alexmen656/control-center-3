<?php
include 'head.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $userId = $_GET['userId'];
  $result = query("SELECT * FROM control_center_chats WHERE FIND_IN_SET('{$userId}', users)");
  
  if ($result) {
    $chats = array();

    while ($row = fetch_assoc($result)) {
      $chatId = $row['chatID'];
      $creationTime = $row['creationTime'];
      $type = $row['type'];

      // Benutzerinformationen abrufen
      $userIds = explode(',', $row['users']);
      $users = array();
      foreach ($userIds as $id) {
        $userResult = query("SELECT * FROM control_center_users WHERE userID = '{$id}'");
        if ($userResult && mysqli_num_rows($userResult) > 0) {
          $userData = fetch_assoc($userResult);

          if($userData['profileImg'] != "avatar" && $userData['profileImg'] != "google"){
            $userData['profileImg'] = file_get_contents('images/profileImages/'.$userData['profileImg']);
        }
        if($userData['userID'] != $userId){
          $users[] = array(
            'userId' => $userData['userID'],
            'profileImg' => $userData['profileImg'],
            'firstname' => $userData['firstname'],
            'lastname' => $userData['lastname'],
            'email' => $userData['email']
          );
        }
         
        }
      }

      if($type == 2){
        //wenn Gruppe
        $group = query("SELECT * FROM control_center_groups WHERE chatID='$chatId'");
        if(mysqli_num_rows($group) == 1){
          $group = fetch_assoc($group);
          $name = $group['name'];
          $image = $group['image'];
        }else{
          echo "error 5783";
        }
      }elseif($type == 1){
       $name = $users[0]['firstname']." ".$users[0]['lastname'];
       $image = $users[0]['profileImg'];
      }else{
        echo "error 34";
      }
  
      $chats[] = array(
        'chatId' => $chatId,
        'creationTime' => $creationTime,
        'type' => $type,
        'image' => $image,
        'name' => $name,
        'users' => $users
      );
    }
  
    echo json_encode($chats);
  } else {
    header('HTTP/1.1 500 Internal Server Error');
    echo "Fehler beim Ausführen der Abfrage: " . mysqli_error($connection);
  }
}












if(isset($_POST['getChatByChatID']) && isset($_POST['chatID']) && isset($_POST['userID'])){
  $chatID = escape_string($_POST['chatID']);
  $userID = escape_string($_POST['userID']);
  $chat = query("SELECT * FROM control_center_chats WHERE chatID='$chatID'");

  if(mysqli_num_rows($chat) == 1){
    $chat = fetch_assoc($chat);
    $type = $chat['type'];
    $creationTime = $chat['creationTime'];
    $userIds = explode(',', $chat['users']);
    $users = array();

    foreach ($userIds as $id) {
      $userResult = query("SELECT * FROM control_center_users WHERE userID = '{$id}'");

      if ($userResult && mysqli_num_rows($userResult) > 0) {
        $userData = fetch_assoc($userResult);

        if($userData['profileImg'] != "avatar" && $userData['profileImg'] != "google"){
            $userData['profileImg'] = file_get_contents('images/profileImages/'.$userData['profileImg']);
        }

        if($userData['userID'] != $userID){
          $users[] = array(
            'userId' => $userData['userID'],
            'profileImg' => $userData['profileImg'],
            'firstname' => $userData['firstname'],
            'lastname' => $userData['lastname'],
            'email' => $userData['email']
          );
        }
         
        }
      }








    if($type == 2){
      //wenn Gruppe
      $group = query("SELECT * FROM control_center_groups WHERE chatID='$chatID'");
      if(mysqli_num_rows($group) == 1){
        $group = fetch_assoc($group);
        $name = $group['name'];
        $image = $group['image'];
      }else{
        echo "error 5783";
      }
    }elseif($type == 1){
     $name = $users[0]['firstname']." ".$users[0]['lastname'];
     $image = $users[0]['profileImg'];
    }else{
      echo "error 34";
    }

    $chats[] = array(
      'chatId' => $chatID,
      'creationTime' => $creationTime,
      'type' => $type,
      'image' => $image,
      'name' => $name,
      'users' => $users
    );



    $json['name'] = $name;
    $json['image'] = $image;
    $json['users'] = $users;

    echo echoJSON($json);
  }else{
    echo "error 83";
  }
}
?>
