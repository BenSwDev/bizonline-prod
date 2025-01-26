function AComplete(opt) {
    var self = this;
    this.cont = $(opt.container || '#searchFreeBox');
    this._builder = opt.builder;
    this._onSelect = opt.onSelect;
    this.suggest = opt.suggest || [];
    this.data = opt.data || [];
    this.q = -1;

	this.cont.find('input[type=text]').on('keydown', function (e) {
        if (e.which == 38) {
            e.preventDefault();
            return self._keysUp();
        }

        if (e.which == 40) {
            e.preventDefault();
            return self._keysDown();
        }

        if (e.which == 13 || e.which == 9) {
            var elem = self.cont.find('.autoBox').children(':visible').find('div.keyActive');
            elem.length && self._onSelect && self._onSelect(elem);
			$('#datesPicker').trigger('click');
			$('.autoSuggest').addClass('hided');
            return self._keysReset();
        }
    }).on('keyup', function (e) {
        if (e.which == 38 || e.which == 40 || e.which == 13 || e.which == 9)
            return false;

        self._init(this);
    }).on('focus', function () {
        self.cont.addClass('active pop');
		$('.autoSuggest').removeClass('hided');

        self._init(this);
    }).on('focusout', function () {
        window.setTimeout(function(){
			self.cont.removeClass('active pop').find('.autoComplete, .autoSuggest').removeClass('show');
		}, 400);
		// window.dispatchEvent(new Event('resize'));
    });

    this.cont.find('.autoComplete').on('click', '> div', function () {
        self._onSelect && self._onSelect(this);
		$('#datesPicker').trigger('click');
		$('.autoSuggest').addClass('hided');
    });

    this.cont.find('.autoSuggest').on('click', 'li', function () {
        self._onSelect && self._onSelect(this);
		$('#datesPicker').trigger('click');
		$('.autoSuggest').addClass('hided');
    });

}
$.extend(AComplete.prototype, {
    _init: function (inp) {
        var value = $(inp).val();
        this._keysReset();
		

        if (! value.length)
            return this._build('.autoSuggest', this.suggest, '');
        this._build('.autoComplete', $.grep(this.data, function(obj){
            return obj.name.includes(value);
        }), value);
    },
    _build: function (selector, data, value) {
        var self = this,
		html = $.map(data, function (v) {
                return self._builder && self._builder(v, self._marker(v.name, value, v.type), selector);
        }).join('').trim();
		
        this.cont.find(selector)[html.length ? 'html' : 'addClass'](html).addClass('show').removeClass('hided').siblings().removeClass('show hided');
		window.dispatchEvent(new Event('resize'));
    },
    _keysUp: function () {
        if (!this.q)
            return false;

        var box = this.cont.find('.autoBox');

        box.children(':visible').find('> div').eq(--this.q).addClass('keyActive').siblings().removeClass('keyActive');
        box.scrollTop(this.q * 32);
    },
    _keysDown: function () {
        var box = this.cont.find('.autoBox'), spans = box.children(':visible').find('> div');
        if (this.q == spans.length - 1)
            return false;

        spans.eq(++this.q).addClass('keyActive').siblings().removeClass('keyActive');

        box.scrollTop(this.q * 32);
    },
    _keysReset: function () {
        this.q = -1;
        this.cont.find('.autoBox').scrollTop(0).find('div').removeClass('keyActive');
    },
    _marker: function (name, value, type) {
        if(!type)
            var type = null;

        var rg = new RegExp(value, 'gi');
        return (value.length ? name.replace(rg, '<b>' + value + '</b>') : name);
    },
    update: function(type, data){
        this[type] = data;
    }
});