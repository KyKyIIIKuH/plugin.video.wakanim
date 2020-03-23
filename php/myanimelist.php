<?
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

define ( 'ROOT_DIR', dirname ( __FILE__ ) );

require_once ROOT_DIR . '/mysqlidb.class.php';

$username = $_GET["login"];
$url = "https://myanimelist.net/login.php?from=%2F";
$newurl = "https://myanimelist.net/";
$add_anime = "https://myanimelist.net/ownlist/anime/add.json";

$ua = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36";

$cookie_list = "";

$db_connect = new MySQLiDB();
$db = $db_connect->open();

if(isset($_GET["auth"]) && !empty($_GET["auth"])) {
	$password = $_GET["password"];
	if(isset($password) && empty($password) || !isset($password) && empty($password)) return false;
	if(isset($username) && empty($username) || !isset($username) && empty($username)) return false;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT,$ua);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: {$cookie_list}"));
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, ROOT_DIR."/cookie/cookie_{$username}.txt");
	curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$username}.txt");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	$res = curl_exec($ch);
	curl_close($ch);

	preg_match("/({$username})/", trim($res), $login_status);

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
		curl_setopt($ch, CURLOPT_POSTFIELDS,"user_name={$username}&password={$password}&cookie=1&sublogin=Login&submit=1&csrf_token=".$csrf_token);
		curl_setopt($ch, CURLOPT_COOKIEJAR, ROOT_DIR."/cookie/cookie_{$username}.txt");
		curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$username}.txt");
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
		curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$username}.txt");
		$info = curl_getinfo($ch);
		$res = curl_exec($ch);
		curl_close($ch);

		preg_match("/({$username})/", trim($res), $login_status);
	}

	if(isset($login_status) && empty($login_status) || !isset($login_status) && empty($login_status)) {
		echo "FAIL";
		exit();
	}

	$run_query_users = $db_connect->query("SELECT `login`, `password` FROM `mal_users` WHERE `login`='{$username}';");
	$result_query_users = $db_connect->fetch_array($run_query_users)[0];

	if(isset($result_query_users["login"]) && empty($result_query_users["login"]) || !isset($result_query_users["login"]) && empty($result_query_users["login"])) {
		$db_connect->exec("INSERT IGNORE INTO `mal_users` (`login`, `password`) VALUES ('{$username}', '{$password}');");
	}
	if(isset($result_query_users["login"]) && !empty($result_query_users["login"])) {
		if(isset($result_query_users["password"]) && !empty($result_query_users["password"])) {
			if($result_query_users["password"] != $password) {
				$db_connect->exec("UPDATE `mal_users` SET `password`='{$password}' WHERE `login`='{$username}';");
			}
		}
	}

	echo "OK";
	exit();
}

$id_season = $_GET["id_season"];
if(isset($id_season) && empty($id_season) || !isset($id_season) && empty($id_season)) return false;

$run_query_list_anime_id = $db_connect->query("SELECT `mal_id` FROM `wak_list_anime` WHERE `id_season`='{$id_season}';");
$result_query_list_anime_id = $db_connect->fetch_array($run_query_list_anime_id);
$anime_id = $result_query_list_anime_id[0]["mal_id"];

if(isset($result_query_list_anime_id[0]["mal_id"]) && empty($result_query_list_anime_id[0]["mal_id"]) || !isset($result_query_list_anime_id[0]["mal_id"]) && empty($result_query_list_anime_id[0]["mal_id"])) {
	$id_anime_wak = $_GET["id_anime"];

	$db_connect->exec("INSERT IGNORE INTO `wak_list_anime` (`id_anime`, `id_season`) VALUES ('{$id_anime_wak}', '{$id_season}');");
}

if(isset($_GET["check_wlist"]) && !empty($_GET["check_wlist"])) {
	$run_query_list_anime_id = $db_connect->query("SELECT `mal_id` FROM `wak_list_anime` WHERE `id_season`='{$id_season}';");
	$result_query_list_anime_id = $db_connect->fetch_array($run_query_list_anime_id);
	$anime_id = $result_query_list_anime_id[0]["mal_id"];

	if(isset($result_query_list_anime_id[0]["mal_id"]) && empty($result_query_list_anime_id[0]["mal_id"]) || !isset($result_query_list_anime_id[0]["mal_id"]) && empty($result_query_list_anime_id[0]["mal_id"])) {
		$id_anime_wak = $_GET["id_anime"];
		$title_wak = $_GET["title"];

		if(isset($id_anime_wak) && empty($id_anime_wak) || !isset($id_anime_wak) && empty($id_anime_wak)) return false;
		if(isset($title_wak) && empty($title_wak) || !isset($title_wak) && empty($title_wak)) return false;

		$title_anime = urlencode($title_wak);

		$anime_id = file_get_contents("https://ploader.ru/scripts/php/url_js.php?method=GET&url=myanimelist.net/search/prefix.json?type=all&keyword={$title_anime}&v=1");
		$anime_id = (array) json_decode($anime_id, true);

		foreach ($anime_id as $key => $value) {
			$value = (array) json_decode($value, true);
			foreach ($value as $key2 => $value2) {
				foreach ($value2 as $key3 => $value3) {
					if($value3["type"] == "anime") {
						foreach ($value3["items"] as $key4 => $value4) {
							$anime_id = $value4["id"];
							break;
						}
					}
				}
			}
		}

		$db_connect->exec("UPDATE `wak_list_anime` SET `mal_id`='{$anime_id}' WHERE `id_anime`='{$id_anime_wak}' AND `id_season`='{$id_season}';");
	}
	exit();
}

if(isset($_GET["check_ep"]) && !empty($_GET["check_ep"])) {
	$run_query_list = $db_connect->query("SELECT `ep` FROM `mal_list` WHERE `username`='{$username}' AND `anime_id`='{$anime_id}';");
	$result_query_list = $db_connect->fetch_array($run_query_list);

	if(isset($result_query_list[0]["ep"]) && !empty($result_query_list[0]["ep"])) {
		echo $result_query_list[0]["ep"];
		exit();
	}

	if(isset($result_query_list[0]["ep"]) && empty($result_query_list[0]["ep"]) || !isset($result_query_list[0]["ep"]) && empty($result_query_list[0]["ep"])) {
		// Получаем текущую серию
		$ch = curl_init("https://myanimelist.net/anime/{$anime_id}/");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT,$ua);
		curl_setopt($ch, CURLOPT_REFERER, "https://myanimelist.net/");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("DNT: 1", "sec-fetch-dest: document", "sec-fetch-mode: navigate", "sec-fetch-site: cross-site", "sec-fetch-user: ?1", "upgrade-insecure-requests: 1"));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$username}.txt");
		$info = curl_getinfo($ch);
		$res = curl_exec($ch);
		curl_close($ch);

		$pattern_ep_site = '/<input(.*?)id=\"myinfo_watchedeps\"(.*)value=\"(.*?)\"/i';
		preg_match_all($pattern_ep_site, $res, $ep_site);

		/*
		if(isset($ep_site[3][0]) && empty($ep_site[3][0]) || !isset($ep_site[3][0]) && empty($ep_site[3][0])) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT,$ua);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: {$cookie_list}"));
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_COOKIEJAR, ROOT_DIR."/cookie/cookie_{$username}.txt");
			curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$username}.txt");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			$res = curl_exec($ch);
			curl_close($ch);

			preg_match("/<meta name='csrf_token' content='([^']+)'>/", trim($res), $csrf_token);
			$csrf_token = $csrf_token[1];

			$ch = curl_init($add_anime);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT,$ua);
			curl_setopt($ch, CURLOPT_REFERER, "https://myanimelist.net/anime/{$anime_id}/");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("DNT: 1", "sec-fetch-dest: empty", "sec-fetch-mode: cors", "sec-fetch-site: same-origin", "content-type: application/x-www-form-urlencoded; charset=UTF-8"));
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS,'{"anime_id":'.$anime_id.',"status":6,"score":0,"num_watched_episodes":0,"csrf_token":"'.$csrf_token.'"}');
			curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$username}.txt");
			$info = curl_getinfo($ch);
			$res = curl_exec($ch);
			curl_close($ch);

			// Получаем текущую серию
			$ch = curl_init("https://myanimelist.net/anime/{$anime_id}/");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT,$ua);
			curl_setopt($ch, CURLOPT_REFERER, "https://myanimelist.net/");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("DNT: 1", "sec-fetch-dest: document", "sec-fetch-mode: navigate", "sec-fetch-site: cross-site", "sec-fetch-user: ?1", "upgrade-insecure-requests: 1"));
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_COOKIEFILE, ROOT_DIR."/cookie/cookie_{$username}.txt");
			$info = curl_getinfo($ch);
			$res = curl_exec($ch);
			curl_close($ch);

			$pattern_ep_site = '/<input(.*?)id=\"myinfo_watchedeps\"(.*)value=\"(.*?)\"/i';
			preg_match_all($pattern_ep_site, $res, $ep_site);

			echo $ep_site[3][0];
			exit();
		}
		*/

		if(isset($ep_site[3][0]) && empty($ep_site[3][0]) || !isset($ep_site[3][0]) && empty($ep_site[3][0])) {
			echo 0;
			exit();
		}
		
		$db_connect->exec("INSERT INTO `mal_cron` (`username`, `anime_id`, `ep`, `status`, `datetime`) VALUES ('{$username}', '{$anime_id}', '{$ep_site[3][0]}', '1', '".time()."') ON DUPLICATE KEY UPDATE ep=VALUES (ep);");

		echo $ep_site[3][0];
		exit();
	}
	exit();
}

if(isset($_GET["check_ep_all"]) && !empty($_GET["check_ep_all"])) {
	$run_query_list = $db_connect->query("SELECT `ep_all` FROM `mal_list` WHERE `username`='{$username}' AND `anime_id`='{$anime_id}';");
	$result_query_list = $db_connect->fetch_array($run_query_list);

	echo $result_query_list[0]["ep_all"];
	exit();
}

if(isset($_GET["update"]) && !empty($_GET["update"])) {
	$episodes = $_GET["ep"];
	$status = $_GET["status"];
	
	if(isset($episodes) && empty($episodes) || !isset($episodes) && empty($episodes)) return false;
	if(isset($status) && empty($status) || !isset($status) && empty($status)) return false;

	$db_connect->exec("INSERT INTO `mal_cron` (`username`, `anime_id`, `ep`, `status`, `datetime`) VALUES ('{$username}', '{$anime_id}', '{$episodes}', '{$status}', '".time()."') ON DUPLICATE KEY UPDATE ep=VALUES (ep);");
}
@$db_connect->close();

echo "OK";
?>
