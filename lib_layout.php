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


class button
{
	var $type;
	var $value;

	var $id = null;
	var $onclick = null;

	function __construct($value, $type = 'button')
	{
		$this->value = $value;
		$this->type = $type;
	}

	function render($prefix="")
	{
		echo $prefix."<input type={$this->type} value=\"{$this->value}\" ".
				($this->id?"id=\"{$this->id}\" ":"").
				($this->onclick?"onclick=\"{$this->onclick}\" ":"")."\n".
				$prefix."    onmouseover=\"this.setAttribute('class','hover')\" ".
				"onfocus=\"this.setAttribute('class','hover')\" "."\n".
				$prefix."    onmouseout=\"this.removeAttribute('class')\" ".
				"onblur=\"this.removeAttribute('class')\">\n";
	}
}

class form
{
	var $next;

	var $delete = null;
	var $save;
	var $cancel;

	var $action;

	function __construct($next, $delete = true)
	{
		$this->next = $next;

		if($delete)
		{
			$this->delete = new button('delete');
			$this->delete->onclick =
					"document.getElementById('action').value='delete';".
					"this.form.submit();";
		}

		$this->save = new button('save', 'submit');

		$this->cancel = new button('cancel');
		$this->cancel->onclick = "window.close()";

		if($delete)
			$this->action = 'update';
		else
			$this->action = 'insert';
	}

	function head()
	{	?>
<!-- Start Form Header -->
<form id='form' method=post action="<?=$this->next?>">
<input type=hidden name=action id='action' value='<?=$this->action?>'>
<!-- Eind Form Header -->
<?php  }

	function foot()
	{ ?>
<!-- Start Form Footer -->
<table width="80%" align=center>
  <tr>
<?php
		if($this->delete)
		{
			echo "    <td>\n";
			$this->delete->render("      ");
			echo "    </td>\n";
		}
		echo "    <td align=center>\n";
		$this->save->render("      ");
		echo "      &nbsp;&nbsp;\n";
		$this->cancel->render("      ");
		echo "    </td>\n";
	?>
  </tr>
</table>
</form>
<!-- Eind Form Footer -->
<?php
	}
}

class popup
{
	var $title;

	function __construct($title = "")
	{
		if(!$title)
			$title = __("Penny Pool");
		$this->title = $title;
	}

	function head()
	{
?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<link rel=stylesheet title="Penny Pool" href="style.css">
<title><?=$this->title?></title>
</head><body>
<h1 align=center><?=$this->title?></h1>
<?php	}

	function foot()
	{
?></body></html><?php
	}
}

class popup_eval
{
	var $title;
	var $form;
	var $opener;

	var $error = null;

	function __construct($title, $form, $location = null)
	{
		$this->title = $title;
		$this->form = $form;
		if($location)
			$this->opener = $location;
		else
			$this->opener = "index.php";
	}

	function set_error($error)
	{
		$this->error = $error;
	}

	function render_ok()
	{
?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head><title><?=$this->title?></title>
<link rel=stylesheet title="Penny Pool" href="style.css">
<script>
function init() {
  if(window.opener)
    window.opener.location='<?=$this->opener?>'
  window.close()
}
</script>
</head><body onload="init()">
</body></html><?php
	}

	function render_error($vars)
	{
		$back = new button('back', 'submit');
		$back->id = 'back';

		$cancel = new button('cancel');
		$cancel->onclick = "window.close()";

?><!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN"
  "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head><title>Error</title>
<link rel=stylesheet title="Penny Pool" href="style.css">
</head>
<body onload="document.getElementById('back').focus()">
<h1 class="error" align=center><?=__("Ongeldige data")?></h1>
<?php if($this->error) { ?>
<p align=center><font color=red><?=$this->error?></font></p>
<?php } ?>
<form action="<?=$this->form?>" method=post><?php
		foreach($vars as $key => $val)
		{
			echo "<input type=hidden name=\"$key\" value=\"$val\">\n";
		}
?><center><?php
		$back->render();
		echo "&nbsp;&nbsp;";
		$cancel->render();
?></center>
</form>
</body></html><?php
	}
}
