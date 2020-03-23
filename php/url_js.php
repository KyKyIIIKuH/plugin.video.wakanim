<?
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if(empty($_GET["url"]) && !isset($_GET["url"])) return;
if(empty($_GET["method"]) && !isset($_GET["method"])) return;
//if(empty($_GET["params"]) && !isset($_GET["params"])) return;

$method = $_GET["method"];
$params = $_GET["params"];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, (($method == "GET") ? "https://".$_GET["url"]."&keyword=".urlencode($_GET["keyword"]) : "https://".$_GET["url"]));
if($method == "POST") {
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
}
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36");
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json; charset=UTF-8', 'Referer: https://myanimelist.net/'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
$result = curl_exec($ch);
$curl_errno = curl_errno($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error_code = false;
if($httpCode != 200) {
	$error_code = true;
}
curl_close($ch);

echo json_encode($result);
?>
