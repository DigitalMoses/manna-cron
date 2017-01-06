/**
 * Created by Vitalik on 6/22/16.
 */
;(function(){

    "use strict";

    var app = angular.module('sputnikApp',['ui.bootstrap']);

    app.filter('escape', function() {
        return window.encodeURIComponent;
    });

    app.controller("historyController", ['$scope','$http','$timeout', function($scope, $http, $timeout) {

        var config = {
            headers: {
                'Accept': 'application/json;odata=verbose'
            }
        };

        var newline = String.fromCharCode(13, 10);
        var table;
        var isInitTable = false;
        var processing_info = {};

        $scope.isTurnOff = false;
        $scope.textarea_info = '';


        /**************************************************************************************************/
        $scope.today = function() {
            $scope.dt = new Date();
        };
        $scope.today();

        $scope.clear = function() {
            $scope.dt = null;
        };

        $scope.inlineOptions = {
            customClass: getDayClass,
            minDate: new Date(),
            showWeeks: true
        };

        $scope.dateOptions = {
            //dateDisabled: disabled,
            formatYear: 'yyyy',
            maxDate: new Date(),
            minDate: new Date(),
            startingDay: 1
        };

        // Disable weekend selection
        function disabled(data) {
            var date = data.date,
                mode = data.mode;
            return mode === 'day' && (date.getDay() === 0 || date.getDay() === 6);
        }

        $scope.toggleMin = function() {
            $scope.inlineOptions.minDate = $scope.inlineOptions.minDate ? null : new Date();
            $scope.dateOptions.minDate = $scope.inlineOptions.minDate;
        };

        $scope.toggleMin();

        $scope.open = function() {
            $scope.popup.opened = true;
        };

        $scope.setDate = function(year, month, day) {
            $scope.dt = new Date(year, month, day);
        };

        $scope.formats = ['yyyy-MM-dd','dd-MMMM-yyyy', 'yyyy/MM/dd', 'dd.MM.yyyy', 'shortDate'];
        $scope.format = $scope.formats[0];
        $scope.altInputFormats = ['yyyy-M!-d!'];

        $scope.popup = {
            opened: false
        };

        function getDayClass(data) {
            var date = data.date,
                mode = data.mode;
            if (mode === 'day') {
                var dayToCheck = new Date(date).setHours(0,0,0,0);

                for (var i = 0; i < $scope.events.length; i++) {
                    var currentDay = new Date($scope.events[i].date).setHours(0,0,0,0);

                    if (dayToCheck === currentDay) {
                        return $scope.events[i].status;
                    }
                }
            }

            return '';
        }
        /**************************************************************************************************/

        //getAdAccounts();

        $scope.updateAds = function () {
            delete processing_info['current_date'];
            delete processing_info['ads_list'];
            $scope.textarea_info = '';
            getAdsByAdAccountId();
        };

        $scope.start = function() {
            if ($scope.selectedAdAccount != undefined) {
                $scope.isTurnOff = true;    // и на время работы поиска делаем недоступными для редактирования опции поиска
                processing();
            }
        };

        $scope.pause = function() {
            $scope.isTurnOff = false;
            setAdStatus('Stopped');
        };

        function processing() {
            if (!$scope.isTurnOff) return;

            if (processing_info['ads_list'] === undefined) {
                getAdsByAdAccountId();
            } else {
                setAdStatus('Processing');
                if (processing_info['existing_dates'] === undefined) {
                    getAllExistingDatesForAdId();
                }
                else {

                    if (processing_info['ads_index'] < table.rows().count()) {

                        if (processing_info['current_date'] === undefined) {
                            processing_info['current_date'] = dateAdd(new Date(), 'd', -1);
                        }

                        if ($.inArray(processing_info['current_date'], processing_info['existing_dates']) == -1) {
                            // данных с такой датой не обнаружено в базе. Значит можно делать запрос.
                            if (processing_info['current_date'] >= processing_info['ads_list'][processing_info['ads_index']]['created_time'] &&
                                processing_info['current_date'] >= $scope.dt.yyyymmdd()) {
                                getInsightsAndSave();
                            } else {
                                delete processing_info['current_date'];
                                setAdStatus("Complete");
                                processing_info['ads_index'] = processing_info['ads_index'] + 1;
                                processing();
                            }
                        } else {
                            // данные с такой датой уже есть. Пишем об этом сообщение
                            printResultMessage(0);
                            processing_info['current_date'] = dateAdd(processing_info['current_date'], 'd', -1);
                            processing();
                        }

                    } else {
                        // если попали сюда, то значит уже пробежали по всем строкам
                        delete processing_info['ads_list'];
                        delete processing_info['ads_index'];

                        table.destroy();
                        isInitTable = false;
                        $scope.isTurnOff = false;
                    }
                }
            }
        }

        function getAdAccounts() {
            $http.get('History/getAdAccounts', config)
                .then(function(response) {
                    if (response['data']['succeed']) {
                        $scope.ad_accounts_list = response['data']['data'];
                        $scope.selectedAdAccount = $scope.ad_accounts_list[0];
                        getAdsByAdAccountId();
                    } else {
                        printErrorMessage(response['data']['exception']);
                    }
                }, function(reason){
                    alert(reason.data);
                });
        }

        function getAdsByAdAccountId() {
            $http.get('History/getAdsByAdAccountId/' + $scope.selectedAdAccount, config)
                .then(function (response) {
                    if (response['data']['succeed']) {
                        if (response['data']['data']) {
                            for (var i = 0; i < response['data']['data'].length; i++) {
                                response['data']['data'][i]['n'] = i + 1;
                                response['data']['data'][i]['status'] = '';
                            }
                            processing_info['ads_list'] = response['data']['data'];
                            processing_info['ads_index'] = 0;
                            delete processing_info['existing_dates'];

                            if (isInitTable) {
                                table.clear();
                                table.destroy();
                            }

                            table = $("#ads_table").DataTable({
                                data: processing_info['ads_list'],
                                columns: [
                                    {data: 'n'},
                                    {data: 'name'},
                                    {data: 'status'}
                                ],
                                paging: false,
                                searching: false
                            });

                            isInitTable = true;
                        } else {
                            table.clear();
                            table.destroy();
                            isInitTable = false;
                        }

                        processing();
                    } else {
                        printErrorMessage(response['data']['exception']);
                        if (response['data']['exception']['code'] == 17) {
                            $timeout(processing, 120000);
                        }
                    }
                }, function(reason){
                    alert(reason.data);
                });
        }

        function getAllExistingDatesForAdId() {
            var ad_id = processing_info['ads_list'][processing_info['ads_index']]['id'];

            $http.get('History/getAllExistingDatesForAdId/' + ad_id, config)
                .then(function (response) {
                    if (response['data']['succeed']) {
                        processing_info['existing_dates'] = response['data']['data'];
                        processing();
                    } else {
                        printErrorMessage(response['data']['exception']);
                    }
                }, function (reason) {
                    alert(reason.data);
                });
        }

        function getInsightsAndSave() {
            var ad_id = processing_info['ads_list'][processing_info['ads_index']]['id'];
            var looking_date = processing_info['current_date'];
            var created_time = processing_info['ads_list'][processing_info['ads_index']]['created_time'];

            $http.get('History/getInsightsAndSave/' + ad_id + '/' + looking_date + '/' + created_time, config)
                .then(function (response) {
                    if (response['data']['succeed']) {
                        processing_info['current_date'] = dateAdd(processing_info['current_date'], 'd', -1);
                        printResultMessage(response['data']['data']);
                        processing();
                    } else {
                        printErrorMessage(response['data']['exception']);
                        if (response['data']['exception']['code'] == 17) {
                            $timeout(processing, 120000);
                        }
                    }
                }, function (reason) {
                    alert(reason.data);
                });
        }

        function dateAdd(start, interval, number) {
            var buffer = Date.parse(start);
            switch (interval.toLowerCase()) {
                case 'y':
                    number *= 365;	//years to days
                case 'd':
                    number *= 24 ; // days to hours
                case 'h':
                    number *= 60 ; // hours to minutes
                case 'm':
                    number *= 60 ; // minutes to seconds
                case 's':
                    number *= 1000 ; // seconds to milliseconds
                    break ;
                default:
                    break;
            }
            buffer = new Date(buffer + number);
            return buffer.yyyymmdd();
        }

        function setAdStatus(status) {
            var tr = $("#ads_table tbody").find('tr').eq(processing_info['ads_index']);
            var col_status = $(tr).find('td:eq(2)');
            $(col_status).text(status);
            switch (status) {
                case 'Complete':
                    $(col_status).attr('class', 'success');
                    break;
                case 'Stopped':
                    $(col_status).attr('class', 'danger');
                    break;
                default:
                    $(col_status).attr('class', 'warning');
                    break;
            }
        }

        function printResultMessage(msg_code) {
            var msg = 'date: ' + processing_info['current_date'] + newline;
            msg += 'ad name: ' + processing_info['ads_list'][processing_info['ads_index']]['name'] + newline;
            msg += 'ad id: ' + processing_info['ads_list'][processing_info['ads_index']]['id'] + newline;
            msg += 'status: ';
            switch (msg_code) {
                case 0:
                    msg += 'EXIST IN DATABASE';
                    break;
                case 1:
                    msg += 'LOADED';
                    break;
                case 2:
                    msg += 'NO DATA';
                    break;
                default:
                    break;
            }
            msg += newline;

            $scope.textarea_info = getTimeStamp() + msg + newline + $scope.textarea_info;
        }

        function getTimeStamp() {
            var d = new Date();
            var h = d.getHours().toString(); h = (h[1]) ? h : '0' + h[0];
            var m = d.getMinutes().toString(); m = (m[1]) ? m : '0' + m[0];
            var s = d.getSeconds().toString(); s = (s[1]) ? s : '0' + s[0];
            var timestamp = h + ':' + m + ':' + s + '   ***   ' + newline;
            return timestamp;
        }

        function printErrorMessage(data) {
            var msg = 'error code: ' + data['code'] + newline;
            msg += 'message: ' + data['message'] + newline;
            if (data['code'] == 17) {
                msg += 'decision: I will try to get data in 2 min.' + newline;
            }
            $scope.textarea_info = getTimeStamp() + msg + newline + $scope.textarea_info;
        }

    }]);

    app.controller("homeController", ['$scope', '$http', function($scope, $http) {

/*
        $scope.go = function ( path ) {
            $location.path( path );
        };
*/
    }]);
    
})();