<?php

if (!isset($_REQUEST)) {
return;
}

$confirmation_token = ' fca0e00f';

$token = 'caa5390beb6b24f6f95a7b3bee6124bd1d52e68c97000354a810b0339c5ff96115a135b74913e65273eab';

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
$arr=str_split($msg);
if ($arr[0]=="%"){
    $len=0;
    //Длинна сообщения и ответа
    $k=1;
    //номер символа в строке
    $i=0;
    //переменная для создания цикла
    $mesg=array();
    //массив с сообщением
    $ans=array();
    //массив с ответом
    while($i==0){
        //пока i=0
        if ($arr[$k]=="%"){
            //если символ равен знаку %...
            $k=$k+3;
            $i++;
            //то сообщение уже инициализировано
        }else{
            if ($len>300){
              $request_params = array(
              'message' => "Сообщение не должно быть длиннее 300 символов.",
              'user_id' => $user_id,
              'access_token' => $token,
              'v' => '5.0'
              );
              $get_params = http_build_query($request_params);
              file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
              echo('ok');
              exit();
            }else{
              //если нет, то мы добавляем символ...
              array_push($mesg, $arr[$k]);
              $k++;
              $len++;
            //в массив с сообщением
            }
        }
    }
    $i=0;
    $len=0;
    while($i==0){
        //пока i=0
        if ($arr[$k]=="%"){
            //если символ равен знаку %...
            $i++;
            //то ответ уже инициализировано
        }else{
            if ($len>300){
              $request_params = array(
              'message' => "Ответ не должен быть длиннее 300 символов.",
              'user_id' => $user_id,
              'access_token' => $token,
              'v' => '5.0'
              );
              $get_params = http_build_query($request_params);
              file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
              echo('ok');
              exit();
            }else{
              //если нет, то мы добавляем символ...
              array_push($ans, $arr[$k]);
              $k++;
              $len++;
              //в массив с ответом
            }
        }
    }
    $mesg=implode($mesg);
    $ans=implode($ans);
    require('connect_db.php');
    $sql="INSERT INTO Answers
    (mes, ans)
    VALUES
    ('$mesg', '$ans')";
    mysqli_query($dbc, $sql);
    $request_params = array(
    'message' => "Я записала ваш ответ.",
    'user_id' => $user_id,
    'access_token' => $token,
    'v' => '5.0'
    );
    $get_params = http_build_query($request_params);
    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
    mysqli_close($dbc);
}else{
  require('connect_db.php');
  $sql="SELECT * FROM Answers WHERE mes='$msg'";
  $result=mysqli_query($dbc, $sql);
  if (mysqli_num_rows($result)>0){
  $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
  $request_params = array(
  'message' => $row['ans'],
  'user_id' => $user_id,
  'access_token' => $token,
  'v' => '5.0'
  );
  $get_params = http_build_query($request_params);
  file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
  }else{
    $request_params = array(
    'message' => "У меня нет ответа. Попробуй научить меня с помощью команды %xxx% %yyy%, где xxx-сообщение, а yyy-ответ.",
    'user_id' => $user_id,
    'access_token' => $token,
    'v' => '5.0'
  );
  $get_params = http_build_query($request_params);
  file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
  }
  /*$request_params = array(
  'message' => $msg,
  'user_id' => $user_id,
  'access_token' => $token,
  'v' => '5.0'
  );
  $get_params = http_build_query($request_params);
  file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);*/
}
echo('ok');
break;
}
