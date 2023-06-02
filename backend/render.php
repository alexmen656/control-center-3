<?php
function useTemplate($nazov,$data=[]){
  $temp=file_get_contents('/www/control-center/templates/'.$nazov.'.html');
  //priprava parametrov
  $co=[];$zaco=[];
  foreach ($data as $key=>$val):
    $co[]='{{'.$key.'}}';
    $zaco[]=$val;
  endforeach;
  return str_replace($co,$zaco,$temp);
}

echo useTemplate('dashboard', ["test" => "toto je iba test"]);
?>

