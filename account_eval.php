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
global $dbh;

$popup = new popup_eval(__("Rekening opslaan"), "account.php");

if(@$_POST['nick'] != '')
{
	$info=array();
	foreach(array('nick','rekeningnr') as $key)
	{
		$info[$key]=addSlashes(@$_POST[$key]);
	}

	if(isset($_POST['pers_id']))
	{
		$pers_id = $_POST['pers_id'];

		if($_POST['action'] != 'delete')
		{
			$dbh->executeStatement(" UPDATE mensen SET nick=?, rekeningnr=?, type='rekening' WHERE pers_id=?",
				[$info['nick'], $info['rekeningnr'], $pers_id],
				[ParameterType::STRING,ParameterType::STRING,ParameterType::INTEGER]
			);
		}
		else
		{
			$cnt = $dbh->executeQuery("SELECT COUNT(*) FROM deelnemers WHERE pers_id=?",
				[$pers_id],
				[ParameterType::INTEGER]
			)->fetchOne();
			if($cnt > 0)
			{
					$popup->set_error(__("Rekening wordt gebruikt bij activiteiten."));
					$popup->render_error($_POST);
					exit();
			}

			$dbh->executeStatement("DELETE FROM mensen WHERE pers_id=?",
				[$pers_id], [ParameterType::INTEGER]);
			$dbh->executeStatement("DELETE FROM deelnemers WHERE pers_id=?",
				[$pers_id], [ParameterType::INTEGER]);
			$dbh->executeStatement("DELETE FROM betalingen WHERE van=? OR naar=?",
				[$pers_id,$pers_id], [ParameterType::INTEGER,ParameterType::INTEGER]);
		}
	}
	else
	{
		$res = $dbh->executeStatement("INSERT INTO mensen (nick,rekeningnr,type) VALUES (?,?,'rekening')",
			[$info['nick'], $info['rekeningnr']], [ParameterType::STRING, ParameterType::STRING]);
	}

	$popup->render_ok();
}
else
{
	$popup->render_error($_POST);
}
