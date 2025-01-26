var cnt = 0, but = [];

function checkForm(tab)
{
	switch(tab){
		case 'a':
			var a = document.getElementById('newarea'), m = document.getElementById('mar');
			if (!a.value){
				alert('Please enter area name');
				a.focus();
				return false;
			}
			if (!parseInt(m.value)){
				alert('Please choose region');
				m.focus();
				return false;
			}
		break;
		
		case 's':
			var s = document.getElementById('newsett'), a = document.getElementById('sarea');
			if (!s.value){
				alert('Please enter settlement name');
				s.focus();
				return false;
			}
			if (!parseInt(a.value)){
				alert('Please choose country');
				a.focus();
				return false;
			}
		break;
	}
	return true;
}

function tab_edit(name)
{
	var i = name.substr(0,1), id = parseInt(name.substr(1)), tr = document.getElementById('tr'+name), td;
	but[++cnt] = {'tab':i, 'id':id, 'name':name};
	
	switch(i){
		case 'a':
			td = tr.cells.item(1);
			//but[cnt]['click'] = td.onclick;
			//td.onclick = null;
			td.innerHTML = '<input type="text" class="inptText" name="tmp'+cnt+'" id="tmp'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(2);
			td.innerHTML = '<input type="text" class="inptText" name="eng'+cnt+'" id="eng'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(3);
			td.innerHTML = '<input type="text" class="inptText" name="fra'+cnt+'" id="fra'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(4);
			td.innerHTML = gen_select('mar',cnt,td.innerHTML);
			td = tr.cells.item(5);
			but[cnt]['edit'] = td.innerHTML;
			td.innerHTML = '<input type="button" class="submit" value="שמור" onClick="tab_finish('+cnt+')">';
		break;
		
		case 's':
			td = tr.cells.item(1);
			td.innerHTML = '<input type="text" class="inptText" name="tmp'+cnt+'" id="tmp'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(2);
			td.innerHTML = '<input type="text" class="inptText" name="eng'+cnt+'" id="eng'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(3);
			td.innerHTML = '<input type="text" class="inptText" name="fra'+cnt+'" id="fra'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(4);
			td.innerHTML = gen_select('sarea',cnt,td.innerHTML);
			td = tr.cells.item(5);
			td.innerHTML = '<input type="text" class="inptText" name="gps'+cnt+'" id="gps'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(6);
			but[cnt]['edit'] = td.innerHTML;
			td.innerHTML = '<input type="button" value="שמור"  class="submit" onClick="tab_finish('+cnt+')">';
		break;
		
		case 'm':

			td = tr.cells.item(1);
			td.innerHTML = '<input type="text" class="inptText" name="tmp'+cnt+'" id="tmp'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(2);
			td.innerHTML = '<input type="text" class="inptText" name="eng'+cnt+'" id="eng'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(3);
			td.innerHTML = '<input type="text" class="inptText" name="fra'+cnt+'" id="fra'+cnt+'" value="'+td.innerHTML+'">';
			td = tr.cells.item(4);
			but[cnt]['edit'] = td.innerHTML;
			td.innerHTML = '<input type="button" value="שמור"  class="submit" onClick="tab_finish('+cnt+')">';
		break;
	}
}


function gen_select(oid,ind,txt)
{
	var o = document.getElementById(oid), res = '<select class="sel" name="smp'+ind+'" id="smp'+ind+'">', i, tmp;
	for(i=1; i<o.options.length; i++){
		tmp = o.options[i];
		res += '<option value="'+tmp.value+'" '+(tmp.text == txt ? 'selected' : '')+'>'+tmp.text+'</option>';
	}
	return res+'</select>';
}

function tab_finish(curr)
{


	var o = but[curr];

	if (!o){
		alert('Internal error !!!');
		return;
	}

	var inp = document.getElementById('tmp'+curr), eng = document.getElementById('eng'+curr), rus = document.getElementById('rus'+curr), fra = document.getElementById('fra'+curr);
	var smp = document.getElementById('smp'+curr);
	if(!document.getElementById('smp'+curr)){
	var areaSend = "0";
	} else {
	var areaSend = smp.value;
	}
	var par = {'index':curr, 'tab':o.tab, 'id':o.id, 'name':inp.value, 'name_eng':eng.value, 'name_fra':fra.value, 'sarea':areaSend};
	if (o.tab == 's'){
		inp = document.getElementById('gps'+curr);
		par['gps'] = inp.value;
	}
	//JsHttpRequest.query('js_update_map.php',par,js_done,true);
	$.post('js_update_map.php',par,js_done,'json');
}

function js_done(res,err)
{
	if(parseInt(res.status)){
		alert('Error '+res.status+': '+err);
		return;
	}
	var o = but[res.index];
	if (!o){
		alert('Internal error !!!');
		return;
	}
	
	var tr = document.getElementById('tr'+o.name);

	switch(o.tab){
		case 'a':
			td = tr.cells.item(1);
			td.innerHTML = res.newname;
			td = tr.cells.item(2);
			td.innerHTML = res.neweng;
			td = tr.cells.item(3);
			td.innerHTML = res.newfra;
			td = tr.cells.item(4);
			td.innerHTML = res.newarea;
			td = tr.cells.item(5);
			td.innerHTML = o.edit;
			//window.location.reload();
		break;
		
		case 's':
			td = tr.cells.item(1);
			td.innerHTML = res.newname;
			td = tr.cells.item(2);
			td.innerHTML = res.neweng;
			td = tr.cells.item(3);
			td.innerHTML = res.newfra;
			td = tr.cells.item(4);
			td.innerHTML = res.newarea;
			td = tr.cells.item(5);
			td.innerHTML = res.newgps;
			td = tr.cells.item(6);
			td.innerHTML = o.edit;
		break;
		
		case 'm':
			td = tr.cells.item(1);
			td.innerHTML = res.newname;
			td = tr.cells.item(2);
			td.innerHTML = res.neweng;
			td = tr.cells.item(3);
			td.innerHTML = res.newfra;
			td = tr.cells.item(4);
			td.innerHTML = o.edit;
			//window.location.reload();
		break;
	}
	but[res.index] = null;
}
