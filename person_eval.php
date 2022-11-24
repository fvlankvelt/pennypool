<?php
/*
	Penny Pool, a utility to share expenses among a group of friends
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


require_once("pennypool.php");
include_once("lib_layout.php");
include_once("lib_util.php");

function check_post_vars($popup) {
	global $_POST;

	if(@$_POST['nick']=='') {
		$popup->set_error(__("Bijnaam (login) is leeg."));
		return false;
	}
	else if(!@$_POST['pers_id'] && @$_POST['password']=='') {
		$popup->set_error(__("Passwoord is leeg."));
		return false;
	}
	else if(@$_POST['passwd2']!=@$_POST['password']) {
		$popup->set_error(__("Passwoorden zijn niet gelijk."));
		return false;
	}
	return true;
}


$popup = new popup_eval("Persoon opslaan", "person.php");

if(check_post_vars(&$popup)) {
	$info=array();
	foreach(array('voornaam','achternaam','nick','email','rekeningnr',
				'password', 'lang') as $key) {
		$info[$key]=addSlashes(@$_POST[$key]);
	}
	if(@$_POST['pers_id'])
	{
		$me=my_data();
		if($_POST['action'] != 'delete')
		{
			/* check for $login=='nick' van pers_id in db,
			   update $login als dit het geval is */
			if($me['pers_id']==$_POST['pers_id'])
			{
				$_SESSION['login']=$info['nick'];
				$_SESSION['lang']=$info['lang'];
			}
			$passwd_hash = $passwd_hash($info['password'],  PASSWORD_ARGON2ID);
			$res=mysql_query("UPDATE ".$db['prefix']."mensen SET ".
						 "voornaam='".$info['voornaam']."',".
						 "achternaam='".$info['achternaam']."',".
						 "nick='".$info['nick']."',".
						 "email='".$info['email']."',".
						 "rekeningnr='".$info['rekeningnr']."',".
						 "lang='".$info['lang']."'".
						 ($info['password']!=''?
						  	",password='$passwd_hash' ":" ").
						 "WHERE pers_id=".$_POST['pers_id'],$db_conn);
		}
		else
		{
			$res=mysql_query("SELECT count(*) ".
							 "FROM ".$db['prefix']."deelnemers ".
							 "WHERE pers_id=".$_POST['pers_id'],$db_conn);
			$row=mysql_fetch_row($res);
			if($row[0] > 0)
			{
					$popup->set_error(__("Persoon is een deelnemer aan activiteiten."));
					$popup->render_error($_POST);
					exit();
			}
			if($me['pers_id']==$_POST['pers_id'])
			{
				$popup->opener = "logout.php";
			}
			$res=mysql_query("DELETE FROM ".$db['prefix']."mensen ".
						"WHERE pers_id=".$_POST['pers_id'],$db_conn);
			$res=mysql_query("DELETE FROM ".$db['prefix']."deelnemers ".
						"WHERE pers_id=".$_POST['pers_id'],$db_conn);
			$res=mysql_query("DELETE FROM ".$db['prefix']."betalingen ".
						"WHERE van=".$_POST['pers_id'].
						  " OR naar=".$_POST['pers_id'],$db_conn);
		}
	}
	else
	{
		$res=mysql_query("SELECT * FROM ".$db['prefix']."mensen ".
						 "WHERE nick='".$info['nick']."'",$db_conn);
		if(mysql_num_rows($res))
		{
			$popup->set_error(__("Bijnaam wordt al gebruikt.  Kies aub een andere naam."));
			$popup->render_error($_POST);
			exit();
		}
		$passwd=addSlashes(crypt($info['password'],randstr(2)));
		$res=mysql_query("INSERT INTO ".$db['prefix']."mensen ".
						 "(voornaam,achternaam,nick,email,rekeningnr,".
						 "password,lang) VALUES ".
						 "('".$info['voornaam']."',".
						 "'".$info['achternaam']."',".
						 "'".$info['nick']."',".
						 "'".$info['email']."',".
						 "'".$info['rekeningnr']."',".
						 "'".$passwd."','".$info['lang']."')",$db_conn);
	}

	$popup->render_ok();

} else {

	$popup->render_error($_POST);
}
