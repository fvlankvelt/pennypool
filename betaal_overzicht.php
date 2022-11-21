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

require_once("pennypool.php");
include_once("lib_layout.php");

function datum_to_html($date) {
	list($year,$month,$day)=split('-',$date);
	return strftime("%d %b %y", mktime(0, 0, 0, $month, $day, $year));
	/*
	$months=array('jan','feb','mar','apr','mei','jun','jul','aug',
						'sep','okt','nov','dec');
	if(substr($day,0,1)=='0')
		$day=substr($day,1,1);
	return $day." ".$months[$month-1]." '".substr($year,2,2);
	*/
}

$me=my_data();
$res_aan=mysql_query("SELECT bet.van as van,bet.naar as naar,".
		"pers.nick as nick, datum,bedrag ".
		"FROM ".$db['prefix']."betalingen bet, ".$db['prefix']."mensen pers ".
		"WHERE bet.van=".$me['pers_id']." AND pers.pers_id=bet.naar ".
		"AND bet.afr_id=0 ".
		"ORDER BY bet.datum DESC LIMIT 20",$db_conn);
$res_van=mysql_query("SELECT bet.van as van,bet.naar as naar,".
		"pers.nick as nick, datum,bedrag ".
		"FROM ".$db['prefix']."betalingen bet, ".$db['prefix']."mensen pers ".
		"WHERE bet.naar=".$me['pers_id']." AND pers.pers_id=bet.van ".
		"AND bet.afr_id=0 ".
		"ORDER BY bet.datum DESC LIMIT 20",$db_conn);
$num_aan=mysql_num_rows($res_aan);
$num_van=mysql_num_rows($res_van);
$i=0;
$aan=array();
$van=array();
while($i<$num_aan || $i<$num_van) {
	if($i<$num_aan) {
		$aan[]=mysql_fetch_assoc($res_aan);
	}
	if($i<$num_van) {
		$van[]=mysql_fetch_assoc($res_van);
	}
	$i++;
}
mysql_free_result($res_aan);
mysql_free_result($res_van);

?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<title><?php=__("Betalingen")?></title>
<link rel=stylesheet title="Penny Pool" href="style.css">
<script language="JavaScript">
<?php
echo "var van=new Array(";
for($i=0;$i<$num_van;$i++) {
	if($i!=0)
		echo ",\n";
	$date=split('-',$van[$i]['datum']);
	echo "'van=".$van[$i]['van']."&naar=".$van[$i]['naar'].
		"&datum=".$date[2]."-".$date[1]."-".$date[0]."'";
}
echo ")\n";
echo "var aan=new Array(";
for($i=0;$i<$num_aan;$i++) {
	if($i!=0)
		echo ",\n";
	$date=split('-',$aan[$i]['datum']);
	echo "'van=".$aan[$i]['van']."&naar=".$aan[$i]['naar'].
		"&datum=".$date[2]."-".$date[1]."-".$date[0]."'";
}
echo ")\n";
?>
function popup(link) {
  window.open(link,'','toolbar=no,status=no,menubar=no,width=350px,height=270px,resizable=yes,scrollbars=yes')
}
function init() {
  document.getElementById('close').focus()
  for(var i=0;i<<?php=$num_aan?>;i++) {
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

  for(var i=0;i<<?php=$num_van?>;i++) {
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
<h1 align=center><?php=__("Betalingen")?></h1>
<table align=center cellspacing=0 cellpadding=2 width="80%" style="border: 1px solid black;">
  <tr>
    <th><?php=__("datum")?></th>
    <th><?php=__("aan")?></th>
    <th style="border-right: 1px solid black;"><?php=__("bedrag")?></th>
    <th><?php=__("datum")?></th>
    <th><?php=__("van")?></th>
    <th><?php=__("bedrag")?></th>
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
<p align=center><a href="javascript:popup('betaling.php')"><?php=__("nieuwe betaling")?></a>
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
