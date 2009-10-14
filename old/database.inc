<?PHP

$dbh=mysql_connect ("localhost", "wurme_wurme", "9d7ebf93a2ee2ffa02083b35c8bb3023") or die (mysql_error() . "\n");
mysql_select_db ("wurme_wurme", $dbh) or die (mysql_error() . "\n");

?>