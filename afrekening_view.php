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

/* set $login, $db_conn */
require_once("pennypool.php");
require_once("lib_util.php");

?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN" 
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<title><?=__("Huisrekening")?></title>
<link rel=stylesheet title="Penny Pool" href="style.css">
<script type="text/javascript" language="JavaScript1.2">
function popup(link) {
  window.open(link,'','toolbar=no,status=no,menubar=no,width=400px,height=400px,resizable=yes,scrollbars=yes')
}
function popup_large(link) {
  window.open(link,'','toolbar=no,status=no,menubar=no,width=600px,height=400px,resizable=yes,scrollbars=yes')
}
</script>
</head>
<body><?

$afr_id=@$HTTP_GET_VARS["afr_id"];

/* link zorgt ervoor dat we alleen acts krijgen waaraan zelf is meegedaan */
$res = mysql_query("SELECT act.act_id as act_id,act.name,act.date,".
				   "act.afr_id as afr_id, deeln.pers_id, ".
				   "deeln.credit, deeln.aantal ".
		"FROM ".$db['prefix']."deelnemers deeln,".
			$db['prefix']."activiteiten act ".
		"WHERE deeln.act_id=act.act_id AND act.afr_id=$afr_id ".
		"ORDER BY act.act_id DESC",$db_conn);
$activiteiten = parse_activiteiten($res);
mysql_free_result($res);

$me=my_data();

?>
<table align=center width=70%>
<tr><td valign=middle align=left width=60%>
<h1 align=center><?=__("Afrekening")?></h1></td>
<td valign=middle align=center width=40%>
<a href="index.php">[ <?=__("terug naar index")?> ]</a>
</td></tr></table>

<table cellspacing=0 cellpadding=2 style="border: 1pt solid black;" align=center>
  <tr>
    <th colspan=2 style="color: #666;"><?=__("activiteiten")?></th>
<?

$nicks=$people->nick();
foreach($nicks as $id=>$nick)
{
	$person = $people->find($id);
	if($person['type'] == 'person')
	{
		echo "    <th style=\"padding: 0px 5px 0px 5px;\" nowrap>".
			"<a href=\"javascript:popup('person.php?pers_id=$id')\">$nick</a></th>\n";
	}
	else if($person['type'] == 'rekening')
	{
		echo "    <th style=\"padding: 0px 5px 0px 5px;\" nowrap>".
			"<a href=\"javascript:popup('account.php?pers_id=$id')\">$nick</a></th>\n";
	}
}
echo "  </tr>\n";

foreach($activiteiten as $item)
{
?>
  <tr onmouseover="this.style.backgroundColor='#cccccc'" onmouseout="this.style.backgroundColor='#ffffff'"
      onclick="popup('activiteit.php?act_id=<?=$item['act_id']?>')" class="enabled">
<?
	echo "    <td id=\"act_".$item['act_id']."\" nowrap>".$item['date']."</td>\n";
	echo "    <td id=\"act_".$item['act_id']."\" nowrap>".$item['name']."</td>\n";
	foreach($nicks as $id => $nick)
	{
		echo "    <td align=right style=\"padding: 0px 5px 0px 5px;\">";
		if(@$item['credit'][$id])
		{
			$amount=$item['credit'][$id]-$item['mult'][$id] * $item['debet'];
			echo amount_to_html($amount);
		}
		else
		{
			echo "&nbsp;";
		}
		echo "</td>\n";
	}
	echo "  </tr>\n";
}

$total_amount = calc_totals($activiteiten);

$res=mysql_query("SELECT van,naar,SUM(bedrag) as bedrag,afr_id ".
				  "FROM ".$db['prefix']."betalingen ".
				  "WHERE afr_id=$afr_id ".
				  "GROUP BY van,naar", $db_conn);
$sums = parse_betalingen($res);
mysql_free_result($res);
$sums = @$sums[$afr_id];

?>
  <tr>
	<th colspan=2 style="color: #666; padding-top: 8;"><?=__("betalingen")?></th>
<?
foreach($nicks as $id => $nick) {
	echo "    <td align=right>";
	if(@$sums[$id])
		echo amount_to_html($sums[$id]);
	else
		echo "&nbsp;";
	echo "</td>\n";
}
echo "  </tr>\n";

?>
  <tr>
    <th colspan=2 align=center><?=__("totaal")?></th>
<?
foreach($nicks as $id => $nick) {
	echo "    <th align=right style=\"padding: 0px 5px 0px 5px;\">";
	if(@$total_amount[$id] || @$sums[$id])
		echo amount_to_html(@$total_amount[$id]+@$sums[$id]);
	else
		echo "&nbsp;";
	echo "</th>\n";
}
echo "  </tr>\n";

echo "</table>\n";

?></body></html>
