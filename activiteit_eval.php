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

function check_post_vars($popup) {
	/** @var people $people */
	global $people;

	if(@$_POST['name']=='')
	{
		$popup->set_error(__("Geen naam"));
		return 0;
	}


	if(!isset($_POST['date']) or date_to_sql($_POST['date'])===false)
	{
		$popup->set_error(__("Datum is ongeldig"));
		return 0;
	}
	if($_POST['action'] != 'delete')
	{
		$found_person = false;
		$ids = explode(',', $_POST['ids']);
		$nicks = $people->nick($ids);
		for($i = 0; $i < count($ids); $i++)
		{
			$nick = $people->find($ids[$i]);
			if(@$_POST['credit_'.$i]=='yes' &&
					$nick['type'] == 'person')
			{
					$found_person = true;
					break;
			}
		}
		if(!$found_person)
		{
			$popup->set_error(__("Geen deelnemers geselecteerd"));
			return 0;
		}
	}
	return 1;
}

$popup = new popup_eval(__("Activiteit eval"), "activiteit.php");

if(check_post_vars($popup))
{
	$date_sql = date_to_sql($_POST['date']);

	$insert_deelnemers=true;
	if(!@$_POST['act_id'])
	{
		$dbh->executeStatement("INSERT INTO activiteiten (name,date) VALUES (?,?)",
			[$_POST['name'], $date_sql], [ParameterType::STRING, ParameterType::STRING]);
		$act_id=$dbh->lastInsertId();
	}
	else
	{
		$act_id=$_POST['act_id'];
		if($_POST['action'] != 'delete')
		{
			$dbh->executeStatement("UPDATE activiteiten SET name=?, date=? WHERE act_id=?",
				[$_POST['name'],$date_sql,$act_id], [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER]);

			$dbh->executeStatement("DELETE FROM deelnemers WHERE act_id=:id",
				[$act_id], [ParameterType::INTEGER]);
		}
		else
		{
			$dbh->executeStatement("DELETE FROM activiteiten WHERE act_id=?",
				[$act_id], [ParameterType::INTEGER]);
			$dbh->executeStatement("DELETE FROM deelnemers WHERE act_id=?",
				[$act_id], [ParameterType::INTEGER]);
		}
	}
	if(!@$_POST['act_id'] || $_POST['action'] != 'delete')
	{
		$ids=explode(',', $_POST['ids']);
		for($i=0;$i<count($ids);$i++)
		{
			if(@$_POST['credit_'.$i]=='yes')
			{
				$credit = array_key_exists("id_{$i}_credit", $_POST) ? $_POST["id_{$i}_credit"] : 0;
				$mult   = array_key_exists("id_{$i}_mult",   $_POST) ? $_POST["id_{$i}_mult"  ] : 0;

				$dbh->executeStatement("INSERT INTO deelnemers (act_id, pers_id, credit, aantal) VALUES (?,?,?,?)",
					[$act_id,$ids[$i],$credit,$mult],
					[ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER]
				);
			}
		}
	}

	$popup->render_ok();
}
else
{
	$popup->render_error($_POST);
}
