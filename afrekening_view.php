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

require_once 'vendor/autoload.php';
use \Doctrine\DBAL\ParameterType;

require_once("pennypool.php");
include_once("lib_cal.php");
include_once("lib_layout.php");
include_once("lib_util.php");

/**
 * @var Doctrine\DBAL\Connection $dbh
 * @var people $people
 */
global $dbh;
global $people;


?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<title><?=__("Huisrekening")?></title>
<link rel=stylesheet title="Penny Pool" href="style.css">
<?= javascript_popup(); ?>
</head>
<body><?php

$afr_id=@$HTTP_GET_VARS["afr_id"];

/* link zorgt ervoor dat we alleen acts krijgen waaraan zelf is meegedaan */
$stm = $dbh->executeQuery("
		SELECT act.act_id AS act_id, act.name, act.date, act.afr_id as afr_id,
			   deeln.credit, deeln.aantal
		FROM deelnemers AS deeln,
		     activiteiten AS act
		WHERE deeln.act_id=act.act_id AND
		      act.afr_id=?
		ORDER BY act.date DESC, act.act_id DESC
	",[$afr_id],[ParameterType::INTEGER]);
$activiteiten = parse_activiteiten($stm);

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
<?php

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
<?php
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

$stm = $dbh->executeQuery("SELECT van, naar, SUM(bedrag) AS BEDRAG, afr_id FROM betalingen WHERE afr_id=? GROUP BY van, naar",
	[$afr_id], [ParameterType::INTEGER]);
$sums = parse_betalingen($stm);
$sums = @$sums[$afr_id];

?>
  <tr>
	<th colspan=2 style="color: #666; padding-top: 8px;"><?=__("betalingen")?></th>
<?php
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
<?php
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
