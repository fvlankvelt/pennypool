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


/* shouldn't be necessary */
require_once("pennypool.php");
include_once("lib_layout.php");

function check_post_vars($popup) {
	global $_POST;

	if(!@$_POST['date']) {
		$popup->set_error(__("Invalid \"datum\"."));
		return false;
	}

	$date=split('-',$_POST['date']);
	if(count($date)!=3) {
		$popup->set_error(__("Invalid \"datum\"."));
		return false;
	}
	return 1;
}

function update_betalingen($betalingen, $afr_id)
{
	global $db, $db_conn;

	if(is_null($betalingen))
		$betalingen = array();
	$res = mysql_query("UPDATE ".$db['prefix']."betalingen ".
					   "SET afr_id=0 WHERE afr_id=$afr_id",
					   $db_conn);
	if(count($betalingen))
	{
		$query_txt = "";
		foreach($betalingen as $betaling)
		{
			$item = split(':', $betaling);
			if($query_txt)
				$query_txt .= " OR ";
			$query_txt .= "(van=".$item[0]." ".
						  "AND naar=".$item[1]." ".
						  "AND datum='".$item[2]."') ";
		}
		$res = mysql_query("UPDATE ".$db['prefix']."betalingen ".
						   "SET afr_id=$afr_id ".
						   "WHERE ".$query_txt, $db_conn);
	}
}

$popup = new popup_eval(__("Afrekening"), "afrekening.php");

if(check_post_vars(&$popup)) {
	$date=split('-',$_POST['date']);
	if(!@$_POST['afr_id'])
	{
		/* nieuwe afrekening, netjes invoeren */
		$res = mysql_query("INSERT INTO ".$db['prefix']."afrekeningen ".
						   "(date) VALUES ('".
							$date[2]."-".$date[1]."-".$date[0]."')",
							$db_conn);
		$afr_id = mysql_insert_id();

		if(count($_POST['activiteiten']))
		{
			$txt = implode(" OR act_id=", $_POST['activiteiten']);
			$res = mysql_query("UPDATE ".$db['prefix']."activiteiten ".
						   "SET afr_id=$afr_id ".
						   "WHERE act_id=$txt", $db_conn);
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
			$txt = implode(" AND act_id!=", $_POST['activiteiten']);
			$res = mysql_query("UPDATE ".$db['prefix']."activiteiten ".
							   "SET afr_id=0 WHERE afr_id=$afr_id ".
							   ($txt?"AND act_id!=".$txt:""), $db_conn);

			if(count($_POST['activiteiten']))
			{
				/* note: we only support the changing of
				 *		 activities with act_id=0
				 */
				$txt = implode(" OR act_id=", $_POST['activiteiten']);
				$res = mysql_query("UPDATE ".$db['prefix']."activiteiten ".
								   "SET afr_id=$afr_id ".
								   "WHERE act_id=".$txt, $db_conn);
			}

			/* betalingen */
			update_betalingen($_POST['betalingen'], $afr_id);

			/* afrekening zelf */
			$res = mysql_query("UPDATE ".$db['prefix']."afrekeningen ".
							   "SET date='".$date[2]."-".$date[1].
							   			  "-".$date[0]."' ".
							   "WHERE afr_id=$afr_id", $db_conn);
		}
		else
		{
			/* 'e'en van de verwijder opties */
			$res = mysql_query("DELETE FROM ".$db['prefix']."afrekeningen ".
							   "WHERE afr_id=$afr_id", $db_conn);
			$res = mysql_query("UPDATE ".$db['prefix']."activiteiten ".
							   "SET afr_id=0 WHERE afr_id=$afr_id",
							   $db_conn);
			$res = mysql_query("UPDATE ".$db['prefix']."betalingen ".
							   "SET afr_id=0 WHERE afr_id=$afr_id",
							   $db_conn);
		}
	}

	$popup->render_ok();
}
else
{
	$popup->render_error($_POST);
}
