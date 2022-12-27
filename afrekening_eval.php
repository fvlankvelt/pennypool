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
include_once("lib_cal.php");
include_once("lib_layout.php");
include_once("lib_util.php");

/**
 * @var Doctrine\DBAL\Connection $dbh
 */
global $dbh;

function check_post_vars(&$popup) {
	global $_POST;

	if(!@$_POST['date']) {
		$popup->set_error(__("Invalid \"datum\"."));
		return false;
	}

	$date=date_to_dt($_POST['date']);
	if(!$date) {
		$popup->set_error(__("Invalid \"datum\"."));
		return false;
	}
	return 1;
}

function update_betalingen($betalingen, $afr_id)
{
	/**
	 * @var Doctrine\DBAL\Connection $dbh
	 */
	global $dbh;

	if(is_null($betalingen))
		$betalingen = array();
	$dbh->executeStatement("UPDATE betalingen SET afr_id=0 WHERE afr_id=?",
	 	[$afr_id], [ParameterType::INTEGER]);

	if(count($betalingen))
	{
		$query_txt = "";
		$query_params = array();
		$query_types = array();
		foreach($betalingen as $betaling)
		{
			# split in van, naar, datum
			$items = explode(':', $betaling, 3);
			$items[2] = date_to_sql($items[2]);

			if($query_txt)  $query_txt .= " OR ";
			$query_txt .= "(van=? AND naar=? AND datum=?)";
			array_push($query_params, ...$items);
			array_push($query_types, ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING);

		}
		$dbh->executeStatement("UPDATE betalingen SET afr_id=? WHERE {$query_txt}",
			[$afr_id, ...$query_params],
			[ParameterType::INTEGER, ...$query_types]);
	}
}

$popup = new popup_eval(__("Afrekening"), "afrekening.php");

if(check_post_vars($popup)) {
	$date = date_to_dt($_POST['date']);
	if(!@$_POST['afr_id'])
	{
		/* nieuwe afrekening, netjes invoeren */
		$res = $dbh->executeStatement("INSERT INTO afrekeningen (date) VALUES (?)",
			[date_to_sql($_POST['date'])], [ParameterType::STRING]);
		$afr_id = $dbh->lastInsertId();

		if(count($_POST['activiteiten']))
		{
			$act_ids = $_POST['activiteiten'];
			$dbh->executeStatement("UPDATE activiteiten SET afr_id=? WHERE act_id IN (?)",
				[$afr_id, $act_ids], [ParameterType::INTEGER, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
		}

		/* betalingen */
		update_betalingen($_POST['betalingen'], $afr_id);
	}
	else
	{
		$afr_id = $_POST['afr_id'];

		/* bestaande afrekening */
		if($_POST['action'] == 'default')
		{
			/* activiteiten */
			$act_ids = $_POST['activiteiten'];
			$dbh->executeStatement("UPDATE activiteiten SET afr_id=0 WHERE act_id NOT IN (?)",
				[$act_ids], [ParameterType::INTEGER, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);

			if(count($_POST['activiteiten']))
			{
				/* note: we only support the changing of
				 *		 activities with act_id=0
				 */
				$act_ids = $_POST['activiteiten'];
				$dbh->executeStatement("UPDATE activiteiten SET afr_id=? WHERE act_id IN (?)",
					[$afr_id, $act_ids], [ParameterType::INTEGER, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
			}

			/* betalingen */
			update_betalingen($_POST['betalingen'], $afr_id);

			/* afrekening zelf */
			$dbh-> executeStatement("UPDATE afrekeningen SET date=? WHERE afr_id=?",
			  	[dt_to_sql($date)],[ParameterType::STRING]);
		}
		else
		{
			/* 'e'en van de verwijder opties */
			$dbh->executeStatement("DELETE FROM afrekeningen WHERE afr_id=?",
				[$afr_id], [ParameterType::INTEGER]);
			$dbh->executeStatement("UPDATE activiteiten SET afr_id=0 WHERE afr_id=?",
				[$afr_id], [ParameterType::INTEGER]);
			$dbh->executeStatement("UPDATE betalingen SET afr_id=0 WHERE afr_id=?",
			    [$afr_id], [ParameterType::INTEGER]);
		}
	}

	$popup->render_ok();
}
else
{
	$popup->render_error($_POST);
}
