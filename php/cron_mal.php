<?

define ( 'ROOT_DIR', dirname ( __FILE__ ) );

require_once ROOT_DIR . '/mysqlidb.class.php';

// Подключаемся к базе данных
$db_connect = new MySQLiDB();
$db =$db_connect->open();

$url = "https://myanimelist.net/login.php?from=%2F";
$newurl = "https://myanimelist.net/";
$edit_anime = "https://myanimelist.net/ownlist/anime/edit.json";

$ua = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36";

$cookie_list = "";

$run_query_users = $db_connect->query("SELECT `login`, `password` FROM `mal_users`;");
$result_query_users = $db_connect->fetch_array($run_query_users);

foreach ($result_query_users as $keys_users => $value_users) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT,$ua);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: {$cookie_list}"));
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, ROOT_DIR."/cookie/cookie_{$value_users["login"]}.txt");
	curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$value_users["login"]}.txt");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	$res = curl_exec($ch);
	curl_close($ch);

	preg_match("/({$value_users["login"]})/", trim($res), $login_status);

	preg_match("/<meta name='csrf_token' content='([^']+)'>/", trim($res), $csrf_token);
	$csrf_token = $csrf_token[1];

	if(isset($login_status) && empty($login_status) || !isset($login_status) && empty($login_status)) {
		//авторизация
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $ua);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: {$cookie_list}", "DNT: 1", "sec-fetch-dest: document", "sec-fetch-mode: navigate", "sec-fetch-site: same-origin", "sec-fetch-user: ?1", "upgrade-insecure-requests: 1"));
		curl_setopt($ch, CURLOPT_REFERER, "https://myanimelist.net/login.php?from=%2F");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"user_name={$value_users["login"]}&password={$value_users["password"]}&cookie=1&sublogin=Login&submit=1&csrf_token=".$csrf_token);
		curl_setopt($ch, CURLOPT_COOKIEJAR, ROOT_DIR."/cookie/cookie_{$value_users["login"]}.txt");
		curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$value_users["login"]}.txt");
		$info = curl_getinfo($ch);
		$res = curl_exec($ch);
		curl_close($ch);

		//работа после авторизации
		$ch = curl_init($newurl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT,$ua);
		curl_setopt($ch, CURLOPT_REFERER, "https://myanimelist.net/login.php?from=%2F");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("DNT: 1", "sec-fetch-dest: document", "sec-fetch-mode: navigate", "sec-fetch-site: same-origin", "sec-fetch-user: ?1", "upgrade-insecure-requests: 1"));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$value_users["login"]}.txt");
		$info = curl_getinfo($ch);
		$res = curl_exec($ch);
		curl_close($ch);

		preg_match("/({$value_users["login"]})/", trim($res), $login_status);
	}

	if(isset($login_status) && empty($login_status) || !isset($login_status) && empty($login_status)) {
		echo "FAIL";
	}

	// Список задач
	$run_query_cron = $db_connect->query("SELECT `anime_id`, `ep`, `status`, (SELECT `ep_all` FROM `mal_list` WHERE anime_id=mal_cron.anime_id) as ep_all FROM `mal_cron` WHERE `username`='{$value_users["login"]}';");
	$result_query_cron = $db_connect->fetch_array($run_query_cron);

	foreach ($result_query_cron as $keys_cron => $value_cron) {
		$anime_id = $value_cron["anime_id"];

		// Отправляем запрос на MyanimeList.Net
		$episodes = $value_cron["ep"];
		$status = $value_cron["status"];
		
		if(isset($episodes) && empty($episodes) || !isset($episodes) && empty($episodes)) return false;
		if(isset($status) && empty($status) || !isset($status) && empty($status)) return false;

		if(isset($value_cron["ep_all"]) && !empty($value_cron["ep_all"]) && $value_cron["ep_all"] == $episodes) {
			$status = 2;
		}

		$ch = curl_init($edit_anime);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT,$ua);
		curl_setopt($ch, CURLOPT_REFERER, "https://myanimelist.net/anime/{$anime_id}/");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("DNT: 1", "sec-fetch-dest: empty", "sec-fetch-mode: cors", "sec-fetch-site: same-origin", "content-type: application/x-www-form-urlencoded; charset=UTF-8"));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,'{"anime_id":'.$anime_id.',"status":'.$status.',"score":0,"num_watched_episodes":'.$episodes.',"csrf_token":"'.$csrf_token.'"}');
		curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$value_users["login"]}.txt");
		$info = curl_getinfo($ch);
		$res = curl_exec($ch);
		curl_close($ch);

		$db_connect->exec("DELETE FROM `mal_cron` WHERE `username`='{$value_users["login"]}' AND `anime_id`='{$anime_id}';");
	}

	// Получаем текущую серию
	$ch = curl_init("https://myanimelist.net/anime/{$anime_id}/");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT,$ua);
	curl_setopt($ch, CURLOPT_REFERER, "https://myanimelist.net/");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("DNT: 1", "sec-fetch-dest: document", "sec-fetch-mode: navigate", "sec-fetch-site: cross-site", "sec-fetch-user: ?1", "upgrade-insecure-requests: 1"));
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$value_users["login"]}.txt");
	$info = curl_getinfo($ch);
	$res = curl_exec($ch);
	curl_close($ch);

	$pattern_ep_site = '/<input(.*?)id=\"myinfo_watchedeps\"(.*)value=\"(.*?)\"/i';
	preg_match_all($pattern_ep_site, $res, $ep_site);

	$pattern_ep_all = '/<span(.*?)id=\"curEps\"(.*?)data-num=\"(.*?)\"/i';
	preg_match_all($pattern_ep_all, $res, $ep_all);

	$db_connect->exec("INSERT IGNORE INTO `mal_list` (`username`, `anime_id`, `ep`, `ep_all`) VALUES ('{$value_users["login"]}', '{$anime_id}', '{$ep_site[3][0]}', '{$ep_all[3][0]}') ON DUPLICATE KEY UPDATE ep=VALUES (ep), ep_all=VALUES (ep_all);");
}

echo "OK";

@$db_connect->close();
?>
