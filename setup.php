<?
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

function create_tables($prefix, $conn)
{
	$tables = array(
	"activiteiten" => array(
		"base" => 
<<<HEREDOC
  act_id int(11) NOT NULL auto_increment,
  name varchar(40) NOT NULL default '""',
  date date NOT NULL default '0000-00-00',
  PRIMARY KEY  (act_id),
  UNIQUE KEY act_id (act_id),
  KEY name (name,date)
HEREDOC
			,
  		"afr_id" => "afr_id int(11) default '0'"
		),

	"afrekeningen" => array(
		"base" =>
<<<HEREDOC
  afr_id int(11) NOT NULL auto_increment,
  date date NOT NULL default '0000-00-00',
  PRIMARY KEY (afr_id),
  UNIQUE KEY afr_id (afr_id)
HEREDOC
		),

	"betalingen" => array(
		"base" =>
<<<HEREDOC
  van int(11) NOT NULL default '0',
  naar int(11) NOT NULL default '0',
  datum date NOT NULL default '0000-00-00',
  bedrag decimal(10,2) default '0.00',
  PRIMARY KEY  (van,naar,datum)
HEREDOC
  		,
		"afr_id" => "afr_id int(11) default '0'"
		),

	"deelnemers" => array(
		"base" => 
<<<HEREDOC
  act_id int(11) NOT NULL default '0',
  pers_id int(11) NOT NULL default '0',
  credit decimal(10,2) default '0.00',
  PRIMARY KEY  (act_id,pers_id)
HEREDOC
		,
		"aantal" => "aantal int(11) NOT NULL default '1'"
		),

	"mensen" => array(
		"base" =>
<<<HEREDOC
  pers_id int(11) NOT NULL auto_increment,
  voornaam varchar(10) NOT NULL default '',
  achternaam varchar(20) NOT NULL default '',
  rekeningnr varchar(9) default NULL,
  nick varchar(10) NOT NULL default '',
  email varchar(40) default NULL,
  password varchar(16) NOT NULL default '',
  PRIMARY KEY  (pers_id),
  UNIQUE KEY pers_id (pers_id,nick),
  UNIQUE KEY nick (nick)
HEREDOC
		,
		"init" =>
<<<HEREDOC
  (pers_id,voornaam,nick) VALUES (1,'User','user')
HEREDOC
		,
		"type" => "type varchar(20) default 'person'",
		"lang" => "lang varchar(5) default 'en'"
		)
	);

	foreach($tables as $name => $type)
	{
		$res=mysql_query("DESCRIBE {$prefix}{$name}", $conn);
		if(mysql_errno($conn) == 1146)
		{
			echo "creating $name<br>\n";

			mysql_query("CREATE TABLE {$prefix}{$name} ( ".
						$type["base"]." ) TYPE=MyISAM", $conn);
			if(@$type["init"])
			{
				mysql_query("INSERT INTO {$prefix}{$name} ".
							$type["init"], $conn);
			}
			$res=mysql_query("DESCRIBE {$prefix}{$name}", $conn);
		}

		if(mysql_errno($conn))
		{
			error("Error for table \"{$prefix}{$name}\"",
					"An unknown error occured when querying the ".
					"database for table \"{$prefix}{$name}\".",
				  array());
		}

		unset($type["base"]);
		unset($type["init"]);
		while($row = mysql_fetch_assoc($res))
		{
			unset($type[$row["Field"]]);
		}
		mysql_free_result($res);

		if(count($type))
		{
			$query = "ALTER TABLE {$prefix}{$name} ";
			$first = true;
			foreach($type as $column => $sub)
			{
				if($first)
					$first = false;
				else
					$query .= ", ";
				$query .= "ADD COLUMN ".$sub;
			}
			mysql_query($query, $conn);
		}
	}
}

function print_style($default = "", $errs = array(), $field = null)
{
	if(in_array($field, $errs))
	{
		if($default)
			$default .= " ";
		echo " style=\"{$default}color: red;\"";
	}
	else if($default)
		echo " style=\"{$default}\"";
}

// error handling
function error($title, $explanation, $fields) {
	form(false, array('title' => $title,
					  'explanation' => $explanation),
		 $fields);
}

function form($upgrade = false, $error = array(),
			  $errs = array()) {
	global $_POST;
?>
<body>
<center>
<h1>Penny Pool Setup</h1>
<?
	if(count($error)) {
?>
<div style="width: 60%; align: center; background-color: white; color: red;
            border: 1pt solid #aaa; padding: 2pt;">
<h2><?=$error['title']?></h2>
<p align=center style="font-size: small; color: red">
<?=$error['explanation']?></p></div><br>
<?
	} else if($upgrade) {
?>
<div style="width: 60%; align: center; background-color: white; border: 1pt solid #aaa;
            padding-left: 8pt; padding-right: 8pt;">
<p align=center style="font-size: small;">An existing installation of Penny
Pool / Huisrekening has been detected.  An attempt will be made to upgrade
automatically.  However, success is not guaranteed.  Please do not forget to
make a backup of your existing data.</p></div><br>
<?
	}
?>
<form method=post action="setup.php">
<input type=hidden name="step" value="create_tables">
<table width="80%" cellpadding=2 style="border: 1pt solid black;">
<tr><td valign=top width="30%" style="border: 1pt solid #aaa; padding: 4pt; padding-bottom: 8pt;">
<h3>Database</h3>
<table align=center>
  <tr>
    <td align=right<? print_style("", $errs, "db_host");
		?>><label for="db_host">host:</label></td>
    <td><input type=text name="db_host" id="db_host" size=16 value="<?
	if(@$_POST['db_host'])
		echo $_POST['db_host'];
	else
		echo "localhost";
?>"></td>
  </tr>
  <tr>
    <td colspan=2 style="padding-top: 8pt;">
      <input type=radio name="db_exist" id="db_e_new" value="new"<? 
	if(!@$_POST['db_exist'] || @$_POST['db_exist']=='new')
		echo " checked";
?>><label for="db_e_new">New Database</label><br>
      <input type=radio name="db_exist" id="db_e_old" value="old"<?
	if(@$_POST['db_exist']=='old')
		echo " checked";
?>><label for="db_e_old">Existing Database</label>
    </td>
  <tr>
    <td align=right<? print_style("", $errs, "db_name");
		?>><label for="db_name">name:</label></td>
    <td><input type=text name="db_name" id="db_name" size=16 value="<?
if(@$_POST['db_name'])
	echo $_POST['db_name'];
else
	echo "pennypool"; 
?>"></td>
  </tr>
</table>
</td><td valign=top width="45%" colspan=2 style="border: 1pt solid #aaa; padding: 4pt;">
<h3>Database User</h3>
<input type=radio name="user_exist" value="new"<? 
if(!@$_POST['user_exist'] || @$_POST['user_exist']=='new')
	echo " checked"; ?>>New User<br>
<input type=radio name="user_exist" value="old"<?
if(@$_POST['user_exist']=='old')
	echo " checked"; ?>>Existing User<br>
<table>
  <tr>
    <td align=right<? print_style("", $errs, "user");
		?>><label for="user">user:</label></td>
    <td><input type=text name="user" id="user" size=14 value="<?
if(@$_POST['user'])
	echo $_POST['user'];
else
	echo "pennypool"; 
?>"></td>
  </tr>
  <tr>
    <td align=right<? print_style("", $errs, "pass");
		?>><label for="pass">password:</label></td>
    <td><input type=password name="pass" id="pass" size=8<?
if(@$_POST['pass'])
	echo " value=\"".$_POST['pass']."\"";
?>></td>
  </tr>
</table><br>
<small>The database user should have permission to create tables
(when installing Penny Pool for the first time),
or alter them (when upgrading).</small>
</td>
<td valign=top width="25%" style="border: 1pt solid #aaa; padding: 4pt;">
  <h3>Table prefix</h3>
prefix: <input type=text name="table_prefix" size=16 value="<?
if(@$_POST['table_prefix'])
	echo $_POST['table_prefix'];
else
	echo "pennypool";
?>">
</td> </tr>
<tr><td colspan=2 style="border: 1pt solid #aaa; padding: 4pt;">
<h3>Database root user</h3>
<p align=center<? print_style("", $errs, "root_user"); ?>>
user: <input type=text name="root_user" size=12 value="<?
if(@$_POST['root_user'])
	echo $_POST['root_user'];
else
	echo "root"; 
?>">&nbsp;&nbsp;
password: <input type=password name="root_pass" size=8<?
if(@$_POST['root_pass'])
	echo " value=\"".$_POST['root_pass']."\"";
?>><br><br>
<small>This information is only needed when either a new database
or a new database user is used.</small>
</td>
<td valign=top width="30%" colspan=2 style="border: 1pt solid #aaa; padding: 4pt;">
  <h3>Language</h3>
  <select name="lang">
    <option value="nl"<?
		if(!@$_POST['lang'] || @$_POST['lang']=='nl')
			echo " selected";
	?>>Default (nl)</option>
<?
	$dir=opendir("lang");
	while($file=readdir($dir)) {
		if(!ereg(".php", $file) || $file=="new_lang.php")
			continue;
		$lang=ereg_replace("(.*).php","\\1",$file);
		echo "    <option value=\"$lang\"";
		if(@$_POST['lang'] == $lang)
			echo " selected";
		echo ">$lang</option>\n";
	}
?>
  </select><br><br>
  <small>The default language can be changed by editing
  <code>config.php</code>.</small>
</td></tr>
<tr><td colspan=4 align=center>
<input type=submit value="submit">
<input type=reset value="reset">
</td></tr></table>
</form>
</center>
</body></html>
<?
	exit();
}

?><html><head>
<title>Setup Penny Pool</title>
<link rel=stylesheet title="Penny Pool" href="style.css">
</head>
<?

switch(@$_POST['step']) {
	case 'create_tables':
	if($_POST['db_exist']=='new' ||
			$_POST['user_exist']=='new') {
		$root_user=addSlashes($_POST['root_user']);
		$root_pass=addSlashes($_POST['root_pass']);
		$db_root=mysql_pconnect($_POST['db_host'],$root_user,$root_pass) or
			error("Error opening database",
				"root username and/or password incorrect?");

		if($_POST['db_exist']=='new') {
			mysql_query("CREATE DATABASE ".$_POST['db_name'], $db_root) or
				error("Error creating database \"".
						$_POST['db_name']."\"",
					"An error occurred while creating the new database. ".
					"Perhaps \"".$_POST['db_name']."\" already exists?");
		}
		if($_POST['user_exist']=='new') {
			mysql_select_db('mysql',$db_root);
			mysql_query("INSERT INTO user ".
				"(host,user,password) VALUES ('localhost','".
					addSlashes($_POST['user']).
					"',password('".addSlashes($_POST['pass'])."'))",
					$db_root) or
				error("Error creating user \"".$_POST['user']."\"",
					"An error occurred while creating the new user. ".
					"Perhaps \"".$_POST['user']."\" already exists?");
		}
		mysql_query("INSERT INTO db (host,db,user,select_priv,insert_priv,".
			"update_priv,delete_priv,create_priv,drop_priv,index_priv,".
			"alter_priv) VALUES ('localhost','".
				addSlashes($_POST['db_name'])."','".
				addSlashes($_POST['user']).
				"','Y','Y','Y','Y','Y','Y','Y','Y')",$db_root) or
			error("Error setting permissions",
				"It was not possible to set the permissions for user ".
				"\"".$_POST['user']."\" and database ".
				"\"".$_POST['db_name']."\".");
		mysql_query("FLUSH privileges;",$db_root);
		mysql_close($db_root);
	}
	$db_conn=@mysql_connect($_POST['db_host'],
					addSlashes($_POST['user']),
					addSlashes($_POST['pass'])) or
		error("Error connecting as \"".$_POST['user']."\"",
				"Unable to connect to the database server with ".
				"the given username &amp; password.",
			  array("user", "pass"));
	mysql_select_db($_POST['db_name'], $db_conn) or
		error("Error selecting database \"".$_POST['db_name']."\"",
				"Unable to select the database.",
			  array("db_name"));

	if(@$_POST['table_prefix'])
		$prefix=$_POST['table_prefix']."_";
	else
		$prefix="";
	
	create_tables($prefix, $db_conn);

	mysql_close($db_conn);
?>
<body>
<center>
<div style="width: 70%; text-align: left; border: 1pt solid black; padding: 10pt;">
<h2 style="text-align: center; padding-top: 0pt;">Successfull installation</h2>
The database has been successfully initialized, to let <em>Penny
Pool</em> use it, please copy &amp; paste the following
information into <code>config.php</code> (in the directory where
<em>Penny Pool</em> is installed):
<pre>
&lt;?php
 	$db['host']="<?=$_POST['db_host']?>";
	$db['db']="<?=$_POST['db_name']?>";
	$db['prefix']="<?=$prefix?>";
	$db['user']="<?=$_POST['user']?>";
	$db['passwd']="<?=$_POST['pass']?>";

	$pp['lang']="<?=$_POST['lang']?>";

</pre>

<p>You have to make sure it has the right
permissions, and may want to disable access to
<code>setup.php</code>.  On UNIX this is achieved by
<pre>
    chmod 644 config.php
    chmod 600 setup.php
</pre>

<? if($_POST['user_exist'] == 'new') { ?>
You can now 
<a href="login.php?login=user">start using Penny Pool</a>.  The 
login "user" has been created, with no password.
<? } else { ?>
You can now <a href="login.php" class="plain">start using Penny Pool</a>.
<? } ?>
</div>
</center>
</body></html>
<?
	break;
	default:
		$upgrade=false;
		if(file_exists('config.php'))
		{
			$upgrade=true;

			include_once('config.php');

			$_POST = array();
			$_POST['db_host'] = $db['host'];
			$_POST['db_name'] = $db['db'];
			if(@$db['prefix'])
			{
				$_POST['table_prefix'] = substr($db['prefix'], 0,
									  strrpos($db['prefix'], '_'));
			}
			$_POST['user'] = $db['user'];
			$_POST['pass'] = $db['passwd'];

			$_POST['lang'] = @$pp['lang'];

			$_POST['db_exist'] = 'old';
			$_POST['user_exist'] = 'old';
		}
		form($upgrade);
} ?>
