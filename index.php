<?php

require('connect_db.php');

if (!isset($_REQUEST)) {
return;
}

$confirmation_token = ' fca0e00f';

$token = '533f57591b94597f0e2f739bc414b35a650a14401e8c974abf33568d65380903e521a73ef9ff4ef21c092';

$data = json_decode(file_get_contents('php://input'));

//Проверяем, что находится в поле "type"
switch ($data->type) {
//Если это уведомление для подтверждения адреса...
case 'confirmation':
  echo $confirmation_token;
break;

//Если это уведомление о новом сообщении...
case 'message_new':
//...получаем id его автора
$user_id = $data->object->user_id;
//затем с помощью users.get получаем данные об авторе
$user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&access_token={$token}&v=5.0"));

//и извлекаем из ответа его имя
$user_name = $user_info->response[0]->first_name;

$msg=$data->object->body;
$mesg=str_split($msg, 4);
if ($mesg[0]=="/об "){
  $mesg=str_split($msg);
  $i=0;
  $k=5;
  $usmes=array();
  while ($i<1){
    if ($mesg[$k]!='"'){
      array_push($usmes,$mesg[$k]);
      $k++;
    }else{
      $usmes=implode($usmes);
      $i++;
    }
  }
  $i=0;
  $k=$k+2;
  $ans=array();
  while ($i<1){
    if ($mesg($k)!='"'){
      array_push($ans,$mesg[$k]);
      $k++;
    }else{
      $ans=implode($ans);
      $i++;
    }
  }
  $sql="INSERT INTO Answers
  (mes, ans)
  VALUES
  ('$usmes', '$ans')";
  mysqli_query($dbc, $sql);
}

//Возвращаем "ok" серверу Callback API
echo('ok');
mysqli_close($dbc);
break;
}
