App.config(function($routeProvider) {

    $routeProvider.when(BASE_URL+"/media/mobile_gallery_image_list/index/value_id/:value_id", {
        controller: 'ImageListController',
        templateUrl: BASE_URL+"/media/mobile_gallery_image_list/template",
        code: "image"
    });

}).controller('ImageListController', function($window, $scope, $routeParams, Url, Sidebar, ImageGallery, Image) {

    $scope.$watch("isOnline", function(isOnline) {
        $scope.has_connection = isOnline;
        if(isOnline) {
            $scope.loadContent();
        }
    });

    $scope.gallery = ImageGallery;
    $scope.enable_load_onscroll = true;
    $scope.sidebar = new Sidebar("image");
    $scope.is_loading = false;
    $scope.images = new Array();
    $scope.show_loader_more = false;
    $scope.value_id = Image.value_id = $routeParams.value_id;
    $scope.template_view = Url.get("/media/mobile_gallery_image_view/template");

    $scope.loadContent = function() {

        if($scope.is_loading) return;

        $scope.is_loading = true;
        $scope.sidebar.is_loading = true;

        Image.findAll().success(function(data) {

            $scope.sidebar.reset();

            $scope.header_right_button = {
                action: function() {
                    if(!$scope.sidebar.current_item) return;
                    $scope.sidebar.show = !$scope.sidebar.show;
                },
                picto_url: data.header_right_button.picto_url,
                hide_arrow: true
            };

            $scope.sidebar.collection = data.galleries;
            $scope.page_title = data.page_title;
            $scope.sidebar.showFirstItem(data.galleries);

        }).finally(function() {
            $scope.is_loading = false;
        });
    }

    $scope.sidebar.showItem= function(item) {

        if($scope.sidebar.current_item == item) return;
        $scope.sidebar.current_item = null;
        $scope.loadItem(item, 1);

    };

    $scope.loadItem = function(item, offset) {

        $scope.sidebar.is_loading = true;

        item.current_offset = offset;
        $scope.sidebar.show = false;
        Image.find(item).success(function(data) {

            if(!$scope.sidebar.current_item) {
                $scope.sidebar.current_item = item;
                $scope.collection = data.images;
            } else {
                for(var i = 0; i < data.images.length; i++) {
                    $scope.collection.push(data.images[i]);
                }
            }

            if(!data.images.length) {
                $scope.enable_load_onscroll = false;
            }

            $scope.show_loader_more = false;
            $scope.sidebar.is_loading = false;

        }).error(function() {

        }).finally(function() {
            $scope.is_loading = false;
        });
    };

    $scope.loadMore = function() {
        if(!$scope.show_loader_more) {
            $scope.show_loader_more = true;
            var offset = $scope.collection[$scope.collection.length-1].offset+1;
            $scope.loadItem($scope.sidebar.current_item, offset);
        }
    };

    $scope.showGallery = function(index) {
        $scope.gallery.show($scope.collection, index);
    }

    $scope.loadContent();

});