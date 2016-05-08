
App.factory('SetMeal', function($rootScope, $http, Url) {

    var factory = {};

    factory.value_id = null;

    factory.findAll = function() {

        if(!this.value_id) return;

        return $http({
            method: 'GET',
            url: Url.get("catalog/mobile_setmeal_list/findall", {value_id: this.value_id}),
            cache: !$rootScope.isOverview,
            responseType:'json'
        });
    };

    factory.find = function(set_meal_id) {

        if(!this.value_id) return;

        var url = Url.get('catalog/mobile_setmeal_view/find', {set_meal_id: set_meal_id, value_id: this.value_id});

        return $http({
            method: 'GET',
            url: url,
            cache: !$rootScope.isOverview,
            responseType:'json'
        });
    };

    return factory;
});
