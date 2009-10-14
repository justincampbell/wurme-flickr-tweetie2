<?php

require('database.inc');

if (($_POST['username']) && ($_POST['password'])) {

$curl_handle = curl_init();
curl_setopt($curl_handle, CURLOPT_URL, "http://twitter.com/account/verify_credentials.json");
curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl_handle, CURLOPT_USERPWD, $_POST['username'] . ":" . $_POST['password']);
$twitter = json_decode(curl_exec($curl_handle),TRUE);
curl_close($curl_handle);
if (!$twitter['screen_name']) { header("Location: https://wur.me/"); }

$_POST['password']=md5($_POST['password']);

$result = mysql_query("select v from u where k='" . $_POST['username'] . "'", $dbh) or die(mysql_error() . "\n");
if (mysql_num_rows($result)) {
	$user = json_decode(mysql_result($result,0,0),TRUE);
	if ($user['password'] != md5($_POST['password'])) {
		$user['password'] = md5($_POST['password']);
		mysql_query ("update u set v='" . json_encode($user) . "' where k='" . $_POST['username'] . "'", $dbh) or die(mysql_error() . "\n");
	}
} else {
	$user['password']=md5($_POST['password']);
	mysql_query ("insert into u (k,v) values ('" . $_POST['username'] . "','" . json_encode($user) . "')", $dbh) or die(mysql_error() . "\n");
}

session_start();
$_SESSION['username']=$_POST['username'];
$_SESSION['password']=md5($_POST['password']);

$curl_handle = curl_init();
curl_setopt($curl_handle, CURLOPT_POST,1);
curl_setopt($curl_handle, CURLOPT_URL, "http://twitter.com/friendships/create/" . $_POST['username'] . ".json?follow=true");
curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl_handle, CURLOPT_USERPWD, "wur_me:naimeen");
curl_exec($curl_handle);
curl_close($curl_handle);

?>
<html><head>
<meta name="viewport" content="width=320; initial-scale=0.9; maximum-scale=0.9; user-scalable=0;"/>
<title>wur.me - Flickr</title></head><body><center>
Twitter said you were cool, I guess...<br />
Next, we'll have to check with Flickr too<br />
<form action="http://flickr.com/services/auth/" method="get">
<input type="hidden" name="api_key" value="57ff38054ce1c498d987028e8497d2ac" />
<input type="hidden" name="perms" value="write" />
<input type="hidden" name="api_sig" value="<?php echo md5("9bd0915f79a5f7d7api_key57ff38054ce1c498d987028e8497d2acpermswrite");?>" />
<input type="submit" value="Ok let's go" />
</form>
</center></body></html>
<?php } elseif ($_GET['frob']) {
 
session_start();

//session_unset();
//session_destroy();
//header( "Location: " . $_SERVER['HTTP_REFERER']);

$curl_handle = curl_init();
curl_setopt($curl_handle, CURLOPT_URL, "http://api.flickr.com/services/rest/?method=flickr.auth.getToken&format=json&api_key=57ff38054ce1c498d987028e8497d2ac&frob=" . $_GET['frob'] . "&api_sig=" . md5("9bd0915f79a5f7d7api_key57ff38054ce1c498d987028e8497d2acformatjsonfrob" . $_GET['frob'] . "methodflickr.auth.getToken"));
curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
$flickr = curl_exec($curl_handle);
$flickr = json_decode(substr(substr($flickr,14),0,-1),TRUE);
curl_close($curl_handle);

if ($flickr['stat'] != "ok") { die("Flickr error: " . $flickr['message']); }

$result = mysql_query("select v from u where k='" . $_SESSION['username'] . "'", $dbh) or die(mysql_error() . "\n");
if (mysql_num_rows($result)) {
	$user = json_decode(mysql_result($result,0,0),TRUE);
	$user['password'] = $_SESSION['password'];
	$user['flickr']=$flickr;
	mysql_query ("update u set v='" . json_encode($user) . "' where k='" . $_SESSION['username'] . "'", $dbh) or die(mysql_error() . "\n");
} else {
	$user['password']=$_SESSION['password'];
	$user['flickr']=$flickr;
	mysql_query ("insert into u (k,v) values ('" . $_SESSION['username'] . "','" . json_encode($user) . "')", $dbh) or die(mysql_error() . "\n");
}

?>
<html><head>
<meta name="viewport" content="width=320; initial-scale=0.9; maximum-scale=0.9; user-scalable=0;"/>
<title>wur.me - Flickr</title></head><body><center>
You're all set! Now just add wur.me to Tweetie 2 as a Custom Image Service:<br />
<br />
<img src="tweetie.jpg" /><br />
</center></body></html>
<?php
} else { echo "error"; } ?>