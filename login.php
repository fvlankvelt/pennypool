<?php
/*
	Huisrekening, a utility to share expenses among a group of friends
    Copyright (C) 2003-2005  Frank van Lankvelt <frnk@a-eskwadraat.nl>
*/

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// select default language
if(isset($pp['lang']))
	include_once("lang/".$pp['lang'].".php");

function __($txt)
{
	global $lang_data;
	if(@$lang_data[$txt])
		return $lang_data[$txt];
	return $txt;
}

if(!file_exists('config.php')) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: setup.php");
} else if(!is_readable('config.php')) {
		?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<link rel=stylesheet title="Penny Pool" href="style.css">
</head><body>
<center><div style="width: 50%; border: 1pt solid black; padding: 8pt; text-align: left;">
<h3 style="color: red;">Configuration Error</h3>
The configuration file, <code>config.php</code>, is present but 
it is not readable by the webserver.
</div></center>
</body></html><?

exit();

} else if(@$_POST['login']) {
	$login=addSlashes(@$_POST['login']);
	$passwd=addSlashes(@$_POST['passwd']);

	include_once('config.php');

	$conn=mysql_pconnect($db['host'],$db['user'],$db['passwd']);
	mysql_select_db($db['db'],$conn);

	$res=mysql_query("SELECT * from ".$db['prefix']."mensen WHERE ".
			"nick='$login' and password=encrypt('$passwd',".
				"substring(password,1,2)) LIMIT 1",$conn);
	if($res && mysql_num_rows($res)==1) {
		$row=mysql_fetch_assoc($res);
		unset($passwd);
		session_start();
		// $login variable could be overridden by session,
		// when register_globals is on in php.ini
		$_SESSION['login']=addSlashes($_POST['login']);
		$_SESSION['lang']=@$row['lang'];

		session_write_close();
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: index.php");
		exit();
	} else {
		sleep(1);
		$passwd="";
	}
	if($res)
		mysql_free_result($res);
} else if(@$_GET['login']) {
	$login=$_GET['login'];
	$passwd="";
} else {
	$login="";
	$passwd="";
} ?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<title><?=__("Huisrekening Login")?></title>
<link rel=stylesheet title="Penny Pool" href="style.css">
</head><body onload="document.getElementById('login').focus()">
<h1 align=center><?=__("Huisrekening Login")?></h1>
<form method=post action="login.php">
<table align=center>
  <tr>
	<th align=right><label for=login><?=__("login")?>:</label></th>
	<td><input type=text size=8 id='login' name=login value="<?=$login?>"></td>
  </tr>
  <tr>
	<th align=right><label for=password><?=__("passwd")?>:</label></th>
	<td><input type=password size=8 id='password' name=passwd value=""></td>
  </tr>
</table><br>
<center><input type=submit value="login" onmouseover="this.setAttribute('class','hover')" 
	onfocus="this.setAttribute('class','hover')" onmouseout="this.removeAttribute('class')"
	onblur="this.removeAttribute('class')"></center>
</form>
</body>
</html>
