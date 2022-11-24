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


/**
 * @param $conn
 * @return void
 * @throws \Doctrine\DBAL\Schema\SchemaException
 */
function create_tables(\Doctrine\DBAL\Connection $conn)
{
	$schema = new \Doctrine\DBAL\Schema\Schema();

	$default_date = "0000-00-00";

	$table_act = $schema->createTable("activiteiten");
	$table_act->addColumn("act_id", "integer", ["unsigned" => true, "notnull" => true, "autoincrement" => true]);
	$table_act->addColumn("name", "string", ["length" => 40, "notnull" => true, "default" => ""]);
	$table_act->addColumn("date", "date", ["notnull" => true, "default" => $default_date]);
	$table_act->addColumn("afr_id", "integer", ["unsigned" => true, "notnull" => true, "default" => 0]);
	$table_act->setPrimaryKey(["act_id"]);
	$table_act->addUniqueIndex(["act_id"], "act_id");
	$table_act->addIndex(["name","date"], "name");

	$table_afr = $schema->createTable("afrekeningen");
	$table_afr->addColumn("afr_id", "integer", ["unsigned" => true, "notnull" => true, "autoincrement" => true]);
	$table_afr->addColumn("date", "date", ["notnull" => true, "default" => $default_date]);
	$table_afr->setPrimaryKey(["afr_id"]);
	$table_afr->addUniqueIndex(["afr_id"], "afr_id");

	$table_bet = $schema->createTable("betalingen");
	$table_bet->addColumn("van", "integer", ["notnull" => true, "unsigned" => true]);
	$table_bet->addColumn("naar", "integer", ["notnull" => true, "unsigned" => true]);
	$table_bet->addColumn("datum", "date", ["notnull" => true, "default" => $default_date]);
	$table_bet->addColumn("bedrag", "decimal", ["precision" => 10, "scale" => 2, "default" => 0.00 ]);
	$table_bet->addColumn("afr_id", "integer", ["default" => 0]);
	$table_bet->setPrimaryKey(["van","naar","datum"]);
	$table_bet->addUniqueIndex(["van","naar","datum"], "van_naar_datum");

	$table_dln = $schema->createTable("deelnemers");
	$table_dln->addColumn("act_id", "integer", ["default" => 0]);
	$table_dln->addColumn("pers_id", "integer", ["default" => 0]);
	$table_dln->addColumn("credit", "decimal", ["precision" => 10, "scale" => 2, "default" => 0.00 ]);
	$table_dln->addColumn("aantal", "integer", ["unsigned" => true, "notnull" => true, "default" => 1]);
	$table_dln->setPrimaryKey(["act_id", "pers_id"]);
	$table_dln->addUniqueIndex(["act_id", "pers_id"], "act_pers");

	$table_mns = $schema->createTable("mensen");
	$table_mns->addColumn("pers_id", "integer", ["notnull" => true, "autoincrement" => true]);
	$table_mns->addColumn("voornaam", "string", ["length" => 10, "notnull" => true, "default" => ""]);
	$table_mns->addColumn("achternaam", "string", ["length" => 20, "notnull" => true, "default" => ""]);
	$table_mns->addColumn("rekeningnr", "string", ["length" => 9, "notnull" => true, "default" => ""]);
	$table_mns->addColumn("nick", "string", ["length" => 10, "notnull" => true, "default" => ""]);
	$table_mns->addColumn("email", "string", ["length" => 40, "notnull" => true, "default" => ""]);
	$table_mns->addColumn("password", "string", ["length" => 128, "notnull" => false, "default" => "!"]);
	$table_mns->addColumn("type", "string", ["length" => 20, "notnull" => true, "default" => "person"]);
	$table_mns->addColumn("lang", "string", ["length" => 5, "notnull" => true, "default" => "en"]);
	$table_mns->setPrimaryKey(["pers_id"]);
	$table_mns->addUniqueIndex(["pers_id"], "pers_id");
	$table_mns->addUniqueIndex(["pers_id","nick"], "pers_nick");
	$table_mns->addUniqueIndex(["nick"], "nick");


	/*
	echo "<pre>sqlite\n";
	$foo = $schema->toSql(new Doctrine\DBAL\Platforms\SqlitePlatform);
	var_dump($foo);
	echo "</pre><pre>mysql\n";
	$foo = $schema->toSql(new Doctrine\DBAL\Platforms\MySQL80Platform);
	var_dump($foo);
	echo "</pre>";
	*/
	// TODO: init mensen als: (pers_id,voornaam,nick) VALUES (1,'User','user')


	#$sm = $conn->getSchemaManager();
	$sm = $conn->getDatabasePlatform()->createSchemaManager($conn);
	$schema_old = $sm->introspectSchema();

	$sql = $schema_old->getMigrateToSQL($schema, $conn->getDatabasePlatform());
	foreach ($sql as $stm) {
		$conn->prepare($stm)->executeQuery();
	}

	/* add default user */
	$cnt = $conn->prepare("SELECT COUNT() FROM `mensen`;")->executeQuery()->fetchOne();
	if ($cnt===0) {
		$conn->prepare(
			"INSERT INTO `mensen` (`pers_id`,`voornaam`,`nick`,`password`) VALUES (1,'User','user',null)"
		)->executeQuery();
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
function error($title, $explanation, $fields=array()) {
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
<?php
	if(count($error)) {
?>
<div style="width: 60%; align: center; background-color: white; color: red;
            border: 1pt solid #aaa; padding: 2pt;">
<h2><?= $error['title'] ?></h2>
<p align=center style="font-size: small; color: red">
<?= $error['explanation'] ?></p></div><br>
<?php
	} else if($upgrade) {
?>
<div style="width: 60%; align: center; background-color: white; border: 1pt solid #aaa;
            padding-left: 8pt; padding-right: 8pt;">
<p align=center style="font-size: small;">An existing installation of Penny
Pool / Huisrekening has been detected.  An attempt will be made to upgrade
automatically.  However, success is not guaranteed.  Please do not forget to
make a backup of your existing data.</p></div><br>
<?php
	}
?>
<form method=post action="setup.php">
<input type=hidden name="step" value="create_tables">
<table width="80%" cellpadding=2 style="border: 1pt solid black;">
<tr><td valign=top width="30%" style="border: 1pt solid #aaa; padding: 4pt; padding-bottom: 8pt;">
<h3>Database</h3>
<table align=center>
  <tr>
    <td align=right<?php print_style("", $errs, "db_url");
		?>><label for="db_url">database url:</label></td>
    <td><input type=text name="db_url" id="db_url" size=16 value="<?php
	if(@$_POST['db_url'])
		echo $_POST['db_url'];
	else
		echo "pdo-sqlite://localhost//tmp/pennypool.sqlite";
?>"></td>
  </tr>
  <tr>
    <td colspan=2 style="padding-top: 8pt;">
      <input type=radio name="db_exist" id="db_e_old" value="old" checked/>
		<label for="db_e_old">Existing Database</label>
    </td>
</table>
</td><td valign=top width="45%" colspan=2 style="border: 1pt solid #aaa; padding: 4pt;">
<h3>Database User</h3>
<!-- revise this later
<input type=radio name="user_exist" value="new"<?php
if(@$_POST['user_exist']=='new')
	echo " checked"; ?>>New User<br> -->
<input type=radio name="user_exist" value="old"<?php
if(@$_POST['user_exist']=='old')
	echo " checked"; ?>>Existing User<br>
<input type=radio name="user_exist" value="none"<?php
if(!@$_POST['user_exist'] || @$_POST['user_exist']=='none')
	echo " checked"; ?>>No user<br>
<table>
  <tr>
    <td align=right<?php print_style("", $errs, "user");
		?>><label for="user">user:</label></td>
    <td><input type=text name="user" id="user" size=14 value="<?php
if(@$_POST['user'])
	echo $_POST['user'];
else
	echo "pennypool";
?>"></td>
  </tr>
  <tr>
    <td align=right<?php print_style("", $errs, "pass");
		?>><label for="pass">password:</label></td>
    <td><input type=password name="pass" id="pass" size=8<?php
if(@$_POST['pass'])
	echo " value=\"".$_POST['pass']."\"";
?>></td>
  </tr>
</table><br>
<small>The database user should have permission to create tables
(when installing Penny Pool for the first time),
or alter them (when upgrading).</small>
</td>
</tr>
<tr><!-- revise later
<td colspan=2 style="border: 1pt solid #aaa; padding: 4pt;">
<h3>Database root user</h3>
<p align=center<?php print_style("", $errs, "root_user"); ?>>
user: <input type=text name="root_user" size=12 value="<?php
if(@$_POST['root_user'])
	echo $_POST['root_user'];
else
	echo "root";
?>">&nbsp;&nbsp;
password: <input type=password name="root_pass" size=8<?php
if(@$_POST['root_pass'])
	echo " value=\"".$_POST['root_pass']."\"";
?>><br><br>
<small>This information is only needed when either a new database
or a new database user is used.</small>
-->
</td>
<td valign=top width="30%" colspan=2 style="border: 1pt solid #aaa; padding: 4pt;">
  <h3>Language</h3>
  <select name="lang">
    <option value="nl"<?php
		if(!@$_POST['lang'] || @$_POST['lang']=='nl')
			echo " selected";
	?>>Default (nl)</option>
<?php
	$dir=opendir("lang");
	while($file=readdir($dir)) {
		if(!preg_match(".php", $file) || $file=="new_lang.php")
			continue;
		$lang=preg_replace("(.*).php","\\1",$file);
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
<?php
	exit();
}

?><html><head>
<title>Setup Penny Pool</title>
<link rel=stylesheet title="Penny Pool" href="style.css">
</head>
<?php

switch(@$_POST['step']) {
	case 'create_tables':
		// revise this later
/*
	if($_POST['db_exist']=='new' ||
			$_POST['user_exist']=='new') {
		$root_user=addSlashes($_POST['root_user']);
		$root_pass=addSlashes($_POST['root_pass']);
		$db_root=new PDO($_POST['db_dsn'],$root_user,$root_pass) or
			error("Error opening database",
				"root username and/or password incorrect?");

		if($_POST['db_exist']=='new') {
			$db_root->exec("CREATE DATABASE ".$_POST['db_name'], $db_root) or
				error("Error creating database \"".
						$_POST['db_name']."\"",
					"An error occurred while creating the new database. ".
					"Perhaps \"".$_POST['db_name']."\" already exists?");
		}
		if($_POST['user_exist']=='new') {
			$db_root->exec("INSERT INTO user ".
				"(host,user,password) VALUES ('localhost','".
					addSlashes($_POST['user']).
					"',password('".addSlashes($_POST['pass'])."'))",
					$db_root) or
				error("Error creating user \"".$_POST['user']."\"",
					"An error occurred while creating the new user. ".
					"Perhaps \"".$_POST['user']."\" already exists?");
		}
		$db_root->exec("INSERT INTO db (host,db,user,select_priv,insert_priv,".
			"update_priv,delete_priv,create_priv,drop_priv,index_priv,".
			"alter_priv) VALUES ('localhost','".
				addSlashes($_POST['db_name'])."','".
				addSlashes($_POST['user']).
				"','Y','Y','Y','Y','Y','Y','Y','Y')",$db_root) or
			error("Error setting permissions",
				"It was not possible to set the permissions for user ".
				"\"".$_POST['user']."\" and database ".
				"\"".$_POST['db_name']."\".");
		$db_root->exec("FLUSH privileges;",$db_root);
	}
*/

try {
	$conn_params = ['url' => $_POST['db_url']];
	$db_conn = \Doctrine\DBAL\DriverManager::getConnection($conn_params);
	create_tables($db_conn);
} catch (\Doctrine\DBAL\Exception $e) {
	error("Error connecting to {$_POST['db_url']}",
		"Unable to connect to the database server with " .
		"the given username &amp; password: " .
		$e->getMessage(),
		array("user", "pass"));
}

?>
<body>
<center>
<div style="width: 70%; text-align: left; border: 1pt solid black; padding: 10pt;">
<h2 style="text-align: center; padding-top: 0pt;">Successful installation</h2>
The database has been successfully initialized, to let <em>Penny
Pool</em> use it, please copy &amp; paste the following
information into <code>config.php</code> (in the directory where
<em>Penny Pool</em> is installed):
<pre>
&lt;?php
 	$db['url']="<?=$_POST['db_url']?>";
	$pp['lang']="<?=$_POST['lang']?>";

</pre>

<p>You have to make sure it has the right
permissions, and may want to disable access to
<code>setup.php</code>.  On UNIX this is achieved by
<pre>
    chmod 644 config.php
    chmod 600 setup.php
</pre>

<?php if($_POST['user_exist'] == 'new') { ?>
You can now
<a href="login.php?login=user">start using Penny Pool</a>.  The
login "user" has been created, with no password.
<?php } else { ?>
You can now <a href="login.php" class="plain">start using Penny Pool</a>.
<?php } ?>
</div>
</center>
</body></html>
<?php
	break;
	default:
		$upgrade=false;
		if(file_exists('config.php'))
		{
			$upgrade=true;

			include_once('config.php');
			global $db, $pp;

			$_POST = array();
			$_POST['db_url'] = $db['url'];
			#$_POST['user'] = $db['user'];
			#$_POST['pass'] = $db['passwd'];

			$_POST['lang'] = @$pp['lang'];

			$_POST['db_exist'] = 'old';
			$_POST['user_exist'] = 'old';
		}
		form($upgrade);
} ?>
