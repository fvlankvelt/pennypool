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
include_once("lib_cal.php");
include_once("lib_layout.php");

$me=my_data();

$update=false;
if(!@$_POST && !@$_GET) {
	$title=__("Nieuwe betaling");
	$info=array();
	$info['van']=$me['pers_id'];
	$info['naar']=$me['pers_id'];
} else if(@$_POST['action'] == 'insert') { /* back from eval */
	$title=__("Nieuwe betaling");
	$info=$_POST;
} else {
	$title=__("Betaling bewerken");
	$update=true;
	if(@$_POST) { /* back from eval */
		$info=$_POST;
	} else { /* aangeroepen met alleen keys */
		$info=$_GET;
		$date=split('-',$info['datum']);
		$res=mysql_query("SELECT * FROM ".$db['prefix']."betalingen ".
				"WHERE van=".$info['van']." AND naar=".$info['naar']." ".
				"AND datum='".$date[2]."-".$date[1]."-".$date[0]."' LIMIT 1",
				$db_conn);
		$row=mysql_fetch_assoc($res);
		$info['bedrag']=$row['bedrag'];
		mysql_free_result($res);
	}
}

$res=mysql_query("SELECT pers_id,nick FROM ".$db['prefix']."mensen WHERE 1 ".
			"ORDER BY nick ASC LIMIT 20", $db_conn);
$people=array();
while($row=mysql_fetch_assoc($res)) {
	$people[$row['pers_id']]=$row['nick'];
}
mysql_free_result($res);

$cal=new calendar("datum",@$info['datum']);
$form = new form("betaling_eval.php", (@$update?true:false));

?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<link rel=stylesheet title="Penny Pool" href="style.css">
<title><?=$title?></title>
<?php $cal->render_js(); ?>
<script language="JavaScript">
function check_amount() {
  var elm=document.getElementById('bedrag')
  var amount=elm.value
  var s=amount.split(/\./)
  if(s.length==1) {
    s=amount.split(/,/)
  }
  if(s.length==1) {
    s[1]='00'
  } else {
    if(s[1].length==1) {
      s[1]=s[1]+'0'
    } else if(s[1].length>2) {
      s[1]=s[1].substr(0,2)
    }
  }
  if(isNaN(parseInt(s[0])) || isNaN(parseInt(s[1]))) {
    s=new Array("0","00")
  }
  elm.value=parseInt(s[0])+'\.'+s[1]
}
function init() {
<?php $cal->render_js_init(); ?>
}
</script>
</head><body onload="init()">
<h1 align=center><?=$title?></h1><?php
	$form->head();
?>
<table align=center>
  <tr>
    <td align=right><label for="bedrag"><?=__("bedrag")?>:</label></td>
    <td align=left>
      <input type=text size=8 name="bedrag" id="bedrag" class="credit"
          value="<?php echo @$info['bedrag']?$info['bedrag']:"0.00"; ?>" onchange="check_amount()">
    </td>
  </tr>
  <tr>
    <td align=right><label for="datum"><?=__("datum")?>:</label></td>
    <td align=left valign=top>
<?php  $cal->render_html();  ?>
    </td>
  </tr>
  <tr>
    <td align=right><label for="van"><?=__("van")?>:</label></td>
    <td align=left>
      <select name="van">
<?php
	foreach($people as $id=>$nick) {
		if(@$info['van']==$id)
			echo "        <option value=\"$id\" selected>$nick</option>\n";
		else
			echo "        <option value=\"$id\">$nick</option>\n";
	}	?>
      </select>
    </td>
  </tr>
  <tr>
    <td align=right><label for="naar"><?=__("aan")?>:</label></td>
    <td align=left>
      <select name="naar">
<?php
	foreach($people as $id=>$nick) {
		if(@$info['naar']==$id)
			echo "        <option value=\"$id\" selected>$nick</option>\n";
		else
			echo "        <option value=\"$id\">$nick</option>\n";
	}	?>
      </select>
    </td>
  </tr>
</table><br>
<?php
	$form->foot();
?>
</body></html>
