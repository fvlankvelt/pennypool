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

function display_datum($date)
{
	list($year, $month, $day) = split('-', $date);
	return $day."-".$month;
}

if(!@$_POST && !@$_GET) {
	$title=__("Nieuwe afrekening");
	$info=array();
	$date=date('d-n-Y');
} else if (@$_GET['afr_id']) {
	$title=__("Afrekening bewerken");
	$afr_id=$_GET['afr_id'];

	$res=mysql_query("SELECT * FROM ".$db['prefix']."afrekeningen ".
					 "WHERE afr_id=".$afr_id, $db_conn);
	$info=mysql_fetch_assoc($res);
	mysql_free_result($res);

	if($info['date'] != '0000-00-00')
	{
		$tmpdate=split('-',$info['date']);
		$date=$tmpdate[2]."-".$tmpdate[1]."-".$tmpdate[0];
	} else {
		$date=date('d-n-Y');
	}
	$info['date'] = $date;

	$res = mysql_query("SELECT * FROM ".$db['prefix']."betalingen ".
					   "WHERE afr_id=$afr_id", $db_conn);
	$info['betalingen'] = array();
	while($row = mysql_fetch_assoc($res))
		$info['betalingen'][] = $row['van'].":".$row['naar'].":".$row['datum'];

	$res=mysql_query("SELECT act_id FROM ".$db['prefix']."activiteiten ".
					 "WHERE afr_id=".$afr_id, $db_conn);
	$info['activiteiten']=array();
	while($row = mysql_fetch_assoc($res))
		$info['activiteiten'][] = $row['act_id'];
	mysql_free_result($res);
} else {
	if(@$_POST['afr_id']) {
		$title=__("Afrekening bewerken");
		$afr_id=$_POST['afr_id'];
	} else {
		$title=__("Nieuwe afrekening");
	}
	$info=$_POST;
	$date=$_POST['date'];
}

/* activiteiten */
$all_acts = array();
$act_reverse = array();
$res = mysql_query("SELECT * FROM ".$db['prefix']."activiteiten AS act ".
				   "WHERE afr_id=0 ".
				   (@$afr_id?"OR afr_id=$afr_id ":"").
				   "ORDER BY act.date DESC, act.act_id DESC", $db_conn);
$i = 0;
while($row = mysql_fetch_assoc($res))
{
	$act_reverse[$row['act_id']] = $i;
	$all_acts[$i] = $row;
	$i++;
}
mysql_free_result($res);

/* personen */
$persons = array();
$pers_reverse = array();
$res = mysql_query("SELECT * FROM ".$db['prefix']."mensen ".
				   "ORDER BY nick ASC", $db_conn);
$i=0;
while($row = mysql_fetch_assoc($res))
{
	$pers_reverse[$row['pers_id']] = $i;
	$persons[$i] = $row;
	$i++;
}
mysql_free_result($res);

/* deelnemers aan activiteiten */
$deelnemers = array();
$res = mysql_query("SELECT deeln.* ".
				   "FROM ".$db['prefix']."deelnemers AS deeln, ".
						   $db['prefix']."activiteiten AS act ".
				   "WHERE deeln.act_id = act.act_id ".
						"AND (act.afr_id=0 ".
							  (@$afr_id?"OR afr_id=$afr_id ":"").") ".
				   "ORDER BY act.date DESC, act.act_id DESC", $db_conn);
$act_id = 0;
$cnt = 0;
$mult = 0;
$n_acts = -1;
$total = 0;
while($row = mysql_fetch_assoc($res))
{
	if($row['act_id'] != $act_id)
	{
		if($act_id && $mult)
		{
//			echo "act_id: ".$row['act_id']."<br>\n";
			$avg = $total / $mult;
			while($cnt)
			{
				$cnt--;
				$index = $deelnemers[$n_acts][$cnt]['id'];
				if($persons[$index]['type'] == 'person')
				{
					$deelnemers[$n_acts][$cnt]['credit'] -=
						$deelnemers[$n_acts][$cnt]['aantal'] * $avg;
				}
			}
		}
		$total = 0.0;
		$mult = 0;
		$n_acts++;
		$act_id = $row['act_id'];
	}
	$deelnemers[$n_acts][$cnt] = $row;
	$deelnemers[$n_acts][$cnt]['id'] = $pers_reverse[$row['pers_id']];
	if($persons[$pers_reverse[$row['pers_id']]]['type'] == 'person')
		$mult+=$row['aantal'];
	$cnt++;
	$total += $row['credit'];
}
if($act_id && $mult)
{
	$avg = $total / $mult;
	while($cnt)
	{
		$cnt--;
		$index = $deelnemers[$n_acts][$cnt]['id'];
		if($persons[$index]['type'] == 'person')
		{
			$deelnemers[$n_acts][$cnt]['credit'] -=
						$deelnemers[$n_acts][$cnt]['aantal'] * $avg;
		}
	}
}
$n_acts++;
mysql_free_result($res);

/* betalingen */
$res = mysql_query("SELECT * ".
				   "FROM ".$db['prefix']."betalingen ".
				   "WHERE afr_id=0 ".
						  (@$afr_id?"OR afr_id=$afr_id ":"").
				   "ORDER BY datum DESC", $db_conn);
$betalingen = array();
while($row = mysql_fetch_assoc($res))
{
	$row['checked'] = (@$afr_id && $row['afr_id'] == $afr_id);
	$betalingen[] = $row;
}
mysql_free_result($res);

$cal = new calendar("date", $date);
$form = new form("afrekening_eval.php", (@$afr_id?true:false));

?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<link rel=stylesheet title="Penny Pool" href="style.css">
<title><?=$title?></title>
<?php
	$cal->render_js();
?>
<script language="JavaScript">
deelnemers=new Array()
<?php
	foreach($all_acts as $act_id => $act)
	{	?>
deelnemers[<?=$act_id?>]=new Array()
<?php	}
	foreach($deelnemers as $act_id => $participants)
	{
		foreach($participants as $deeln)
		{	?>
deelnemers[<?=$act_id?>][<?=$deeln['id']?>]=<?=$deeln['credit']."\n"?>
<?php		}
	}

/* Persons */
echo "number_of_persons=".count($persons)."\n";

/* Betalingen */
echo "number_of_betalingen=".count($betalingen)."\n";
echo "betalingen=new Array()\n";
foreach($betalingen as $id => $item)
{
	echo "betalingen[$id]=new Array(".
		$pers_reverse[$item['van']].",".
		$pers_reverse[$item['naar']].",".$item['bedrag'].")\n";
}

/* Activiteiten */
echo "number_of_activities=".count($all_acts)."\n";

?>

function update_total() {
  var total = new Array()

  for(var i = 0; i < number_of_persons; i++) {
    total[i] = 0.0
  }
  for(var i = 0; i < number_of_activities; i++) {
    var item = document.getElementById('act_' + i)

    if(!item || !item.checked)
      continue

    for(var j = 0; j < number_of_persons; j++) {
      if(deelnemers[i][j])
        total[j] += deelnemers[i][j]
    }
  }
  for(var i = 0; i < number_of_betalingen; i++) {
    var item = document.getElementById('betid_' + i)

    if(!item || !item.checked)
      continue

    var data = betalingen[i]

    total[data[0]] += data[2]
    total[data[1]] -= data[2]
  }
  for(var i = 0; i < number_of_persons; i++) {
    var debet = 100 * total[i];

    debet = '' + Math.round(debet) / 100;

    var s = debet.split(/\./)

    if(s.length == 1) {
      s[1]='00'
    } else {
      if(s[1].length == 1) {
        s[1] = s[1] + '0'
      } else if(s[1].length > 2) {
        s[1] = s[1].substr(0,2)
      }
    }
    str = s[0] + '\.' + s[1]

    document.getElementById('pers_'+i+'_debet').value=str
  }
}

active_row=function(id) {
  this.chk=document.getElementById(id)
  this.tr=this.chk.parentNode.parentNode

  if(this.chk.defaultChecked) {
    this.enabled=true
    this.chk.checked=true
    this.tr.style.backgroundColor='#ffffff'
  } else {
    this.enabled=false
    this.chk.checked=false
    this.tr.style.backgroundColor='transparent'
  }

  this.chk.obj=this.tr.obj=this

  this.chk.onfocus=this.tr.onmouseover=function(e) {
    this.obj.tr.style.backgroundColor='#cccccc'
  }
  this.chk.onblur=this.tr.onmouseout=function(e) {
    if(this.obj.enabled)
      this.obj.tr.style.backgroundColor='#ffffff'
    else
      this.obj.tr.style.backgroundColor='transparent'
  }
  this.chk.onclick=function(e) {
  }
  this.tr.onclick=function(e) {
    if(this.obj.enabled)
      this.obj.uncheck()
    else
      this.obj.check()
    e.cancelBubble=true
  }
}
active_row.prototype.check=function() {
  this.enabled=true
  this.chk.checked=true
  update_total()
}
active_row.prototype.uncheck=function() {
  this.enabled=false
  this.chk.checked=false
  update_total()
}

function init() {
  activiteit=new Array()
  for(var i = 0; i < number_of_activities; i++) {
    activiteit[i] = new active_row('act_' + i)
  }

  betaling=new Array()
  for(var i = 0; i < number_of_betalingen; i++) {
    betaling[i] = new active_row('betid_' + i)
  }

  update_total()
<?php	$cal->render_js_init(); ?>
}
</script>
</head><body onload="javascript:init()" style="margin-left: 0px; margin-right: 0px;">
<h1 align=center><?=$title?></h1>
<form id='form' method=post action="afrekening_eval.php">
<input type=hidden id='action' name=action value="default">
<?php  if(@$afr_id) { ?>
<input type=hidden name=afr_id value="<?=$afr_id?>">
<?php }

?><table cellpadding=1 cellspacing=0 border=0 align=center>
<tr>
  <td align=right><label for="date"><?=__("datum")?>:</label></td>
  <td class="disabled" valign=top style="width: 80px;">
<?php $cal->render_html(); ?>
  </td>
</tr>
</table><br>

<table width="99%" cellspacing=4 cellpadding=2 align=center>
<tr>
  <td width="60%" valign=top>

<!-- Start Activiteiten -->
<table width="95%" cellspacing=0 cellpadding=2 style="border: 1px solid black;" align=center>
  <tr>
    <th width="100%"><?=__("activiteit")?></th>
  </tr>
<?php

	for($i = 0; $i < count($all_acts); $i++)
	{
		$activiteit = $all_acts[$i];
?>
  <tr style="background-color: #ffffff;">
    <td>
      <input type=checkbox id='act_<?=$i?>' name='activiteiten[]' value='<?=$activiteit['act_id']?>'<?php

	if(!is_array(@$info['activiteiten']) ||
			in_array($activiteit['act_id'], $info['activiteiten']))
		echo " checked";

?>>&nbsp;<?=$activiteit['name']?></input>
    </td>
  </tr>
<?php  }

?>
</table>
<?php if(@$afr_id) { ?>
    <p align=center><a href="javascript:void(0);"
      onclick="javascript:window.opener.location='afrekening_view.php?afr_id=<?=$afr_id?>';">[ <?=__("overzicht in hoofdscherm")?> ]</a>
<?php } ?>
<!-- Eind Activiteiten -->

  </td>
  <td width="40%" valign=top>

<!-- Start Betalingen -->
<table width="95%" cellspacing=0 cellpadding=2 style="border: 1px solid black;" align=center>
  <tr>
    <th colspan=5><?=__("betalingen")?></th>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <th align=center><?=__("van")?></th>
    <th align=center><?=__("aan")?></th>
    <th align=center><?=__("datum")?></th>
    <td>&nbsp;</td>
  </tr>
<?php
	for($i = 0; $i < count($betalingen); $i++)
	{
		$item = $betalingen[$i];
		?>
  <tr>
    <td>
      <input type=checkbox id='betid_<?=$i?>' name='betalingen[]' value=<?php
	echo "\"".$item['van'].":".$item['naar'].":".$item['datum']."\"";
	if($item['checked'])
		echo " checked";
?>></input>
    </td>
    <td align=center><?=$persons[$pers_reverse[$item['van']]]['nick']?></td>
    <td align=center><?=$persons[$pers_reverse[$item['naar']]]['nick']?></td>
    <td align=center><?=display_datum($item['datum'])?></td>
    <td align=right><?=$item['bedrag']?></td>
  </tr>
		<?php
	} ?>
</table>
<!-- Eind Betalingen -->

<br><br><br>

<!-- Start Balans -->
<table width="95%" cellspacing=0 cellpadding=2 style="border: 1px solid black;" align=center>
  <tr>
    <th colspan=2><?=__("balans")?></th>
  </tr>
<?php
	$i = 0;
	foreach($persons as $person)
	{	?>
  <tr style="background-color: white;">
    <td style="padding-left: 10px;"><?=$person['nick']?></td>
    <td style="text-align: right; padding-right: 10px;" class="input">
      <input type=text size=8 value="0.00" class="debet" id='pers_<?=$i?>_debet' disabled>
    </td>
  </tr>
<?php
		$i++;
	}
?>
</table>
<!-- Eind Balans -->

</td></tr>
</table>

<?php
	if($form->delete)
		$form->delete->value = "delete afrekening";
	$form->foot();
?></body></html>
