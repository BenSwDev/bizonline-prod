var psp = [];

function period_edit(id, pre, key, ind)
{
	var param = {'act':'show', 'pid':id, 'pre':pre, 'key':key};
	if (ind) {
		param.act = 'update';
		param = make_param(param,ind);
	}
	JsHttpRequest.query('/cms/sites/js_period.php',param,period_end,true);
}

function period_end(res,err)
{
	if (parseInt(res['status'])){
		alert('Error '+res['status']+': '+err);
		return false;
	}
	var o = document.getElementById('pspan'+res['id']);
	if (o){
		if (res['save'])
			psp[res['id']] = o.innerHTML;
		o.innerHTML = err;
	}
}

function period_reset(id)
{
	var o = document.getElementById('pspan'+id);
	if (psp[id]) {
		o.innerHTML = psp[id];
		psp[id] = null;
	}
}

function add_period(name, id)
{
	var param = make_param([],1), line = '';
	for(i in param)
		for(j=0; j<param[i].length; j++)
			line += '&'+i+'%5B'+j+'%5D='+param[i][j];
	location.href = '?act=newper&'+name+'='+id+line;
}

function make_param(out, ind)
{
	var sub = ['y','m','d'], param = out, tmp, obj, base = ['from','to'];
	for(i=0; i<base.length; i++){
		tmp = i + parseInt(ind);
		param[base[i]] = [];
		for(j in sub){
			obj = document.getElementById(sub[j]+tmp);
			param[base[i]][param[base[i]].length] = obj.value;
		}
		param['pName'] = document.getElementById('pname'+ind).value;
	}
	return param;
}
