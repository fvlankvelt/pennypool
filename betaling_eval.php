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


function check_post_vars($popup) {
	global $_POST;

	if(@$_POST['van']==0 || @$_POST['naar']==0 ||
			$_POST['van']==$_POST['naar']) {
		$popup->set_error(__("Personen zijn gelijk."));
		return false;
	}
	if(!isset($_POST['datum']) or date_to_sql($_POST['datum'])===false)
	{
		$popup->set_error(__("Datum is ongeldig"));
		return 0;
	}
	if(!@$_POST['bedrag'] || $_POST['bedrag'] == "0.00") {
		$popup->set_error(__("Bedrag is nul."));
		return false;
	}
	return 1;
}

$popup = new popup_eval(__("Betaling opslaan"), "betaling.php",
						"betaal_overzicht.php");

if(check_post_vars($popup)) {
	$info=array();
	foreach(array('van','naar','bedrag') as $key) {
		$info[$key]=addSlashes(@$_POST[$key]);
	}
	$info['datum'] = date_to_sql($_POST['datum']);
	/* update */
	switch($_POST['action'])
	{
	case 'delete':
		$cnt = $dbh->executeStatement("DELETE FROM betalingen WHERE van=? AND naar=? AND datum=?",
			[$info['van'], $info['naar'], $info['datum']],
			[ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING]
		);
		break;
	case 'update':
		/* TODO: bug: updating won't work very well, because the theings you are updating are also primary keys
		 *            so changing "var" or "naar" or "datum" will create a new bealting instead of updating the old one */
		$cnt = $dbh->executeStatement("UPDATE betalingen SET bedrag=? WHERE van=? AND naar=? AND datum=?",
			[$info['bedrag'],$info['van'], $info['naar'], $info['datum']],
			[ParameterType::STRING, ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING]
		);
		break;
	case 'insert':
		try {
			$cnt = $dbh->executeStatement("INSERT INTO betalingen (bedrag,van,naar,datum) VALUES (?,?,?,?)",
				[$info['bedrag'],$info['van'], $info['naar'], $info['datum']],
				[ParameterType::STRING, ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING]
			);
		} catch (\Doctrine\DBAL\Exception $e) {
			$popup->set_error(__("Er bestaat al een betaling op deze dag."));
			$popup->render_error($_POST);
			exit();
		}
		break;
	}

	$popup->render_ok();
}
else
{
	$popup->render_error($_POST);
}
