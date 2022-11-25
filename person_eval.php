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

require_once 'vendor/autoload.php';
use \Doctrine\DBAL\ParameterType;


require_once("pennypool.php");
include_once("lib_layout.php");
include_once("lib_util.php");

/**
 * @var Doctrine\DBAL\Connection $dbh
 */
global $dbh, $pp;

function check_post_vars(popup_eval $popup): bool
{
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

if(check_post_vars($popup)) {
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
			$sql_passwd = "";
			if ($info['password']!='') {
				$info['passwd_hash'] = password_hash($info['password'],  PASSWORD_ARGON2ID);
				$sql_passwd = ",password=:passwd_hash";
			}

			$sql = "
				UPDATE mensen
				SET	voornaam=:voornaam,
				 	achternaam=:achternaam,
					nick=:nick,
					email=:email,
					rekeningnr=:rekeningnr,
					lang=:lang
					$sql_passwd
				WHERE pers_id=:id
			";
			$stm = $dbh->prepare($sql);
			$stm->bindValue('id', $me['pers_id']);
			foreach ($info as $k => $v) {
				if ($k!="password") {
					$stm->bindValue($k, $v);
				}
			}
			$cnt=$stm->executeStatement();
			if ($cnt===1) {
				/* check for $login=='nick' van pers_id in db,
				   update $login als dit het geval is */
				if($me['pers_id']==$_POST['pers_id'])
				{
					$_SESSION['login']=$info['nick'];
					$_SESSION['lang']=$info['lang'];
				}
			}
			else
			{
				error("updating", "num rows changed: $cnt");
			}
		}
		else
		{
			$cnt=$dbh->executeQuery("SELECT count(*) FROM deelnemers WHERE pers_id=?",
				[$_POST['pers_id']], [ParameterType::INTEGER])->fetchOne();

			if($cnt > 0)
			{
					$popup->set_error(__("Persoon is een deelnemer aan activiteiten."));
					$popup->render_error($_POST);
					exit();
			}
			if($me['pers_id']==$_POST['pers_id'])
			{
				$popup->opener = "logout.php";
			}

			$dbh->executeStatement("DELETE FROM mensen     WHERE pers_id=?",
				[$_POST['pers_id']], [ParameterType::INTEGER]);
			$dbh->executeStatement("DELETE FROM deelnemers WHERE pers_id=?",
				[$_POST['pers_id']], [ParameterType::INTEGER]);
			$dbh->executeStatement("DELETE FROM betalingen WHERE van=? OR naar=?",
				[$_POST['pers_id'],$_POST['pers_id']],
				[ParameterType::INTEGER, ParameterType::INTEGER]);
		}
	}
	else
	{
		$cnt=$dbh->executeQuery("SELECT count(*) FROM mensen WHERE nick=?",
			[$info['nick']], [ParameterType::STRING])->fetchOne();
		if($cnt)
		{
			$popup->set_error(__("Bijnaam wordt al gebruikt.  Kies aub een andere naam."));
			$popup->render_error($_POST);
			exit();
		}

		$info['passwd_hash'] = password_hash($info['password'],  PASSWORD_ARGON2ID);
		$sql = "
			INSERT INTO mensen (voornaam,achternaam,nick,email,rekeningnr,password,lang)
			VALUES (:voornaam,:achternaam,:nick,:email,:rekeningnr,:passwd_hash,:lang)
		";
		$stm = $dbh->prepare($sql);
		foreach ($info as $k => $v) {
			if ($k!="password") {
				$stm->bindValue($k, $v, ParameterType::STRING);
			}
		}

		$cnt=$stm->executeStatement();
	}

	$popup->render_ok();

} else {

	$popup->render_error($_POST);
}
