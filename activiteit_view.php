<?php

/* set $login, $db_conn */
require_once("pennypool.php");
require_once("lib_util.php");

/* link zorgt ervoor dat we alleen acts krijgen waaraan zelf is meegedaan */
$res = mysql_query("SELECT act.act_id as act_id, act.name, act.date,".
				   "act.afr_id as afr_id, deeln.pers_id, deeln.credit, ".
				   "deeln.aantal ".
		"FROM ".$db['prefix']."deelnemers deeln,".
			$db['prefix']."activiteiten act ".
		"WHERE deeln.act_id=act.act_id AND act.afr_id=$afr_id ".
		"ORDER BY act.act_id DESC",$db_conn);
$activiteiten = parse_activiteiten($res);
mysql_free_result($res);

?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<title><?php=$title?></title>
<link rel=stylesheet title="Penny Pool" href="style.css">
</head>
<body>
<h1 align=center><?php=__("Activiteit")?></h1>
<table cellpadding=1 cellspacing=0 border=0 align=center>
<tbody>

</tbody>
</table><br>

<table width="80%" align=center>
  <tr>
    <td align=center><?php

$button=new button("Close");
$button->onclick="window.close()";

    ?></td>
  </tr>
</table>

</body> </html>
