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

$popup = new popup_eval(__("Rekening opslaan"), "account.php");

if(@$_POST['nick'] != '')
{
	$info=array();
	foreach(array('nick','rekeningnr') as $key)
	{
		$info[$key]=addSlashes(@$_POST[$key]);
	}

	if(@$_POST['pers_id'])
	{
		if($_POST['action'] != 'delete')
		{
			$passwd=addSlashes(crypt($info['password'],randstr(2)));
			$res=mysql_query("UPDATE ".$db['prefix']."mensen SET ".
						 "nick='".$info['nick']."',".
						 "rekeningnr='".$info['rekeningnr']."',".
						 "type='rekening' ".
						 "WHERE pers_id=".$_POST['pers_id'],$db_conn);
		}
		else
		{
			$res=mysql_query("SELECT count(*) ".
							 "FROM ".$db['prefix']."deelnemers ".
							 "WHERE pers_id='".$_POST['pers_id']."'",$db_conn);
			$row=mysql_fetch_row($res);
			if($row[0] > 0)
			{
					$popup->set_error(__("Rekening wordt gebruikt bij activiteiten."));
					$popup->render_error($_POST);
					exit();
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
		$passwd=addSlashes(crypt($info['password'],randstr(2)));
		$res=mysql_query("INSERT INTO ".$db['prefix']."mensen ".
						 "(nick,rekeningnr,type) VALUES ".
						 "('".$info['nick']."',".
						 "'".$info['rekeningnr']."',".
						 "'rekening')", $db_conn);
	}

	$popup->render_ok();
}
else
{ 
	$popup->render_error($_POST);
}
