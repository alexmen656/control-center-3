<?php
$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost']; // replace with query for domains.
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', $request_host), -2));
//echo $host_domain;
//if (! in_array($host_domain, $allowed_origins, false)) {
  //  header('HTTP/1.0 403 Forbidden');
    //die('You are not allowed to access this.');     
//}
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');
include '/www/paxar/components/php_head.php';

function randomNumber(){
    $rand = rand(100000, 999999);
    return $rand;
}

function echoJson($json){
    return json_encode($json, JSON_PRETTY_PRINT);
}

function useTemplate2($temp,$data=[]){
    $co=[];$zaco=[];
    foreach ($data as $key=>$val):
      $co[]='{{'.$key.'}}';
      $zaco[]=$val;
    endforeach;
    return str_replace($co,$zaco,$temp);
  }

$i=0;
$pages = query("SELECT * FROM control_center_pages");
foreach($pages as $p){
    $pageID = $p['pageID'];
    $data = query("SELECT * FROM control_center_page_data WHERE pageID='$pageID'");
    foreach($data as $d){
        $replaces[$d['key']] = $d['value'];
    }
    $json[$i]['id']=$p['id'];
    $json[$i]['url']=$p['url'];
    $json[$i]['showTitle']=$p['showTitle'];
    $json[$i]['icon']=$p['icon'];
    $json[$i]['title']=$p['title'];
    $json[$i]['html']= useTemplate2($p['html'], $replaces);
    $json[$i]['pageID']=$p['pageID'];
    $i++;
}

echo echoJson($json);
?>