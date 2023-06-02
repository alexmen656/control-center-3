<?php
include 'head.php';

if(isset($_POST['getMessagesByChatID']) && isset($_POST['chatID'])){
    $chatID = escape_string($_POST['chatID']);
    $chat = query("SELECT * FROM control_center_chats WHERE chatID=$chatID");
    if(mysqli_num_rows($chat) == 1){
        $chat = fetch_assoc($chat);
    }else{
        echo echoJSON(["error"=> "Error 23"]);
        $chat = [];
    }
    $messages = query("SELECT * FROM control_center_messages WHERE chatID=$chatID");
    if(mysqli_num_rows($messages) > 0){
        $i=0;
        foreach($messages as $m){

          
            $json[$i]['type'] = $chat['type'];
            $json[$i]['from'] = $m['from'];
            $json[$i]['body'] = $m['body'];
            $json[$i]['datetime'] = $m['datetime'];


            if($chat['type'] == 2){// 2 = Gruppe
                $from = $m['from'];
                $user = query("SELECT * FROM control_center_users WHERE userID=$from");
                if(mysqli_num_rows($user) ==1){
                    $user = fetch_assoc($user);
                    $json[$i]['user']['firstname'] = $user['firstname'];
                    $json[$i]['user']['lastname'] = $user['lastname'];
                    $json[$i]['user']['profileImg'] = $user['profileImg'];
                }else{
                    echo echoJSON(["error"=> "Error 24"]);
                }
            }




            $i++;
        }
        echo echoJSON($json);
    }else{
        //echo echoJSON(["error"=> "0 Messages"])
        echo echoJSON([]);
    }
  
}



?>