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
	global $people;

	if(@$_POST['name']=='' || @$_POST['date']=='')
	{
		$popup->set_error(__("Geen naam en/of datum"));
		return 0;
	}

	$date=split('-',$_POST['date']);
	if(count($date)!=3)
	{
		$popup->set_error(__("Datum is ongeldig"));
		return 0;
	}
	if($_POST['action'] != 'delete')
	{
		$found_person = false;
		$ids = split(',',$_POST['ids']);
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

if(check_post_vars(&$popup))
{
	$date=split('-',$_POST['date']);
	$insert_deelnemers=true;
	if(!@$_POST['act_id'])
	{
		$res=mysql_query("INSERT INTO ".$db['prefix']."activiteiten (name,date) ".
				 	"VALUES ('".addSlashes($_POST['name'])."','".
				 		$date[2]."-".$date[1]."-".$date[0]."')",$db_conn);
		$act_id=mysql_insert_id();
	}
	else
	{
		$act_id=$_POST['act_id'];
		if($_POST['action'] != 'delete')
		{
			$res=mysql_query("UPDATE ".$db['prefix']."activiteiten ".
						 "SET name='".addSlashes($_POST['name'])."', ".
						 "date='".$date[2]."-".$date[1]."-".$date[0]."' ".
						 "WHERE act_id=".$act_id,
						 $db_conn);
			$res=mysql_query("DELETE FROM ".$db['prefix']."deelnemers ".
						 "WHERE act_id=".$act_id,$db_conn);
		}
		else
		{
			$res=mysql_query("DELETE FROM ".$db['prefix']."activiteiten ".
						"WHERE act_id=".$act_id,$db_conn);
			$res=mysql_query("DELETE FROM ".$db['prefix']."deelnemers ".
						"WHERE act_id=".$act_id,$db_conn);
		}
	}
	if(!@$_POST['act_id'] || $_POST['action'] != 'delete')
	{
		$ids=split(',',$_POST['ids']);
		for($i=0;$i<count($ids);$i++)
		{
			if(@$_POST['credit_'.$i]=='yes')
			{
				$mult = 0;
				if(isset($_POST["id_{$i}_mult"]))
					$mult = addSlashes($_POST["id_{$i}_mult"]);
				$res = mysql_query("INSERT INTO ".$db['prefix']."deelnemers ".
						 	"(act_id,pers_id,credit,aantal) VALUES ".
						 	"($act_id,{$ids[$i]},'".
						 	addSlashes($_POST["id_{$i}_credit"])."',".
							$mult.")",
						 	$db_conn);
			}
		}
	}

	$popup->render_ok();
}
else
{ 
	$popup->render_error($_POST);
}
