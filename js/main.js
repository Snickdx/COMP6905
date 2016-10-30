(function(){
    "use strict";

    var app = angular.module('myApp', ['firebase', 'toastr']);

    // var HOST = "http://snickdx.me/A2Backend/index.php"; production

    var HOST = "http://a2backend";//local

    app.config(function(toastrConfig) {
        angular.extend(toastrConfig, {
            autoDismiss: false,
            containerId: 'toast-container',
            maxOpened: 0,
            newestOnTop: true,
            timeout: 3000,
            positionClass: "toast-bottom-full-width",
            preventDuplicates: false,
            preventOpenDuplicates: false,
            target: 'body'
        });
    });

    app.controller("mainController", ["$http", "$scope", "$firebaseArray", "toastr", function ($http, $scope, $firebaseArray, toastr) {
        // Initialize Firebase
        var config = {
            apiKey: "AIzaSyDmuJBICvJPCpq3D0FPsan7IfzjAXhmz5c",
            authDomain: "comp6950a2.firebaseapp.com",
            databaseURL: "https://comp6950a2.firebaseio.com",
            storageBucket: "comp6950a2.appspot.com",
            messagingSenderId: "686237011839"
        };
        firebase.initializeApp(config);

        var db = firebase.database();

        $scope.users = [
            "John",
            "Bill",
            "Jake"
        ];

        $scope.companies = [
            "Harambe Ltd.",
            "Hugh Mungus Co.",
            "Photo Mart"
        ];

        $scope.input = {
            user : "John",
            time : moment(),
            company: "Harambe Ltd."
        };

        /**
         * View Retrieves all pending requests from all users
         *
             | User | Company      | Timestamp |
             |------|--------------|-----------|
             | John | Harambe Ltd  | 128123243 |
             | Bill | Fotomart Inc | 213434234 |
         */
        $scope.data = $firebaseArray(db.ref("/requests/sent"));

        $scope.print = function(timestamp){
            return moment(timestamp*1000).format("MMM Do YY hh:mm:ss a");
        };

        $scope.now = function(){
            $scope.input.time = moment();
        };

        $scope.unix = function(date){
            return moment(date).unix();
        };

        $scope.cancel = function(item){
            // console.log(item);
            $http({
                method: 'POST',
                url: HOST+"/deleterequest",
                data: "key="+item.$id,
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
            })
                .success(function(data, status){
                    console.log(data);
                    console.log(status);
                    toastr.success('Success', 'Request Cancelled!');
                })
                .error(function(data, status){
                    console.log(data);
                    console.log(status);
                });
            $scope.getStream();
        };

        $scope.send = function(){
            console.log($scope.input);
            $http({
                method: 'POST',
                url: HOST+"/sendrequest",
                data: "user="+$scope.input.user+"&company="+$scope.input.company+"&time="+moment($scope.input.time).unix(),
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
            })
                .success(function(data, status){
                    console.log(data);
                    console.log(status);
                    toastr.success('Success', 'Request Sent!');
                })
                .error(function(data, status){
                    console.log(data);
                    console.log(status);
                });
            $scope.getStream();
        };

        $scope.getStream = function(){
            $http({
                method:'GET',
                url: HOST+'/getstream'
            }).success(function(data){
                $scope.logs = data;
                console.log(data);
            });
        };

        $scope.getStream();

        $scope.refresh = function(){
            $http({
                method: 'POST',
                url: HOST+"/playbackStream",
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
            })
                .success(function(data, status){
                    console.log(data);
                    console.log(status);
                    toastr.success('The view is now up to date with the event stream', 'View Restored!');
                })
                .error(function(data, status){
                    console.log(data);
                    console.log(status);
                });
        };


        $scope.destroy = function(){
            $http({
                method: 'POST',
                url: HOST+"/destroyView",
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
            })
                .success(function(data, status){
                    console.log(data);
                    console.log(status);
                    toastr.success('You can restore it by using the event logs', 'View destroyed!');
                })
                .error(function(data, status){
                    console.log(data);
                    console.log(status);
                });
        };


    }]);
})();