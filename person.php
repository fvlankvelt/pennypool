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
	$title=__("Nieuw persoon");
	$info=array();
} else if (@$_GET['pers_id']) {
	$title=__("Persoon bewerken");
	$pers_id=$_GET['pers_id'];
	$res=mysql_query("SELECT * FROM ".$db['prefix']."mensen ".
					 "WHERE pers_id=".$_GET['pers_id'],$db_conn);
	$info=mysql_fetch_assoc($res);
	$info['passwd2']=$info['password']="";
	if(!@$info['lang'])
		$info['lang']=@$pp['lang'];
	mysql_free_result($res);
} else {
	if(@$_POST['pers_id']) {
		$title=__("Persoon bewerken");
		$pers_id=$_POST['pers_id'];
	} else {
		$title=__("Nieuw persoon");
	}
	$info=$_POST;
}

$form = new form("person_eval.php", (@$pers_id?true:false));
$popup = new popup($title);

$popup->head();
$form->head();

if(@$pers_id) { ?>
<input type=hidden name=pers_id value="<?php=$pers_id?>">
<?php } ?><table align=center>
  <tr>
    <td align=right><label for="voornaam"><?php=__("voornaam")?>:</label></td>
    <td align=left><input type=text name="voornaam" size=15 value="<?php=@$info['voornaam']?>"></td>
  </tr>
  <tr>
    <td align=right><label for="achternaam"><?php=__("achternaam")?>:</label></td>
    <td align=left><input type=text name="achternaam" size=25 value="<?php=@$info['achternaam']?>"></td>
  </tr>
  <tr>
    <td align=right><label for="email"><?php=__("email")?>:</label></td>
    <td align=left><input type=text name="email" size=25 value="<?php=@$info['email']?>"></td>
  </tr>
  <tr>
    <td align=right><label for="rekeningnr"><?php=__("rekeningnr")?>:</label></td>
    <td align=left><input type=text name="rekeningnr" size=9 value="<?php=@$info['rekeningnr']?>"></td>
  </tr>
  <tr>
    <td align=right><label for="nick"><?php=__("nickname/login")?>:</label></td>
    <td align=left><input type=text name="nick" size=8 value="<?php=@$info['nick']?>"></td>
  </tr>
  <tr>
    <td align=right><label for="password"><?php=__("passwd")?><font color=red>*</font>:</label></td>
    <td align=left><input type=password name="password" size=8 value="<?php=@$info['password']?>"></td>
  </tr>
  <tr>
    <td align=right><label for="passwd2"><?php=__("re-type")?>:</label></td>
    <td align=left><input type=password name="passwd2" size=8 value="<?php=@$info['passwd2']?>"></td>
  </tr>
  <tr>
    <td align=right><label for="lang"><?php=__("taal")?>:</label></td>
    <td align=left><select name="lang" id="lang">
<?php
	foreach(get_languages() as $lng) {
		echo "      <option value=\"$lng\"";
		if($lng == @$info['lang'])
			echo " selected";
		echo ">$lng</option>\n";
	}
?>
    </select></td>
  </tr>
<tr><td colspan=2 style="font-size: x-small;"><font
color=red>*</font> <?php=__("als dit veld leeg is, blijft het paswoord hetzelfde")?></td></tr>
</table><br>
<?php

$form->foot();
$popup->foot();
