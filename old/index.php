<?php

if ($_SERVER['SERVER_PORT']!=443) {
	header("Location: https://wur.me");
}

if (($_POST['username']) && ($_POST['password'])) {

class base62 { static $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';	static $base = 62;	public function encode($var) {		$stack = array();		while (bccomp($var, 0) != 0) {			$remainder = bcmod($var, self::$base);			$var = bcdiv( bcsub($var, $remainder), self::$base );			array_push($stack, self::$characters[$remainder]);		}		return implode('', array_reverse($stack));	}	public function decode($var) {		$length = strlen($var);		$result = 0;		for($i=0; $i<$length; $i++) {			$result = bcadd($result, bcmul(self::get_digit($var[$i]), bcpow(self::$base, ($length-($i+1)))));		}			return $result;	}	private function get_digit($var){		return strpos(self::$characters, $var);	}}
function flickr_encode($num) { $alphabet="123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";$base_count = strlen($alphabet);$encoded = '';while ($num >= $base_count) {$div = $num/$base_count;$mod = ($num-($base_count*intval($div)));$encoded = $alphabet[$mod] . $encoded;$num = intval($div);}if ($num) $encoded = $alphabet[$num] . $encoded;return $encoded;}

require('database.inc');

if (!$_POST['message']) $$_POST['message'] = "wur.me";
if ($_POST['message'] == "") $_POST['message'] = "wur.me";
$_POST['message'] = str_ireplace("\\","",$_POST['message']);
$_POST['message'] = str_ireplace("'","&#146;",$_POST['message']);
//$_POST['message'] = htmlspecialchars($_POST['message']);
//$_POST['message'] = mysql_real_escape_string(htmlspecialchars($_POST['message']));
if (strpos($_POST['message'],'@') === 0) $_POST['message'] = str_ireplace('@',' @',$_POST['message']);

$curl_handle = curl_init();
curl_setopt($curl_handle, CURLOPT_URL, "http://twitter.com/account/verify_credentials.json");
curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl_handle, CURLOPT_USERPWD, $_POST['username'] . ":" . $_POST['password']);
$twitter = json_decode(curl_exec($curl_handle),TRUE);
curl_close($curl_handle);
if (!$twitter['screen_name']) { die("<?xml version=\"1.0\" encoding=\"UTF-8\"?//><rsp stat=\"fail\"><err code=\"1001\" msg=\"Invalid twitter username or password\" /></rsp>"); }

$_POST['password']=md5($_POST['password']);

$result = mysql_query("select v from u where k='" . $_POST['username'] . "'", $dbh) or die(mysql_error() . "\n");
if (mysql_num_rows($result)) {
	$user = json_decode(mysql_result($result,0,0),TRUE);
	if ($user['password'] != md5($_POST['password'])) {
		$user['password'] = md5($_POST['password']);
		//echo "update u set v='" . json_encode($user) . "' where k='" . $_POST['username'] . "'";
		mysql_query ("update u set v='" . json_encode($user) . "' where k='" . $_POST['username'] . "'", $dbh) or die(mysql_error() . "\n");
	}
} else {
	$user['password']=md5($_POST['password']);
	mysql_query ("insert into u (k,v) values ('" . $_POST['username'] . "','" . json_encode($user) . "')", $dbh) or die(mysql_error() . "\n");
}

if (isset($_POST['debug'])) {
	echo "$" . "_POST " . json_encode($_POST) . "<br />";
	echo "$" . "_FILES " . json_encode($_FILES) . "<br />";
	echo "$" . "twitter " . json_encode($twitter) . "<br />";
	echo "$" . "user " . json_encode($user) . "<br />";
	die ("Debug only");
}

mysql_query ("insert into i (v) values ('" . json_encode($_POST) . "')", $dbh) or die(mysql_error() . "\n");
$id = base62::encode(mysql_insert_id());
if (!move_uploaded_file($_FILES['media']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . '/image/' . $id . '.jpg')) { die("<?xml version=\"1.0\" encoding=\"UTF-8\"?><rsp stat=\"fail\"><err code=\"1002\" msg=\"Image not moved\" /></rsp>"); }
$result = mysql_query("select v from i where k=" . base62::decode($id), $dbh) or die(mysql_error() . "\n");
$img = json_decode(mysql_result($result,0,0),TRUE);
$img['id'] = $id;
if ($user['flickr']) {
	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_URL, "http://api.flickr.com/services/upload");
	curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_handle, CURLOPT_POST, 1);
	$CURLOPT_POSTFIELDS = array(
		'photo' => "@" . $_SERVER['DOCUMENT_ROOT'] . "/image/" . $img['id'] . ".jpg",
		'api_key' => "57ff38054ce1c498d987028e8497d2ac",
		'auth_token' => $user['flickr']['auth']['token']['_content'],
		'title' => $img['message'],
		'description' => "Uploaded from <a href=\"http://www.atebits.com/tweetie-iphone/\">Tweetie 2</a> via <a href=\"https://wur.me\">https://wur.me</a>",
		'is_public' => "1",
		'api_sig' => md5("9bd0915f79a5f7d7api_key57ff38054ce1c498d987028e8497d2acauth_token" . $user['flickr']['auth']['token']['_content'] . "descriptionUploaded from <a href=\"http://www.atebits.com/tweetie-iphone/\">Tweetie 2</a> via <a href=\"https://wur.me\">https://wur.me</a>is_public1title" . $img['message'])
	);
	curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $CURLOPT_POSTFIELDS);
	$flickr = curl_exec($curl_handle);
	curl_close($curl_handle);
	header("Content-type: text/xml");
	if (strpos($flickr,'stat="ok"')) {
		$img['flickr']['id'] = floatval(trim(strip_tags($flickr)));
		$img['flickr']['url'] = "http://flic.kr/p/" . flickr_encode($img['flickr']['id']);
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<rsp stat=\"ok\">\r\n <mediaid>" . flickr_encode($img['flickr']['id']) . "</mediaid>\r\n <mediaurl>" . $img['flickr']['url'] . "</mediaurl>\r\n</rsp>";
	} else {
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><rsp stat=\"fail\"><err code=\"1002\" msg=\"Image not found\" /></rsp>";
		$debugfile = fopen('debug.txt', 'a') or die("Can't open file");
		fwrite($debugfile, "$" . "_POST" . json_encode($_POST) . "\n");
		fwrite($debugfile, "$" . "_FILES" . json_encode($_FILES) . "\n");
		fwrite($debugfile, "$" . "twitter" . json_encode($twitter) . "\n");
		fwrite($debugfile, "$" . "user" . json_encode($user) . "\n");
		fwrite($debugfile, "\n");
		fclose($debugfile);
	}
} else {
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><rsp stat=\"ok\"><mediaid>" . $id . "</mediaid><mediaurl>http://wur.me/" . $id . "</mediaurl></rsp>";
}

mysql_query("update i set v='" . json_encode($img) . "' where k=" . base62::decode($id), $dbh) or die(mysql_error() . "\n");

}else{?>
<html><head>
<meta name="viewport" content="width=320; initial-scale=0.9; maximum-scale=0.9; user-scalable=0;"/>
<style type="text/css" media="screen">@import "iphonenav.css";</style>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-5973800-5");
pageTracker._trackPageview();
} catch(err) {}</script>
<title>wur.me</title></head><body><center>
wur.me is a tool for posting<br />
to Flickr from Tweetie 2<br />
<br />
Login using your Twitter<br />
credentials to get started:<br />
<br />
<form action="flickr.php" method="post" enctype="multipart/form-data">
Username: <input type="text" name="username" value="" /><br />
Password: <input type="password" name="password" value="" /><br />
(we don't store your password)
<button type="submit">Login</button>
</form>
<br />
More services coming soon<br />
<a href="http://twitter.com/wur_me">@wur_me</a><br />
<a href="http://twitter.com/JustinCampbell">@JustinCampbell</a><br />
<br />
<img src="tweetie.jpg" /><br />
</center></body></html>
<?php } ?>