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

/*
   usage: <me>.php 				-> nieuwe activiteit
   		  <me>.php?act_id=..	-> edit activiteit
		  <me>.php,POST_VARS	-> edit verkeerde velden
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

$res = $dbh->executeQuery("SELECT * FROM mensen ORDER BY type DESC, nick ASC");
$ids = array();
$persons = array();
$accounts = 0;
while($row=$res->fetchAssociative())
{
	$row['checked'] = 0;
	$row['credit']  = '0.00';
	$row['mult'] = 1;
	$ids[] = $row['pers_id'];
	$persons[$row['pers_id']] = $row;
	if($row['type'] == 'rekening')
		$accounts++;
}

if(!@$_POST && !@$_GET)
{
	$title = __("Nieuwe activiteit");
	$date = new DateTime("now");
	$name = "";
}
else if(@$_POST['ids'])
{
	/* back from evaluate */
	if(@$_POST['act_id'])
	{
		$title = __("Activiteit bewerken");
		$act_id = $_POST['act_id'];
	}
	else
	{
		$title = __("Nieuwe activiteit");
	}

	for($i = 0; $i < count($ids); $i++)
	{
		if(@$_POST['credit_'.$i] == 'yes')
		{
			$persons[$ids[$i]]['checked'] = 1;
			$persons[$ids[$i]]['credit'] = $_POST['id_'.$i.'_credit'];
			$persons[$ids[$i]]['mult'] = $_POST['id_'.$i.'_mult'];
		}
	}
	if(@$_POST['date'])
	{
		$date=DateTime::createFromFormat('!d-m-Y',$_POST['date']);
	}
	if(@$_POST['name'])
	{
		$name=$_POST['name'];
	}
}
else if (@$_GET['act_id'])
{
	/* edit existing act */
	$title = __("Activiteit bewerken");
	$act_id = $_GET['act_id'];

	$res = $dbh->executeQuery("SELECT pers_id,credit,aantal FROM deelnemers WHERE act_id=?",
		[$act_id], [ParameterType::INTEGER]);
	while($row = $res->fetchAssociative())
	{
		$persons[$row['pers_id']]['checked'] = 1;
		$persons[$row['pers_id']]['credit']  = amount_to_str($row['credit']);
		$persons[$row['pers_id']]['mult']    = $row['aantal'];
	}

	$row = $dbh->executeQuery("SELECT name,date from activiteiten WHERE act_id=?",
		[$act_id], [ParameterType::INTEGER])->fetchAssociative();
	$name = $row['name'];
	/*
	if($row['date'] != '0000-00-00')
	{
		$tmpdate = explode('-', $row['date'], 3);
		$date = $tmpdate[2]."-".$tmpdate[1]."-".$tmpdate[0];
	}
	else
	{
		$date = date('d-n-Y');
	}
	*/
	$date = date_from_sql($row['date']);
}

$cal = new calendar("date",$date);
$form = new form("activiteit_eval.php", (@$act_id?true:false));

?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<title><?=$title?></title>
<script language="JavaScript">

var number_of_persons=<?=count($persons)."\n"?>

function amount_string(amount)
{
  amount = '' + Math.round(amount) / 100;
  var s=amount.split(/\./)
  if(s.length==1) {
    s[1]='00'
  } else {
    if(s[1].length==1) {
      s[1]=s[1]+'0'
    } else if(s[1].length>2) {
      s[1]=s[1].substr(0,2)
    }
  }
  return s[0]+'\.'+s[1]
}

function update_total() {
  var total=new Array(0,0)
  var debet_unit=0.0
  var n=0
  for(var i=0;i<number_of_persons;i++) {
    var credit=document.getElementById('id_'+i+'_credit')
    var mult=credit.obj.mult
    if(!credit.disabled && !credit.obj.is_account) {
      n+=parseInt(mult.value)
    }
    if(credit) {
      s=credit.value.split(/\./)
      total[0]+=parseInt(s[0])
      total[1]+=parseInt(s[1])
      if(total[1]>99) {
        total[0]+=1
        total[1]-=100
      }
    }
  }
  var str=total[0]+'\.'+total[1]
  if(total[1]<10) {
    str=total[0]+'\.0'+total[1]
  }
  document.getElementById('amount_total').value=str

  if(n>0) {
    debet_unit=100.0 * (total[0]+.01*total[1])/n
  }
  for(var i=0;i<number_of_persons;i++) {
    var credit=document.getElementById('id_'+i+'_credit')
    var debet=document.getElementById('id_'+i+'_debet')
    var mult=credit.obj.mult
    if(credit.obj.is_account)
      continue
    if(!credit.disabled) {
      debet.value=amount_string(mult.value * debet_unit)
    } else {
      debet.value="0.00"
    }
  }
}

active_row=function(id,is_account) {
  this.chk=document.getElementById('id_'+id)
  this.chk.checked=true
  this.tr=this.chk.parentNode.parentNode
  this.credit=document.getElementById('id_'+id+'_credit')
  this.mult=new Object()
  this.is_account=is_account
  this.cache='0.00'
  this.enabled=true
  this.cancel=false

  if(!is_account)
    this.debet = document.getElementById('id_'+id+'_debet')
  else
    this.debet = new Object()

  if(this.chk.defaultChecked) {
    this.enabled=true
    this.chk.checked=true
    this.credit.disabled=false
    this.tr.style.backgroundColor='#ffffff'
  } else {
    this.enabled=false
    this.chk.checked=false
    this.credit.disabled=true
    this.tr.style.backgroundColor='transparent'
  }

  this.credit.obj=this.mult.obj=this.debet.obj=this.chk.obj=this.tr.obj=this

  this.credit.onchange=function(e) {
    var s=this.value.split(/\./)
    if(s.length==1) {
      s=this.value.split(/,/)
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
      s=new Array('0','00')
    }
    this.value=parseInt(s[0])+'\.'+s[1]

    update_total()
  }

  this.mult.onchange=function(e) {
    this.update()
    update_total()
  }

  this.debet.onmouseover=this.chk.onfocus=this.tr.onmouseover=function(e) {
    this.obj.tr.style.backgroundColor='#cccccc'
  }
  this.debet.onmouseout=this.chk.onblur=this.tr.onmouseout=function(e) {
    if(this.obj.enabled)
      this.obj.tr.style.backgroundColor='#ffffff'
    else
      this.obj.tr.style.backgroundColor='transparent'
  }
  this.credit.onclick=function(e) {
    e.cancelBubble=true
  }
  this.chk.onclick=function(e) {
  }
  this.tr.onclick=function(e) {
    if(this.obj.cancel) {
      this.obj.cancel=false
      return
    }
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
  this.credit.disabled=false
  this.credit.value=this.cache
  update_total()
}
active_row.prototype.uncheck=function() {
  this.enabled=false
  this.chk.checked=false
  this.credit.disabled=true
  this.cache=this.credit.value
  this.credit.value='0.00'
  update_total()
}

function select_mult(id,i) {
  row=document.getElementById('id_'+id).obj
  row.mult.value=i
  row.cancel=true
  update_total()
}

function cancel(id) {
  row=document.getElementById('id_'+id).obj
  if(select=document.getElementById('id_'+id+'_mult')) {
    row.mult.value=select.value
	update_total()
  }
  row.cancel=true
}
</script>
<?php
	$cal->render_js();
?>
<script language="JavaScript">
function init() {
  var i

  document.getElementById('name').focus()
  row=new Array()
  for(var i=0; i < <?=$accounts?>; i++) {
    row[i] = new active_row(i, true)
  }
  for(var i = <?=$accounts?>; i < number_of_persons; i++) {
    row[i] = new active_row(i, false)
  }
<?php
	for($i = 0; $i < count($persons); $i++) {
		echo "  row[$i].mult.value={$persons[$ids[$i]]["mult"]}\n";
	}  ?>
  update_total()
<?php	$cal->render_js_init(); ?>
}
</script>
<link rel=stylesheet title="Penny Pool" href="style.css">
</head>
<body onload="javascript:init()">
<h1 align=center><?=$title?></h1>
<?php
	$form->head();

if(@$act_id) { ?>
<input type=hidden name=act_id value="<?=$act_id?>">
<?php } ?>
<input type=hidden name=ids value="<?=implode(',',$ids)?>">
<!-- Start Activiteit -->
<table cellpadding=1 cellspacing=0 border=0 align=center>
<tbody>
<tr><td align=right><label for="name"><?=__("activiteit")?>:</label></td>
<td class="input"><input type=text id="name" name="name" size=30 style="font-size: 80%;" value="<?=$name?>"></td></tr>
<tr><td align=right><label for="date"><?=__("datum")?>:</label></td>
<td class="disabled" valign=top style="width: 80px;">
<?php $cal->render_html(); ?>
</td></tr>
</tbody>
</table><br>
<!-- Eind Activiteit -->

<!-- Start Deelnemers -->
<table width="90%" cellspacing=0 cellpadding=2 style="border: 1px solid black;" align=center>
<tbody id='credit_table'>
<tr>
  <th width="50%"><?=__("naam")?></th>
  <th>#</th>
  <th><?=__("credit")?></th>
  <th style="color: #660000;"><?=__("debet")?></th>
</tr>
<?php
	for($i = 0; $i < count($persons); $i++)
	{
		$person = $persons[$ids[$i]];
	?>
<tr style="background-color: #ffffff;">
  <td><input type=checkbox id='id_<?=$i?>' name='credit_<?=$i?>' value='yes'<?php
	if($person['checked'])
		echo " checked";
?>>&nbsp;<?=$person['nick']?></input></td>
  <td align=center class="input">
<?php  if($person['type'] == "person") { ?>
    <select name='id_<?=$i?>_mult' id='id_<?=$i?>_mult' onclick="cancel(<?=$i?>)">
<?php
		for($j = 1; $j < 8; $j++)
		{
			echo "      <option name=\"$j\" value=\"$j\"";
			if($person['mult'] == $j)
				echo " selected";
			echo " onclick=\"select_mult($i,$j)\">$j</option>\n";
		}
	?>
    </select>
<?php	} else {
	echo "&nbsp;";
	} ?>
  </td>
  <td align=center class="input">
    <input type=text size=8 class="credit" name='id_<?=$i?>_credit'
      id='id_<?=$i?>_credit' value="<?php
		echo (@$person['credit']?$person['credit']:"0.00"); ?>"></td>
  <td align=center class="input">
<?php	if($person['type'] == 'person') { ?>
    <input type=text size=8 value="0.00" class="debet" id='id_<?=$i?>_debet' disabled>
<?php	} ?></td>
</tr>
<?php  } ?>
<tr>
  <th><?=__("totaal")?></th>
  <td class="input" colspan=2 align=center>
    <input type="text" size=8" value="0.00" class="debet" id='amount_total' disabled>
  </td>
</tr>
</tbody>
</table><br>
<!-- Eind Deelnemers -->

<?php
	$form->foot();

?></body></html>
