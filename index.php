<?php
/*
	PennyPool, a utility to share expenses among a group of friends
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
require_once("lib_layout.php");

use \Doctrine\DBAL\ParameterType;

/**
 * @var Doctrine\DBAL\Connection $dbh
 * @var string $login
 * @var people $people
 */
global $dbh, $login, $people;

?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<title><?=__("Huisrekening")?></title>
<link rel=stylesheet title="Penny Pool" href="style.css">
<?= javascript_popup(); ?>
</head>
<body><?php

$me=my_data();

/* select alle deelnemers/activiteiten waar we zelf aan deelnemen (voor overzicht)
 * of die in een afrekening zitten (voor totalen afrekening) */
$sql = "
SELECT
	deeln.act_id AS act_id, act.name, act.date,
	act.afr_id AS afr_id, deeln.pers_id, deeln.credit,
	deeln.aantal, pers.type
FROM
	deelnemers AS deeln
LEFT JOIN
	activiteiten AS act on act.act_id=deeln.act_id
LEFT JOIN
	mensen AS pers ON deeln.pers_id=pers.pers_id
WHERE
      act.afr_id!=0
   OR act.act_id IN (SELECT DISTINCT act_id FROM deelnemers WHERE pers_id=?)
ORDER BY
	act.date DESC, act_id DESC, deeln.pers_id ASC;
";
$res = $dbh->executeQuery($sql, [$me['pers_id']], [ParameterType::INTEGER]);

$activiteiten = parse_activiteiten($res);


?>
<table align=center width=70%>
  <tr>
    <td valign=middle align=left width=60% nowrap>
      <h1><?php printf(__("%s's Huisrekening"), $me['nick'])?></h1></td>
    <td valign=middle align=center width=40%>
      <table align=center cellpadding=5 cellspacing=0>
        <tr>
          <td align=right valign=top style="padding: 0px 10px 0px 10px;">
            <nobr><b><?=__("Nieuw")?>:</b>
            [&nbsp;<a href="javascript:popup('person.php')"><?=__("persoon")?></a>&nbsp;]
            [&nbsp;<a href="javascript:popup('account.php')"><?=__("rekening")?></a>&nbsp;]
            </nobr>
          </td>
        </tr>
        <tr>
          <td align=right valign=top style="padding: 0px 10px 0px 10px;">
            <nobr>[&nbsp;<a href="javascript:popup('person.php?pers_id=<?=$me['pers_id']?>')"><?=__("mijn gegevens")?></a>&nbsp;]
            [&nbsp;<a href="logout.php"><?=__("logout")?></a>&nbsp;]
            </nobr>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<table cellspacing=0 cellpadding=3 style="border: 1pt solid black; border-collapse: collapse;" align=center>
  <tr>
    <th colspan=2 style="color: #666; padding-top: 8pt; padding-bottom: 8pt;"><?=__("activiteiten")?>
      <small><a href="javascript:popup('activiteit.php')" style="color: #666;">(<?=__("nieuw")?>)</a></small>
    </th>
<?php
$nicks=$people->nick();
foreach($nicks as $id=>$nick)
{
	$person = $people->find($id);
	if($person['type'] == 'person')
	{	?>
    <th style="padding: 0px 5px 0px 5px;" nowrap>
      <a href="javascript:popup('person.php?pers_id=<?=$id?>')"><?=$nick?></a>
    </th>
<?php	}
	else if($person['type'] == 'rekening')
	{	?>
    <th style="padding: 0px 5px 0px 5px;" nowrap>
      <a href="javascript:popup('account.php?pers_id=<?=$id?>')"><?=$nick?></a>
    </th>
<?php	}
}
echo "  </tr>\n";

$current = filter_activiteiten($activiteiten);

foreach($current as $item)
{
	if($item['afr_id'])
		continue;
?>
  <tr onmouseover="this.style.backgroundColor='#cccccc'" onmouseout="this.style.backgroundColor='#ffffff'"
      onclick="popup('activiteit.php?act_id=<?=$item['act_id']?>')" class="enabled">
<?php
	echo "    <td id=\"act_".$item['act_id']."\" nowrap>".$item['date']."</td>\n";
	echo "    <td id=\"act_".$item['act_id']."\" nowrap>".$item['name']."</td>\n";
	foreach($nicks as $id => $nick)
	{
		echo "    <td align=right style=\"padding: 0px 5px 0px 5px;\">";
		if(array_key_exists($id, $item['credit']))
		{
			$amount = $item['credit'][$id] - $item['mult'][$id] * $item['debet'];
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

$total_amount = calc_totals($current);
$my_dept = calc_debts($current, $me);

?>
  <tr>
    <th  style="padding-bottom: 8pt;" colspan=2 align=center><?=__("totaal")?></th>
<?php
foreach($nicks as $id => $nick) {
	echo "    <th align=right style=\"padding: 0px 5px 0px 5px;\">";
	if(@$total_amount[$id])
		echo amount_to_html($total_amount[$id]);
	else
		echo "&nbsp;";
	echo "</th>\n";
}	?>
  </tr>
<?php

/* betalingen */
?>
  <tr style=" border-top: 1pt solid black;">
    <th colspan=2 style="color: #666; padding-top: 8pt; padding-bottom: 8pt;"><?=__("betalingen")?></th>
<?php
foreach($nicks as $id => $nick) { ?>
    <th>&nbsp;</th>
<?php } ?>
  </tr>
<?php

/*
 * twee rijen:
 *       - wat ik heb betaald / aan mij is betaald
 *		 - wat verder aan elkaar is betaald
 */
$stm = $dbh->prepare("
	SELECT van,naar,SUM(bedrag) as bedrag
	FROM betalingen
	WHERE
	    (van=:me OR naar=:me)
		AND afr_id=0
	GROUP BY van,naar");
$stm->bindValue("me", $me['pers_id'], ParameterType::INTEGER);
$res = $stm->executeQuery();

$sums = parse_betalingen($res);
$sums = @$sums[0];

?>
  <tr onmouseover="this.style.backgroundColor='#cccccc'" onmouseout="this.style.backgroundColor='#ffffff'"
      onclick="popup('betaal_overzicht.php')" class="enabled">
    <td colspan=2><?=__("aan/door mij betaald")?></td>
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

/* rest */
$stm = $dbh->prepare("
	SELECT van, naar, SUM(bedrag) as bedrag
	FROM betalingen
	WHERE
	    NOT (van=:me OR naar=:me)
		AND afr_id=0
	GROUP BY van,naar
");
$stm->bindValue("me", $me['pers_id'], ParameterType::INTEGER);
$res=$stm->executeQuery();

$sums = parse_betalingen($res);
$sums = @$sums[0];

?>
  <tr>
    <td style="padding-bottom: 8pt;" colspan=2><?=__("andere betalingen")?></td>
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

/* Afrekeningen */

$total_afr = array();
foreach($activiteiten as $item)
{
	if(!$item['afr_id'])
		continue;
	$afr_id = $item['afr_id'];
	if(!@$total_afr[$afr_id])
		$total_afr[$afr_id] = array();
	foreach($nicks as $id => $nick)
	{
		if(array_key_exists($id, $item['credit']) or array_key_exists($id, $item['mult']))
		{
			$amount = $item['credit'][$id];
			$person = $people->find($id);
			if($person['type'] == 'person')
				$amount -= $item['mult'][$id] * $item['debet'];
			$total_afr[$afr_id][$id] = @$total_afr[$afr_id][$id] + $amount;
		}
	}
}

$res = $dbh->executeQuery("SELECT * FROM betalingen WHERE afr_id!=0");
$total_bet = parse_betalingen($res);

foreach($total_bet as $afr_id => $sums) {
	foreach($sums as $id => $sum) {
		$total_afr[$afr_id][$id] =
			@$total_afr[$afr_id][$id] + $sum;
	}
}

$res = $dbh->executeQuery("SELECT * FROM afrekeningen ORDER BY date DESC");
?>
  <tr style="border-top: 1pt solid black;">
    <th colspan=2 style="color: #666; padding-top: 8pt; padding-bottom: 8pt;"><?=__("afrekeningen")?>
      <small><a href="javascript:popup_large('afrekening.php')" style="color: #666;">(<?=__("nieuw")?>)</a></small>
    </th>
<?php  foreach($nicks as $id=>$nick) { ?>
    <th>&nbsp;</th>
<?php  } ?>
  </tr>
<?php

while($row = $res->fetchAssociative())
{	?>
  <tr onmouseover="this.style.backgroundColor='#cccccc'" onmouseout="this.style.backgroundColor='#ffffff'"
      onclick="popup_large('afrekening.php?afr_id=<?=$row['afr_id']?>')" class="enabled">
<?php
	echo "    <th colspan=2>".$row['date']."</th>\n";
	foreach($nicks as $id => $nick)
	{
		echo "    <td align=right>";
		if(@$total_afr[$row['afr_id']][$id])
		{
			echo amount_to_html($total_afr[$row['afr_id']][$id]);
		}
		else
		{
			echo "&nbsp;";
		}
		echo "</td>\n";
	}
	echo "  </tr>\n";
}

echo "</table><br>\n";

?>
</body></html>
