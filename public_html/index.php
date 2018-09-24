<?php
/*
  Чат-бот для групп в ВК
  Версия: alpha 0.4(Money4All)
  Разработан для некоммерческого использования
*/
if (!isset($_REQUEST)) {
return;
}
//Не забудьте указать токен своей группы и настроить базу данных
$confirmation_token = 'YourConfirmationToken';

$token = 'YourToken';

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
if ($arr[0]=="!"){
//блок команд бота
    switch ($arr[1]){
        case 'm':
            if ($msg=="!m"){
                //музыкальный модуль
                require('connect_db.php');
                $sql="SELECT id, owner_id FROM music ORDER BY rand() LIMIT 1";
                $result=mysqli_query($dbc, $sql);
                $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                $owner_id=$row['owner_id'];
                $id=$row['id'];
                $audio="audio".$owner_id."_".$id;
                $request_params = array(
                'attachment' => $audio,
                'user_id' => $user_id,
                'access_token' => $token,
                'v' => '5.0'
                );
                $get_params = http_build_query($request_params);
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                mysqli_close($dbc);
                echo('ok');
                exit();
            }
            $k=4;
            $i=0;
            $len=0;
            $artist=array();
            if ($arr[3]=="#"){
                $type=$data->object->attachments[0]->type;
                if ($type=='audio'){
                    $id=$data->object->attachments[0]->audio->id;
                    $owner_id=$data->object->attachments[0]->audio->owner_id;
                    $artist=$data->object->attachments[0]->audio->artist;
                    $title=$data->object->attachments[0]->audio->title;
                    require('connect_db.php');
                    $sql="INSERT INTO music
                    (id, owner_id, artist, title)
                    VALUES
                    ('$id','$owner_id','$artist','$title')";
                    mysqli_query($dbc, $sql);
                    mysqli_close($dbc);
                    $request_params = array(
                    'message' => "Я записала вашу аудиозапись",
                    'user_id' => $user_id,
                    'access_token' => $token,
                    'v' => '5.0'
                    );
                    $get_params = http_build_query($request_params);
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    echo('ok');
                    exit();
                }else{
                    $request_params = array(
                    'message' => "Во вложениях должна быть только одна аудиозапись.",
                    'user_id' => $user_id,
                    'access_token' => $token,
                    'v' => '5.0'
                    );
                    $get_params = http_build_query($request_params);
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    echo('ok');
                    exit();
                }
            }
            if ($arr[3]=="%"){
                while($i==0){
                    if ($arr[$k]=="%"){
                        $i++;
                    }else{
                        if ($len<50){
                            array_push($artist, $arr[$k]);
                            $k++;
                            $len++;
                        }else{
                            $request_params = array(
                            'message' => "Название исполнителя должно быть не длиннее 50 символов.",
                            'user_id' => $user_id,
                            'access_token' => $token,
                            'v' => '5.0'
                            );
                            $get_params = http_build_query($request_params);
                            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                            echo('ok');
                            exit();
                        }
                    }
                }
                $artist=implode($artist);
                require('connect_db.php');
                $sql="SELECT id, owner_id FROM music WHERE artist='$artist' ORDER BY rand() LIMIT 1";
                $result=mysqli_query($dbc, $sql);
                $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                if (isset($row['id'])){
                    $owner_id=$row['owner_id'];
                    $id=$row['id'];
                    $audio="audio".$owner_id."_".$id;
                    $request_params = array(
                    'attachment' => $audio,
                    'user_id' => $user_id,
                    'access_token' => $token,
                    'v' => '5.0'
                    );
                    $get_params = http_build_query($request_params);
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    mysqli_close($dbc);
                    echo('ok');
                    exit();
                }else{
                    $request_params = array(
                    'message' => 'В моей базе нет треков этого исполнителся.
                    Попробуй добавить с помощью команды
                    !m #(прикрепи аудио во вложения)',
                    'user_id' => $user_id,
                    'access_token' => $token,
                    'v' => '5.0'
                    );
                    $get_params = http_build_query($request_params);
                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    mysqli_close($dbc);
                    echo('ok');
                    exit();
                }
            }else{
                $request_params = array(
                'message' => "Вы пропустили символ %.
                Формат команды-!m %<исполнитель>%,
                например, !m %T-Fest%",
                'user_id' => $user_id,
                'access_token' => $token,
                'v' => '5.0'
                );
                $get_params = http_build_query($request_params);
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                echo('ok');
                exit();
            }
            echo ('ok');
            exit();
        break;
        case 't':
        //модуль исправления
            $i=0;
            $k=4;
            $len=0;
            $correct=array();
            if ($arr[3]=="%"){
                while ($i==0){
                    if ($len>1500){
                        $request_params = array(
                        'message' => "Сообщение должно быть короче 1500 символов.",
                        'user_id' => $user_id,
                        'access_token' => $token,
                        'v' => '5.0'
                        );
                        $get_params = http_build_query($request_params);
                        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                        echo('ok');
                        exit();
                        $i++;
                    }else{
                        if ($arr[$k]=="%"){
                            $i++;
                        }else{
                            switch ($arr[$k]){
                                case 'Q':
                                    array_push($correct, 'Й');
                                break;
                                case 'q':
                                    array_push($correct, 'й');
                                break;
                                case 'W':
                                    array_push($correct, 'Ц');
                                break;
                                case 'w':
                                    array_push($correct, 'ц');
                                break;
                                case 'E':
                                    array_push($correct, 'У');
                                break;
                                case 'e':
                                    array_push($correct, 'у');
                                break;
                                case 'R':
                                    array_push($correct, 'К');
                                break;
                                case 'r':
                                    array_push($correct, 'к');
                                break;
                                case 'T':
                                    array_push($correct, 'Е');
                                break;
                                case 't':
                                    array_push($correct, 'е');
                                break;
                                case 'Y':
                                    array_push($correct, 'Н');
                                break;
                                case 'y':
                                    array_push($correct, 'н');
                                break;
                                case 'U':
                                    array_push($correct, 'Г');
                                break;
                                case 'u':
                                    array_push($correct, 'г');
                                break;
                                case 'I':
                                    array_push($correct, 'Ш');
                                break;
                                case 'i':
                                    array_push($correct, 'ш');
                                break;
                                case 'O':
                                    array_push($correct, 'Щ');
                                break;
                                case 'o':
                                    array_push($correct, 'щ');
                                break;
                                case 'P':
                                    array_push($correct, 'З');
                                break;
                                case 'p':
                                    array_push($correct, 'з');
                                break;
                                case '{':
                                    array_push($correct, 'Х');
                                break;
                                case '[':
                                    array_push($correct, 'х');
                                break;
                                case '}':
                                    array_push($correct, 'Ъ');
                                break;
                                case ']':
                                    array_push($correct, 'ъ');
                                break;
                                case 'A':
                                    array_push($correct, 'Ф');
                                break;
                                case 'a':
                                    array_push($correct, 'ф');
                                break;
                                case 'S':
                                    array_push($correct, 'Ы');
                                break;
                                case 's':
                                    array_push($correct, 'ы');
                                break;
                                case 'D':
                                    array_push($correct, 'В');
                                break;
                                case 'd':
                                    array_push($correct, 'в');
                                break;
                                case 'F':
                                    array_push($correct, 'А');
                                break;
                                case 'f':
                                    array_push($correct, 'а');
                                break;
                                case 'G':
                                    array_push($correct, 'П');
                                break;
                                case 'g':
                                    array_push($correct, 'п');
                                break;
                                case 'H':
                                    array_push($correct, 'Р');
                                break;
                                case 'h':
                                    array_push($correct, 'р');
                                break;
                                case 'J':
                                    array_push($correct, 'О');
                                break;
                                case 'j':
                                    array_push($correct, 'о');
                                break;
                                case 'K':
                                    array_push($correct, 'Л');
                                break;
                                case 'k':
                                    array_push($correct, 'л');
                                break;
                                case 'L':
                                    array_push($correct, 'Д');
                                break;
                                case 'l':
                                    array_push($correct, 'д');
                                break;
                                case ':':
                                    array_push($correct, 'Ж');
                                break;
                                case ';':
                                    array_push($correct, 'ж');
                                break;
                                case '"':
                                    array_push($correct, 'Э');
                                break;
                                case "'":
                                    array_push($correct, 'э');
                                break;
                                case 'Z':
                                    array_push($correct, 'Я');
                                break;
                                case 'z':
                                    array_push($correct, 'я');
                                break;
                                 case 'X':
                                    array_push($correct, 'Ч');
                                break;
                                case 'x':
                                    array_push($correct, 'ч');
                                break;
                                 case 'C':
                                    array_push($correct, 'С');
                                break;
                                case 'c':
                                    array_push($correct, 'с');
                                break;
                                 case 'V':
                                    array_push($correct, 'М');
                                break;
                                case 'v':
                                    array_push($correct, 'м');
                                break;
                                 case 'B':
                                    array_push($correct, 'И');
                                break;
                                case 'b':
                                    array_push($correct, 'и');
                                break;
                                 case 'N':
                                    array_push($correct, 'Т');
                                break;
                                case 'n':
                                    array_push($correct, 'т');
                                break;
                                 case 'M':
                                    array_push($correct, 'Ь');
                                break;
                                case 'm':
                                    array_push($correct, 'ь');
                                break;
                                 case '<':
                                    array_push($correct, 'Б');
                                break;
                                case ',':
                                    array_push($correct, 'б');
                                break;
                                 case '>':
                                    array_push($correct, 'Ю');
                                break;
                                case '.':
                                    array_push($correct, 'ю');
                                break;
                                case '~':
                                    array_push($correct, 'Ё');
                                break;
                                case '`':
                                    array_push($correct, 'ё');
                                break;
                                case '/':
                                    array_push($correct, '.');
                                break;
                                default:
                                    array_push($correct, $arr[$k]);
                            }
                            $len++;
                            $k++;
                        }
                    }
                }
                $correct=implode($correct);
                $request_params = array(
                'message' => $correct,
                'user_id' => $user_id,
                'access_token' => $token,
                'v' => '5.0'
                );
                $get_params = http_build_query($request_params);
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                echo('ok');
                exit();
            }
        break;
        case 'r':
            //функции для счёта денег
            require('connect_db.php');
            $sql="SELECT * FROM money WHERE user_id='$user_id'";
            $result=mysqli_query($dbc, $sql);
            $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
            if (!isset($row['user_id'])){
                $zero=1;
                $sql="INSERT INTO money
                (user_id)
                VALUES
                ('$user_id')";
                mysqli_query($dbc, $sql);
                mysqli_close($dbc);
                $request_params = array(
                'message' => "Набор функций 'деньги' активирован.",
                'user_id' => $user_id,
                'access_token' => $token,
                'v' => '5.0'
                );
                $get_params = http_build_query($request_params);
                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                echo('ok');
                exit();
            }else{
                switch ($arr[3]){
                    case 'a':
                        $sql="SELECT incomes, costs, cash, card FROM money WHERE user_id='$user_id'";
                        $result=mysqli_query($dbc, $sql);
                        $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
                        mysqli_close($dbc);
                        $uid=$row['user_id'];
                        $incomes=$row['incomes'];
                        $costs=$row['costs'];
                        $cash=$row['cash'];
                        $card=$row['card'];
                        $total=$cash+$card;
                        $request_params = array(
                        'message' => "Ваши доходы составляют $incomes руб.
                        Ваши расходы составляют $costs руб.
                        Всего у вас $total руб.
                        Из них наличными $cash руб.
                        И на карте $card руб.",
                        'user_id' => $user_id,
                        'access_token' => $token,
                        'v' => '5.0'
                        );
                        $get_params = http_build_query($request_params);
                        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                        echo('ok');
                        exit();
                    break;
                    case 'p':
                        require('connect_db.php');
                        if ($arr[5]=='c'){
                            $i=0;
                            $k=8;
                            $len=0;
                            $money=array();
                            while ($i==0){
                                if ($arr[$k]=="%"){
                                    $i++;
                                }else{
                                    if ($len>20){
                                        $request_params = array(
                                        'message' => "Слишком длинное значение.",
                                        'user_id' => $user_id,
                                        'access_token' => $token,
                                        'v' => '5.0'
                                        );
                                        $get_params = http_build_query($request_params);
                                        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                        echo('ok');
                                        exit();
                                    }else{
                                        array_push($money, $arr[$k]);
                                        $k++;
                                        $len++;
                                    }
                                }
                            }
                            $money=implode($money);
                            $money=0+$money;
                            $type=gettype($money);
                            if ($type=='integer'){
                                $sql="SELECT card, incomes FROM money WHERE user_id='$user_id'";
                                $result=mysqli_query($dbc, $sql);
                                $card=$row['card']+$money;
                                $incomes=$row['incomes']+$money;
                                $sql="UPDATE money SET card='$card', incomes='$incomes' WHERE user_id='$user_id'";
                                mysqli_query($dbc, $sql);
                                mysqli_close($dbc);
                                $request_params = array(
                                'message' => "Доход записан.",
                                'user_id' => $user_id,
                                'access_token' => $token,
                                'v' => '5.0'
                                );
                                $get_params = http_build_query($request_params);
                                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                echo('ok');
                                exit();
                            }else{
                                $request_params = array(
                                'message' => "Колличество денег должно быть целым числом",
                                'user_id' => $user_id,
                                'access_token' => $token,
                                'v' => '5.0'
                                );
                                $get_params = http_build_query($request_params);
                                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                echo('ok');
                                exit();
                            }
                        }
                        if ($arr[5]=='m'){
                            $i=0;
                            $k=8;
                            $len=0;
                            $money=array();
                            while ($i==0){
                                if ($arr[$k]=="%"){
                                    $i++;
                                }else{
                                    if ($len>20){
                                        $request_params = array(
                                        'message' => "Слишком длинное значение.",
                                        'user_id' => $user_id,
                                        'access_token' => $token,
                                        'v' => '5.0'
                                        );
                                        $get_params = http_build_query($request_params);
                                        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                        echo('ok');
                                        exit();
                                    }else{
                                        array_push($money, $arr[$k]);
                                        $k++;
                                        $len++;
                                    }
                                }
                            }
                            $money=implode($money);
                            $money=0+$money;
                            $type=gettype($money);
                            if ($type=='integer'){
                                $sql="SELECT cash, incomes FROM money WHERE user_id='$user_id'";
                                $result=mysqli_query($dbc, $sql);
                                $cash=$row['cash']+$money;
                                $incomes=$row['incomes']+$money;
                                $sql="UPDATE money SET cash='$cash', incomes='$incomes' WHERE user_id='$user_id'";
                                mysqli_query($dbc, $sql);
                                mysqli_close($dbc);
                                $request_params = array(
                                'message' => "Доход записан.",
                                'user_id' => $user_id,
                                'access_token' => $token,
                                'v' => '5.0'
                                );
                                $get_params = http_build_query($request_params);
                                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                echo('ok');
                                exit();
                            }else{
                                $request_params = array(
                                'message' => "Колличество денег должно быть целым числом",
                                'user_id' => $user_id,
                                'access_token' => $token,
                                'v' => '5.0'
                                );
                                $get_params = http_build_query($request_params);
                                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                echo('ok');
                                exit();
                            }
                        }
                    break;
                    case 'm':
                        require('connect_db.php');
                        if ($arr[5]=='c'){
                            $i=0;
                            $k=8;
                            $len=0;
                            $money=array();
                            while ($i==0){
                                if ($arr[$k]=="%"){
                                    $i++;
                                }else{
                                    if ($len>20){
                                        $request_params = array(
                                        'message' => "Слишком длинное значение.",
                                        'user_id' => $user_id,
                                        'access_token' => $token,
                                        'v' => '5.0'
                                        );
                                        $get_params = http_build_query($request_params);
                                        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                        echo('ok');
                                        exit();
                                    }else{
                                        array_push($money, $arr[$k]);
                                        $k++;
                                        $len++;
                                    }
                                }
                            }
                            $money=implode($money);
                            $money=0+$money;
                            $type=gettype($money);
                            if ($type=='integer'){
                                $sql="SELECT card, costs FROM money WHERE user_id='$user_id'";
                                $result=mysqli_query($dbc, $sql);
                                $card=$row['card']-$money;
                                $costs=$row['costs']+$money;
                                $sql="UPDATE money SET card='$card', costs='$costs' WHERE user_id='$user_id'";
                                mysqli_query($dbc, $sql);
                                mysqli_close($dbc);
                                $request_params = array(
                                'message' => "Расход записан.",
                                'user_id' => $user_id,
                                'access_token' => $token,
                                'v' => '5.0'
                                );
                                $get_params = http_build_query($request_params);
                                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                echo('ok');
                                exit();
                            }else{
                                $request_params = array(
                                'message' => "Колличество денег должно быть целым числом",
                                'user_id' => $user_id,
                                'access_token' => $token,
                                'v' => '5.0'
                                );
                                $get_params = http_build_query($request_params);
                                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                echo('ok');
                                exit();
                            }
                        }
                        if ($arr[5]=='m'){
                            $i=0;
                            $k=8;
                            $len=0;
                            $money=array();
                            while ($i==0){
                                if ($arr[$k]=="%"){
                                    $i++;
                                }else{
                                    if ($len>20){
                                        $request_params = array(
                                        'message' => "Слишком длинное значение.",
                                        'user_id' => $user_id,
                                        'access_token' => $token,
                                        'v' => '5.0'
                                        );
                                        $get_params = http_build_query($request_params);
                                        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                        echo('ok');
                                        exit();
                                    }else{
                                        array_push($money, $arr[$k]);
                                        $k++;
                                        $len++;
                                    }
                                }
                            }
                            $money=implode($money);
                            $money=0+$money;
                            $type=gettype($money);
                            if ($type=='integer'){
                                $sql="SELECT cash, costs FROM money WHERE user_id='$user_id'";
                                $result=mysqli_query($dbc, $sql);
                                $cash=$row['cash']-$money;
                                $costs=$row['costs']+$money;
                                $sql="UPDATE money SET cash='$cash', costs='$costs' WHERE user_id='$user_id'";
                                mysqli_query($dbc, $sql);
                                mysqli_close($dbc);
                                $request_params = array(
                                'message' => "Расход записан.",
                                'user_id' => $user_id,
                                'access_token' => $token,
                                'v' => '5.0'
                                );
                                $get_params = http_build_query($request_params);
                                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                echo('ok');
                                exit();
                            }else{
                                $request_params = array(
                                'message' => "Колличество денег должно быть целым числом",
                                'user_id' => $user_id,
                                'access_token' => $token,
                                'v' => '5.0'
                                );
                                $get_params = http_build_query($request_params);
                                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                echo('ok');
                                exit();
                            }
                        }
                    break;
                    default:
                        $request_params = array(
                        'message' => "Неверная команда, кожаный ублюдок.",
                        'user_id' => $user_id,
                        'access_token' => $token,
                        'v' => '5.0'
                        );
                        $get_params = http_build_query($request_params);
                        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                        echo('ok');
                        exit();
                }
            }
        break;
    }
}
if ($arr[0]=="%"){
    //обучение бота
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
  $sql="SELECT ans FROM Answers WHERE mes='$msg'";
  $result=mysqli_query($dbc, $sql);
  if (mysqli_num_rows($result)>0){
      if(mysqli_num_rows($result)==1){
        $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
        $request_params = array(
        'message' => $row['ans'],
        'user_id' => $user_id,
        'access_token' => $token,
        'v' => '5.0'
        );
        $get_params = http_build_query($request_params);
        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
        mysqli_close($dbc);
      }else{
        $sql="SELECT ans FROM Answers WHERE mes='$msg' ORDER BY rand() LIMIT 1";
        $result=mysqli_query($dbc, $sql);
        $row=mysqli_fetch_array($result, MYSQLI_ASSOC);
        $request_params = array(
        'message' => $row['ans'],
        'user_id' => $user_id,
        'access_token' => $token,
        'v' => '5.0'
        );
        $get_params = http_build_query($request_params);
        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
        mysqli_close($dbc);
     }
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
}
echo('ok');
break;
case 'wall_repost':
    $user_id = $data->object->owner_id;
    $request_params = array(
    'message' => "Благодарю тебя за репост записи и помощь в развитии паблоса, бро!",
    'user_id' => $user_id,
    'access_token' => $token,
    'v' => '5.0'
    );
    $get_params = http_build_query($request_params);
    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
    echo('ok');
break;
case 'group_join':
    $user_id = $data->object->user_id;
    $request_params = array(
    'message' => "Добро пожаловать в наши ряды, салага!",
    'user_id' => $user_id,
    'access_token' => $token,
    'v' => '5.0'
    );
    $get_params = http_build_query($request_params);
    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
    echo('ok');
break;
case 'group_leave':
    $user_id = $data->object->user_id;
    $request_params = array(
    'message' => "Я так и знала, что ты слабак, который меня не вытерпит!",
    'user_id' => $user_id,
    'access_token' => $token,
    'v' => '5.0'
    );
    $get_params = http_build_query($request_params);
    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
    echo('ok');
break;
}
