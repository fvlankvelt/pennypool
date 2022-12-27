<?php
/*
	Huisrekening, a utility to share expenses among a group of friends
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
use \Doctrine\DBAL\Types\Type;


function randstr($size)
{
        $str='';
        for ($i=0;$i<$size;$i++)
        {
                $v = rand(0,10+26+26-1);
                if ($v<10)
                        $str.=$v;
                else if ($v<36)
                        $str.=chr($v-10+65);
                else
                        $str.=chr($v-36+65+32);
        }
        return $str;
}

/**
 * verwacht een db result
 *    act_id, name, afr_id, pers_id, credit
 * van alle deelnemers, gegroepeerd met
 *    act_id, name en afr_id
 * @param \Doctrine\DBAL\Result $res
 * @return string[][]
 */
function parse_activiteiten(Doctrine\DBAL\Result $res): array
{
	/** @var people $people */
	global $people;

	/* laad gegevens van alle mogelijke deelnemers */
	$people->find();

	/* first gather all data in a single data structure
	 * $activiteiten has all activiteiten in order
	 * $act_mapping maps $act_id to the correct array index of $activiteiten
	 */
	$activiteiten = array();
	$act_mapping = array();
	while($row = $res->fetchAssociative())
	{
		$act_id = $row['act_id'];
		if (!array_key_exists($act_id, $act_mapping)) {
			$activiteiten[] = array(
				'act_id' => $row['act_id'],
				'afr_id' => $row['afr_id'],
				'name'   => $row['name'],
				'date'   => $row['date'],
				'credit' => array(),
				'mult'   => array()

			);
			$act_mapping[$act_id] = array_key_last($activiteiten);
		}

		$this_act = &$activiteiten[$act_mapping[$act_id]];

		$pers_id = $row['pers_id'];
		$type    = $row['type'];
		$mult    = $row['aantal'];
		$credit  = $row['credit'];

		$this_act['credit'][$pers_id] = $credit;
		$this_act['mult'][$pers_id] = $type=='person' ? $mult : 0;

	}

	/* now go through the entire array again to calculate totals etc */
	foreach ($activiteiten as &$this_act)
	{
		$this_act['total'] = array_sum($this_act['credit']);
		$this_act['n']     = array_sum($this_act['mult']);
		$this_act['debet'] = $this_act['total'] / $this_act['n'];
	}

	return $activiteiten;
}

function filter_activiteiten($activiteiten, $afr_id = null)
{
	$ret = array();
	foreach($activiteiten as $item)
	{
		if(is_null($afr_id) && !$item['afr_id'])
			$ret[] = $item;
		else if(@$item['afr_id'] == $afr_id)
			$ret[] = $item;
	}
	return $ret;
}

function calc_totals($activiteiten)
{
	global $people;

	$nicks = $people->nick();

	$total_amount=array();
	foreach($activiteiten as $item)
	{
		foreach($nicks as $id => $nick)
		{
			if(array_key_exists($id,$item['credit']))
			{
				$amount=$item['credit'][$id]-$item['mult'][$id] * $item['debet'];
				$total_amount[$id]=@$total_amount[$id]+$amount;
			}
		}
	}
	return $total_amount;
}

function calc_debts($activiteiten, $me)
{
	global $people;

	$nicks = $people->nick();

	$my_debt = array();
	foreach($activiteiten as $item)
	{
		foreach($nicks as $id => $nick)
		{
			if(@$item['credit'][$id])
			{
				$my_debt[$id]=@$my_debt[$id]+
						($item['credit'][$id]-
								@$item['credit'][$me['pers_id']])/ $item['n'];
			}
		}
	}
	return $my_debt;
}

function filter_mensen($activiteiten)
{
	global $people;

	$all = $people->nick();
	foreach($activiteiten as $item)
	{
		foreach($item['credit'] as $id => $credit)
		{
			unset($all[$id]);
		}
		if(!count($all))
			return $people->nick();
	}
	return array_diff($people->nick(), $all);
}

function parse_betalingen(Doctrine\DBAL\Result $res): array
{
	global $people;

	$sums = array();
	$all = $people->nick();
	while($row = $res->fetchAssociative())
	{
		if(@$row['afr_id'])
			$afr_id = $row['afr_id'];
		else
			$afr_id = 0;
		$sums[$afr_id][$row['van']]  = @$sums[$afr_id][$row['van']]  + $row['bedrag'];
		$sums[$afr_id][$row['naar']] = @$sums[$afr_id][$row['naar']] - $row['bedrag'];
	}
	return $sums;
}

function date_from_sql(string $date_sql): DateTime
{
	/** @var Doctrine\DBAL\Connection $dbh */
	global $dbh;

	$date = Type::getType('date')->convertToPHPValue($date_sql, $dbh->getDatabasePlatform());
	return $date;
}

function date_to_sql(string $date): bool|string
{
	$dt = date_to_dt($date);
	if (!$dt) return false;
	return dt_to_sql($dt);
}

function dt_to_sql(DateTime $dt): string
{
	/** @var Doctrine\DBAL\Connection $dbh */
	global $dbh;

	$date_sqlsafe = Type::getType('date')->convertToDatabaseValue($dt, $dbh->getDatabasePlatform());
	return $date_sqlsafe;
}

function date_to_dt(string $date): bool|DateTime
{
	/* TODO really annoying that these formats are used inconsistently */
	$dt = DateTime::createFromFormat('!d-m-Y',$date);
	if (!$dt) $dt = DateTime::createFromFormat('!Y-m-d',$date) ;
	if (!$dt) return false;

	return $dt;
}
