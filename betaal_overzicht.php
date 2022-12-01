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


/* TODO:
	- uitsplitsen naar afrekening.
 */

require_once 'vendor/autoload.php';
use \Doctrine\DBAL\ParameterType;

require_once("pennypool.php");
include_once("lib_layout.php");
include_once("lib_util.php");

/**
 * @var Doctrine\DBAL\Connection $dbh
 */
global $dbh;

function datum_to_html(DateTime|null $date) : string {
	if (!$date) return "unknown";
	return $date->format('Y-m-d');
}

$me=my_data();
$res_aan = $dbh->executeQuery("
		SELECT bet.van AS `van`, bet.naar AS `naar`, pers.nick AS `nick`, datum, bedrag
		FROM betalingen AS `bet`, mensen AS `pers`
		WHERE bet.van=? AND pers.pers_id=bet.naar AND bet.afr_id=0
		ORDER BY datum DESC
	",[$me['pers_id']], [ParameterType::INTEGER]);
$res_van = $dbh->executeQuery("
		SELECT bet.van AS `van`, bet.naar AS `naar`, pers.nick AS `nick`, datum, bedrag
		FROM betalingen AS `bet`, mensen AS `pers`
		WHERE bet.naar=? AND pers.pers_id=bet.van AND bet.afr_id=0
		ORDER BY datum DESC
	",[$me['pers_id']], [ParameterType::INTEGER]);
$aan = $res_aan->fetchAllAssociative();
$van = $res_van->fetchAllAssociative();
$num_aan = count($aan);
$num_van = count($van);

function fix_dates(array &$data) {
	foreach ($data as &$row) {
		$row['datum'] = date_from_sql($row['datum']); /* annoyingly, this isn't automatic */
	}
	return;
}
fix_dates($aan);
fix_dates($van);

?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<title><?=__("Betalingen")?></title>
<link rel=stylesheet title="Penny Pool" href="style.css">
<script language="JavaScript">
<?php
echo "var van=new Array(";
for($i=0;$i<$num_van;$i++) {
	if($i!=0)
		echo ",\n";
	$date=datum_to_html($van[$i]['datum']);
	echo "'van={$van[$i]['van']}&naar={$van[$i]['naar']}&datum={$date}'";
}
echo ")\n";
echo "var aan=new Array(";
for($i=0;$i<$num_aan;$i++) {
	if($i!=0)
		echo ",\n";
	$date=datum_to_html($aan[$i]['datum']);
	echo "'van={$aan[$i]['van']}&naar={$aan[$i]['naar']}&datum={$date}'";
}
echo ")\n";
?>
function popup(link) {
  window.open(link,'','toolbar=no,status=no,menubar=no,width=350px,height=270px,resizable=yes,scrollbars=yes')
}
function init() {
  document.getElementById('close').focus()
  for(var i=0;i<<?=$num_aan?>;i++) {
    var obj=new Object();
    obj.date=document.getElementById('aan_'+i+'_date')
    obj.who=document.getElementById('aan_'+i+'_who')
    obj.amount=document.getElementById('aan_'+i+'_amount')
    obj.i=i

    obj.date.object=obj
    obj.who.object=obj
    obj.amount.object=obj
    obj.date.onmouseover=obj.who.onmouseover=obj.amount.onmouseover=
      function(e) {
        this.object.date.style.backgroundColor='#cccccc'
        this.object.who.style.backgroundColor='#cccccc'
        this.object.amount.style.backgroundColor='#cccccc'
      }
    obj.date.onmouseout=obj.who.onmouseout=obj.amount.onmouseout=
      function(e) {
        this.object.date.style.backgroundColor='#ffffff'
        this.object.who.style.backgroundColor='#ffffff'
        this.object.amount.style.backgroundColor='#ffffff'
      }
    obj.date.onclick=obj.who.onclick=obj.amount.onclick=
      function(e) {
        popup('betaling.php?'+aan[this.object.i])
      }
    obj.date.style.backgroundColor=obj.who.style.backgroundColor=
      obj.amount.style.backgroundColor='#ffffff'
  }

  for(var i=0;i<<?=$num_van?>;i++) {
    var obj=new Object();
    obj.date=document.getElementById('van_'+i+'_date')
    obj.who=document.getElementById('van_'+i+'_who')
    obj.amount=document.getElementById('van_'+i+'_amount')
    obj.i=i

    obj.date.object=obj
    obj.who.object=obj
    obj.amount.object=obj
    obj.date.onmouseover=obj.who.onmouseover=obj.amount.onmouseover=
      function(e) {
        this.object.date.style.backgroundColor='#cccccc'
        this.object.who.style.backgroundColor='#cccccc'
        this.object.amount.style.backgroundColor='#cccccc'
      }
    obj.date.onmouseout=obj.who.onmouseout=obj.amount.onmouseout=
      function(e) {
        this.object.date.style.backgroundColor='#ffffff'
        this.object.who.style.backgroundColor='#ffffff'
        this.object.amount.style.backgroundColor='#ffffff'
      }
    obj.date.onclick=obj.who.onclick=obj.amount.onclick=
      function(e) {
        popup('betaling.php?'+van[this.object.i])
      }
    obj.date.style.backgroundColor=obj.who.style.backgroundColor=
      obj.amount.style.backgroundColor='#ffffff'
  }
}
</script>
</head>
<body onload="init()">
<h1 align=center><?=__("Betalingen")?></h1>
<table align=center cellspacing=0 cellpadding=2 width="80%" style="border: 1px solid black;">
  <tr>
    <th><?=__("datum")?></th>
    <th><?=__("aan")?></th>
    <th style="border-right: 1px solid black;"><?=__("bedrag")?></th>
    <th><?=__("datum")?></th>
    <th><?=__("van")?></th>
    <th><?=__("bedrag")?></th>
  </tr>
<?php
$i=0;
while($i<$num_aan || $i<$num_van) {
	if($i<$num_aan) {
		$row=$aan[$i];
		echo "  <tr>\n    <td align=center id=\"aan_{$i}_date\" nobreak>".
				"<nobr>".datum_to_html($row['datum'])."</nobr></td>\n".
			"    <td align=center id=\"aan_{$i}_who\">".$row['nick']."</td>\n".
			"    <td align=right id=\"aan_{$i}_amount\" ".
				"style=\"border-right: 1px solid black;\">".
			amount_to_html($row['bedrag'])."</td>\n";
	} else {
		echo "  <tr>\n    <td colspan=3 style=\"border-right: 1px solid black;\">".
			"&nbsp;</td>\n";
	}
	if($i<$num_van) {
		$row=$van[$i];
		echo "    <td align=center id=\"van_{$i}_date\">".
				"<nobr>".datum_to_html($row['datum'])."</nobr>\n".
			"</td>\n    <td align=center id=\"van_{$i}_who\">".$row['nick'].
			"</td>\n    <td align=right id=\"van_{$i}_amount\">".
				amount_to_html($row['bedrag'])."</td>\n  </tr>\n";
	} else {
		echo "    <td colspan=3>&nbsp;</td>\n  </tr>\n";
	}
	$i++;
}
?>
</table>
<p align=center><a href="javascript:popup('betaling.php')"><?=__("nieuwe betaling")?></a>
</p>
<form><p align=center>
<?php
	$button = new button("close");
	$button->id = "close";
	$button->onclick = "window.opener.location='index.php';window.close()";
	$button->render();
?>
</p></form>
</body></html>
