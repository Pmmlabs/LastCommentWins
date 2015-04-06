<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">
  <head>  
    <link rel="stylesheet" type="text/css" href="style.css" />  
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta property="og:image" content="LCW.png">
    <title>Last comment wins</title>  
  </head>  
  <body onload="update()">  
    <div class="header">Last comment wins</div>  
    <div class="label">The Real Time Battle</div>  
    <table class="persons">
      <tr>  
        <td></td>
	<td>Эффективность</td>
      </tr> 

<?php
	$login = ''; //Логин
	$pass = ''; //Пароль
	$app = '2274003'; //id приложения для получения token
	$key = 'hHbZxrka2uZ6jB1inYsH'; //secret key приложения

	$acc_token_file='vk_token.php';
	$access_token = '';
	$vk_limit = 100;    // количество дополнительных загрузок. Сразу всё скачать нельзя, ограничение ВК 100 комментов :(
	$method_str = 'wall.getComments?sort=asc&preview_length=1&count='.$vk_limit.'&owner_id=-12922665&post_id=3402';  // Здесь задается id владельца записи и id записи
	$uids = array(); // ключ - user id, значение - сумма временных промежутков в секундах
	$counts = array(); // количество комментов каждого
	load_token();
	$comments = api($method_str);
	$comments = $comments['response'];
	$count_comments = $comments[0];
	unset($comments[0]);
	$current_date = $comments[1]['date'];
	$current_uid = $comments[1]['uid'];
	unset($comments[1]);
	proc_comments($comments);

	$cycles = (int) ($count_comments / $vk_limit); 
	for ($i = 0; $i < $cycles; $i++) {
		$comments = api($method_str . '&offset=' . ($vk_limit*($i+1)));
		$comments = $comments['response'];
		unset($comments[0]); // повторно количество комментов не обрабатывать
		proc_comments($comments);
	}
	
	$uids[$current_uid] = (isset($uids[$current_uid]) ? $uids[$current_uid] : 0) + time() - $current_date;
	$counts[$current_uid] = (isset($counts[$current_uid]) ? $counts[$current_uid] : 0) + 1;
	$last_value = $uids[$current_uid]; // нужно для передачи в javascript, чтобы потом увеличивать
	arsort($uids);
// получение имен и фамилий участников
	$str_uids = 'users.get?uids=';
	foreach ($uids as $key => $val)
		$str_uids .= $key.',';
	$json_uids = api($str_uids);
	$i = 0;
	foreach ($uids as $key => $val) {
	    echo '<tr><td><span class="person'.($key == $current_uid ? ' active' : '').'" id="per'.$key.'">'.$json_uids['response'][$i]['first_name'].' '.$json_uids['response'][$i]['last_name'].' ('.$counts[$key].' комментов)</span><span class="value" id="val'.$key.'">'.date('n месяцев, j дней, H часов, i минут, s секунд',$val).' ('.$val.')</span></td><td>'.(int)($val / $counts[$key]).'</td></tr>';
		$i++;
	}

	function proc_comments($comm) { // обработка блока комментов и заполнение массива uids
	global $uids, $counts, $current_uid, $current_date;
	  	foreach ($comm as $value) {
			$uids[$current_uid] = (isset($uids[$current_uid]) ? $uids[$current_uid] : 0) + $value['date'] - $current_date;
			$counts[$current_uid] = (isset($counts[$current_uid]) ? $counts[$current_uid] : 0) + 1;
			$current_uid = $value['uid'];
			$current_date = $value['date'];
		}
	}

	function load_token($reauth=false) {
	   global $access_token,$acc_token_file;
	   if (!$reauth && file_exists($acc_token_file)) {
		  $s=file_get_contents($acc_token_file);
		  preg_match("/\[([a-f0-9]+)\]/", $s, $matches);
		  if (!$matches[0]){
			 load_token(true);
		  } else {
			 $access_token=$matches[1];
		  }
	   } else {
		  auth_api();
		  if ($access_token==""){
			 echo "Auth Error";
		  } else {
			 file_put_contents($acc_token_file,'<?php if (!defined("vk_online")) die("x_X"); "['.$access_token.']";?>');
		  }
	   }
	}

	function curl( $url ) {
			$ch = curl_init( $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );		
			$response = curl_exec( $ch );
			curl_close( $ch );
			return $response;
	}

	function auth_api(){
	   global $access_token,$app,$key,$login,$pass;
	   $auth = curl( "https://oauth.vk.com/token?grant_type=password&client_id=$app&client_secret=$key&username=$login&password=$pass" ); //Авторизация
	   $json = json_decode( $auth, true );
	   $access_token = $json['access_token'];  
	}

	function api($method){
	   global $access_token;
	   $r = curl("https://api.vk.com/method/$method&access_token=$access_token");
	   $json = json_decode( $r, true );
	   if (isset($json['error'])) {
		  $code=$json['error']['error_code'];
		  if ($code==4 || $code==3) {
			 load_token(true);
			 api($method);
		  } else {
                         echo "fucking fuck";
			 var_dump($json);
		  }
	   } else {
		  return $json;
	   }
	}
?>

</table>  
    <br/>  
    <div class="label">Последний комментарий: <?=date(DATE_RFC850,$current_date) ?>
    <br/>
    There can be only one...
    </div>   
  
    <script type="text/javascript">
      var base_value = <?=$last_value ?>000;  
      function update()  
      {
        base_value += 1000;
        var d = new Date(base_value);
        document.getElementById("val<?=$current_uid ?>").innerHTML = (d.getYear()-70)+' лет, '+(d.getMonth()+1)+' месяцев, '+d.getDate()+' дней, '+d.getHours()+' часов, '+d.getMinutes()+' минут, '+d.getSeconds()+' секунд ('+(base_value/1000)+')';        setTimeout("update()", 1000);  
      }    
    </script>  
  </body>  
</html>