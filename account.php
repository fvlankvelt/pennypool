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

if(!@$_POST && !@$_GET) {
	$title=__("Nieuwe rekening");
	$info=array();
} else if (@$_GET['pers_id']) {
	$title=__("Rekening bewerken");
	$pers_id=$_GET['pers_id'];
	$res=mysql_query("SELECT * FROM ".$db['prefix']."mensen ".
					 "WHERE pers_id=".$_GET['pers_id'],$db_conn);
	$info=mysql_fetch_assoc($res);
	mysql_free_result($res);
} else {
	if(@$_POST['pers_id']) {
		$title=__("Rekening bewerken");
		$pers_id=$_POST['pers_id'];
	} else {
		$title=__("Nieuwe rekening");
	}
	$info=$_POST;
}

$form = new form("account_eval.php",(@$pers_id?true:false));

$popup = new popup($title);

$popup->head();
$form->head();

if(@$pers_id) { ?>
<input type=hidden name=pers_id value="<?=$pers_id?>">
<? } ?>
<table align=center>
  <tr>
	<td align=right><label for="nick"><?=__("naam")?>:</label></td>
	<td align=left><input type=text name="nick" size=8 value="<?=@$info['nick']?>"></td>
  </tr>
  <tr>
	<td align=right><label for="rekeningnr"><?=__("rekeningnr")?>:</label></td>
	<td align=left><input type=text name="rekeningnr" size=9 value="<?=@$info['rekeningnr']?>"></td>
  </tr>
</table><br>
<?

$form->foot();
$popup->foot();
