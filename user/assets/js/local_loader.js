(function($){
    $.fn.localLoader = function(action){
        return this.each(function(){
            var self = $(this), man = self.data('_localLoader');

            if (!man){
                man = $('<div class="local-loader"><div class="loaderContainer"><div class="lds"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div></div>').appendTo(self);
                $(this).data('_localLoader', man);
            }

            switch(action){
                case 'hide': man.hide(); break;
                case 'poof': man.remove(); $(this).data('_localLoader', null); break;
                default: man.show(); break;
            }
        });
    };
})(jQuery);
