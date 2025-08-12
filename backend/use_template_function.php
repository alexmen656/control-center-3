<?php
function useTemplate($nazov,$data=[]){
  $temp=file_get_contents('/www/paxar/'.$nazov);
  //priprava parametrov
  $co=[];$zaco=[];
  foreach ($data as $key=>$val):
    $co[]='['.$key.']';
    $zaco[]=$val;
  endforeach;
  return str_replace($co,$zaco,$temp);
}
?>

