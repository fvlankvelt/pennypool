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


global $_lib_cal_jslib_written;
$_lib_cal_jslib_written=false;

class calendar {
	var $prefix='cal_';
	var $field='';
	var $value='';

	function calendar($field,$value='',$prefix='cal_') {
		$this->field=$field;
		if(!$value)
			$this->value=date('d-n-Y');
		else
			$this->value=$value;
		$this->prefix=$prefix;
	}

	function render_js() {
		if(!$GLOBALS['_lib_cal_jslib_written'])
			$this->render_js_lib();
		$prefix=$this->prefix;
	}

	function render_js_init() {
?>
  new calendar('<?=$this->prefix?>','<?=$this->value?>')
<?php
	}

	function render_js_lib() {
		$GLOBALS['_lib_cal_jslib_written']=true;
?>
<script language="JavaScript">
calendar=function(prefix,date) {
  var date_ar=date.split(/-/)

  this.prefix=prefix
  this.year=cal_parseInt(date_ar[2])
  this.month=cal_parseInt(date_ar[1])-1

  this.cont=document.getElementById(prefix+'container')
  this.cont.obj=new Object()
  this.cont.obj.cal=this
  this.cont.onmouseover=function(e) {
    this.obj.cal.show(true)
  }
  this.cont.onmouseout=function(e) {
    this.obj.cal.show(false)
  }

  this.field=document.getElementById(prefix+'date')
  this.field.obj=new Object()
  this.field.obj.cal=this
  this.set_input(cal_parseInt(date_ar[0]))
  this.field.onchange=function(e) {
    var date=this.value.split('-')
    var cal=this.obj.cal
    if(date.length!=3) {
      this.value=this.obj.oldvalue
      return
    }
    cal.year=cal_parseInt(date[2])
    cal.month=cal_parseInt(date[1])-1
    cal.update()
    cal.set_input(cal_parseInt(date[0]))
  }

  this.popup=document.getElementById(prefix+'popup')
  this.head=document.getElementById(prefix+'head')

  this.prev=document.getElementById(prefix+'prev')
  this.prev.obj=new Object()
  this.prev.obj.cal=this
  this.prev.onclick=function(e) {
    if(this.obj.cal.month--<1) {
      this.obj.cal.month+=12
      this.obj.cal.year--
    }
    this.obj.cal.update()
    this.obj.cal.show(true)
    e.cancelBubble=true
  }
  this.prev.onfocus=function(e) {
    this.obj.cal.show(true)
  }
  this.prev.onblur=function(e) {
    this.obj.cal.show(false)
  }

  this.next=document.getElementById(prefix+'next')
  this.next.obj=new Object()
  this.next.obj.cal=this
  this.next.onclick=function(e) {
    if(this.obj.cal.month++>10) {
      this.obj.cal.month-=12
      this.obj.cal.year++
    }
    this.obj.cal.update()
    this.obj.cal.show(true)
    e.cancelBubble=true
  }
  this.next.onfocus=function(e) {
    this.obj.cal.show(true)
  }
  this.next.onblur=function(e) {
    this.obj.cal.show(false)
  }

  this.table=new Array()

  var row=0,col=0
  this.table[0]=new Array()
  while(!(row==5 && col==7)) {
    if(col==7) {
      col=0
      row++
      this.table[row]=new Array()
    }
    var td=document.getElementById(prefix+'td_'+row+'_'+col)
    td.obj=new Object()
    td.obj.cal=this
    td.obj.row=row
    td.obj.col=col
    this.table[row][col]=td
    td.onmouseover=cal_mouseover
    td.onmouseout=cal_mouseout
    td.onclick=cal_click
    col++
  }
  this.update()
}
calendar.prototype.update=function() {
  var months=new Array('jan','feb','mar','apr','mei','jun','jul','aug',
            'sep','okt','nov','dec')
  var row=0,col=0
  while(!(row==5 && col==7)) {
    if(col==7) {
      col=0
      row++
    }
    var td=this.table[row][col]
    var day=this.day(row,col)
    if(day>0 && day<=this.days())
      td.innerHTML=day
    else
      td.innerHTML='&nbsp;'
    col++
  }
  this.head.innerHTML=months[this.month]+' '+this.year
}
calendar.prototype.day=function(row,col) {
  var date=new Date(this.year,this.month,1)
  return 7*row+col-date.getDay()+1
}
calendar.prototype.days=function() {
  var days=new Array(31,28,31,30,31,30,31,31,30,31,30,31)
  if(this.month!=1)
    return days[this.month]
  if(this.year%4==0 && !(this.year%100==0 && this.year%400!=0)) {
    return 29
  } else
    return 28
}
calendar.prototype.set_input=function(day) {
  var d=day
  var m=this.month+1

  if(d<10)
    d='0'+d
  if(m<10)
    m='0'+m
  this.field.value=d+'-'+m+'-'+this.year
  this.field.obj.oldvalue=this.field.value
}
calendar.prototype.get_input=function() {
}
calendar.prototype.show=function(bool) {
  if(bool) {
    this.cont.style.height='120px'
  } else {
    this.cont.style.height='18px'
  }
}

function cal_mouseover(e) {
  cal=this.obj.cal
  var day=cal.day(this.obj.row,this.obj.col)
  if(day>0 && day<=cal.days()) {
    this.style.backgroundColor='#cccccc'
  }
  this.obj.cal.show(true)
}
function cal_mouseout(e) {
  cal=this.obj.cal
  var day=cal.day(this.obj.row,this.obj.col)
  if(day>0 && day<=cal.days()) {
    this.style.backgroundColor='transparent'
  }
}
function cal_click(e) {
  cal=this.obj.cal
  var day=cal.day(this.obj.row,this.obj.col)
  if(day>0 && day<=cal.days()) {
    cal.set_input(day)
  }
  e.cancelBubble=true
}

function cal_parseInt(s) {
  var n=s

  if(!n)
    return 0
  while(n.substr(0,1)=='0')
    n=n.substr(1)
  if(n.length==0)
    return 0
  return parseInt(n)
}

</script>
<?php  }

	function render_html() {
		$prefix=$this->prefix; ?>
<!-- Start Kalender -->
<div style="position: absolute; border: 0px; overflow: hidden; height: 18px;
    width: 108px; z-index: 10;" id='<?=$prefix?>container'>
<input type=text id='<?=$prefix?>date' name="<?=$this->field?>" size=12
    style="font-size: 80%; height: 18px;" value="<?=$this->value?>">
<div id="<?=$prefix?>popup" style="position: absolute; height: 98px;
    border: 1px solid; width: 106px; top: 18px; left: 0px;
    background-color: #eeeeee; font-size: 6px; text-align: center;">
<a id="<?=$prefix?>prev">&lt;&lt;</a>&nbsp;<span id='<?=$prefix?>head'></span>&nbsp;<a id="<?=$prefix?>next">&gt;&gt;</a>
<table id='<?=$prefix?>table' border=0 cellspacing=0 width="90"
    cellpadding=0 align=center style="font-size: 8pt;">
  <tr>
    <th class=cal>z</th>
    <th class=cal>m</th>
    <th class=cal>d</th>
    <th class=cal>w</th>
    <th class=cal>d</th>
    <th class=cal>v</th>
    <th class=cal>z</th>
  </tr>
<?php
for($i=0;$i<6;$i++) {
	echo "  <tr>\n";
	for($j=0;$j<7;$j++) {
		echo "    <td class=\"cal\" id=\"{$this->prefix}td_{$i}_{$j}\">&nbsp;</td>\n";
	}
	echo "  </tr>\n";
}
?></table></div>
</div>
<!-- Eind  Kalender -->
<?php	}
}
