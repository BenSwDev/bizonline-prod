function WebManager(opt){
    var self = this;
    this.cont = $(opt.container || '#container');
    this.cities = [];
    this.areas = [];
    this.sites = [];
    this.facilities = [];
    this.searchArray = [];

    this._getLocations();
}

$.extend(WebManager.prototype, {
    _getLocations:function(){
        var self = this;
        $.get("../../sitesDb.php?langID=",{langID:langID}, function (res) {
            if(!res)
                return false;
            if(res.areas){
                $.each(res.areas, function (key, data) {
                    data.type = 1;
                    self.areas.push(data);
                    self.searchArray.push(data);
                });
            }
            if(res.cities){
                $.each(res.cities, function (key, data) {
                    data.type = 2;
                    self.cities.push(data);
                    self.searchArray.push(data);
                });
            }
            if(res.mainAreas){
                $.each(res.mainAreas, function (key, data) {
                    data.type = '4';
                    //self.sites.push(data);
                    self.searchArray.push(data);
                });
            }
            if(res.facilities){
                $.each(res.facilities, function (key, data) {
                    data.type = 3;
                    self.facilities.push(data);
                    self.searchArray.push(data);
                });
            }
        }, 'json');
    }
});

var WebHandler = new WebManager({
   container: '#container'
});

/*
function siteNameWebManager(opt){
    var self = this;
    this.cont = $(opt.container || '#container');
    this.sites = [];
    this.siteNamesearchArray = [];

    this._getSites();
}

$.extend(siteNameWebManager.prototype, {
    _getSites:function(){
        var self = this;
		
        $.get("../sitesDb.php", function (res) {
            if(!res)
                return false;
            if(res.sites){
                $.each(res.sites, function (key, data) {
                    data.type = 'site';
                    self.sites.push(data);
                    self.siteNamesearchArray.push(data);
                });
            }
        }, 'json');
    }
});


var siteNameWebHandler = new WebManager({
   container: '#container'
});
*/