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

/**
 * @var Doctrine\DBAL\Connection $dbh
 * @var string $login
 * @var people $people
 */

require_once 'vendor/autoload.php';

use \Doctrine\DBAL\DriverManager;
use \Doctrine\DBAL\ParameterType;

if(!file_exists('config.php')) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: setup.php");
	exit();
} else if(!is_readable('config.php')) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: login.php");
	exit();
}
include_once('config.php');
global $db, $login, $people, $dbh;

class people {
	var $cache=array();

	function __construct() {
		$this->cache=array();
	}

	function nick($id = null) {
		$rows=$this->find($id);
		if(is_array($rows)) {
			$ret=array();
			foreach($rows as $row) {
				$ret[$row['pers_id']]=$row['nick'];
			}
			return $ret;
		} else {
			return $rows['nick'];
		}
	}

	function find($id = null) {
		global $dbh;

		if(is_array(@$id)) {
			$find=array();
			foreach($id as $i) {
				if(!@$this->cache[$i])
					$find[]=$i;
			}
			if(count($find)>0) {
				$res = $dbh->executeQuery(
					"SELECT * FROM mensen WHERE pers_id IN (?)",
					[@$id], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
				);
				while($row = $res->fetchAssociative()) {
					$row['password']="";
					$this->cache[$row['pers_id']]=$row;
				}
				$this->_order();
			}
			$ret=array();
			foreach($this->cache as $key=>$val) {
				if(in_array($key,$id))
					$ret[]=$val;
			}
			return $ret;
		} else if(@$id) {
			if(!@$this->cache[$id]) {
				$res = $dbh->executeQuery(
					"SELECT * FROM mensen WHERE pers_id=?",
					[$id], [ParameterType::INTEGER]
				);
				// TODO: handle case where nothing is found
				$this->cache[$id]=$res->fetchAssociative();
				$this->_order();
			}
			return $this->cache[$id];
		} else {
			$res = $dbh->executeQuery("SELECT * FROM mensen ORDER BY type, nick ASC");
			$ret=array();
			while($row = $res->fetchAssociative()) {
				$row['password']="";
				$this->cache[$row['pers_id']]=$row;
				$ret[]=$row;
			}
			return $ret;
		}
	}

	function _order() {
		uasort($this->cache,array("people","_cmp"));
	}

	function _cmp($a,$b) {
		return strcmp($a['nick'],$b['nick']);
	}
}

function amount_to_html($amount): string
{
	if(!$amount || abs($amount)<.005)
		return "0.00";
	$amount_str = sprintf("%.2f", $amount);
	if($amount<0) {
		return "<font color=red>$amount_str</font>";
	} else {
		return $amount_str;
	}
}

function my_data() {
	global $login,$dbh;

	$res = $dbh->executeQuery(
		"SELECT * FROM mensen WHERE nick=?",
		[$login], [ParameterType::STRING]
	);
	// TODO: show throw exception here if nothing found
	$row = $res->fetchAssociative();
	$row['password']="";
	return $row;
}

function get_languages()
{
	$languages=array("nl");
	$dir=opendir("lang");
	while($file=readdir($dir)) {
		if(!preg_match("\.php$", $file) || $file=="new_lang.php")
			continue;
		$languages[]=preg_replace("(.*).php$","\\1",$file);
	}
	return $languages;
}

function select_language($lng = "")
{
	global $lang_data;
	global $pp;

	if($lng == 'nl')
		$lang_data=array();
	else if($lng != '' && file_exists("lang/".$lng.".php"))
		include_once("lang/".$lng.".php");
	else if(isset($pp['lang']) && file_exists("lang/".$pp['lang'].".php"))
		include_once("lang/".$pp['lang'].".php");
	else
		$lang_data=array();
}

// half-assed attempt at internationalization.
// we do not want to invade the gettext namespace, however
function __($txt)
{
	global $lang_data;

	if(@$lang_data[$txt])
		return $lang_data[$txt];
	return $txt;
}

$people = new people();

unset($login);
session_start();
$login=@$_SESSION['login'];

if(!@$login) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: login.php");
	exit(0);
}

select_language($_SESSION['lang']);

setlocale(LC_TIME, 'nl_NL');

$conn_params = ['url' => $db['url']];
$dbh = DriverManager::getConnection($conn_params);

unset($db['url']);
unset($db['user']);
unset($db['passwd']);

