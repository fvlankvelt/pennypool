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

function check_post_vars($popup) {
	global $_POST;

	if(@$_POST['van']==0 || @$_POST['naar']==0 ||
			$_POST['van']==$_POST['naar']) {
		$popup->set_error(__("Personen zijn gelijk."));
		return false;
	}
	if(@$_POST['datum']=='') {
		$popup->set_error(__("Datum is niet geldig."));
		return false;
	}
	if(!@$_POST['bedrag'] || $_POST['bedrag'] == "0.00") {
		$popup->set_error(__("Bedrag is nul."));
		return false;
	}
	return 1;
}

$popup = new popup_eval(__("Betaling opslaan"), "betaling.php",
						"betaal_overzicht.php");

if(check_post_vars(&$popup)) {
	$info=array();
	foreach(array('van','naar','bedrag') as $key) {
		$info[$key]=addSlashes(@$_POST[$key]);
	}
	$date=split('-',$_POST['datum']);
	$info['datum']=$date[2]."-".$date[1]."-".$date[0];
	/* update */
	switch($_POST['action'])
	{
	case 'delete':
		$res=mysql_query("DELETE FROM ".$db['prefix']."betalingen ".
				"WHERE van=".$info['van']." AND naar=".$info['naar']." ".
				"AND datum='".$info['datum']."'",$db_conn);
		break;
	case 'update':
		$res=mysql_query("UPDATE ".$db['prefix']."betalingen SET ".
				"bedrag='".$info['bedrag']."' WHERE ".
				"van=".$info['van']." AND naar=".$info['naar']." ".
				"AND datum='".$info['datum']."'",$db_conn);
		break;
	case 'insert':
		$query="INSERT INTO ".$db['prefix']."betalingen ".
				"(van,naar,bedrag,datum) VALUES (".
				$info['van'].",".$info['naar'].",'".$info['bedrag']."',".
				"'".$info['datum']."')";
		$res=mysql_query($query,$db_conn);
		if(!@$res || mysql_errno())
		{
			$popup->set_error(__("Er bestaat al een betaling op deze dag."));
			$popup->render_error($_POST);
			exit();
		}
		break;
	}
	mysql_free_result($res);

	$popup->render_ok();
}
else
{
	$popup->render_error($_POST);
}
