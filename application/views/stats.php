<script>
    var server = "<?php echo base_url(); ?>";
    var config = {
                headers: {
                    'Accept': 'application/json;odata=verbose'
                }
            };    
    var fb_date = new Date();
    fb_date.setDate(fb_date.getDate() - 1);
    
    var table;
    function get_insights(account_id, fb_date) {
        var url = server + 'Cron/get_insights/' + account_id + '/' + fb_date;
        
        fb_block(".main");
        if (typeof table == "undefined") {
            table = $(".fb_datatable").DataTable({                        
                    "processing": false,
                    "serverSide": true,
                    "ajax": {
                        "type" : "GET",
                        "url" : url,
                        "dataSrc": function ( json ) {
                            fb_unblock(".main");
                            return json.data;
                        }       
                    },                            
                    "lengthMenu": [ 10, 15, 25, 50, 75, 100 ],
                    "searching": false,
                    "ordering": false,                            
                    "columns": [                                
                        { "data": "id" },
                        { "data": "ad_name" },
                        { "data": "spend" },
                        { "data": "impressions" },
                        { "data": "unique_clicks" },
                    ],
                    "pageLength": 10,
                    "rowCallback": function( row, data, index ) {
                        $('td:eq(0)', row).html( index + 1 );
                        //console.log(data);
                        $('td:eq(2)', row).html( parseFloat(Math.round(data["spend"] * 100) / 100).toFixed(2) );
                    }
                });
            
        } else {
            table.ajax.url( url ).load(function(response) {
                table.draw();
                //console.log(response);
            });
        }
    }
    
    angular.module('AdAccountsModule', [])
        .controller('AdAccountsController', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
            
            $scope.ad_accounts = <?php echo json_encode($ad_accounts); ?>;
            $scope.pos = 0;                        
            $scope.current = $scope.ad_accounts[$scope.pos];                        
            $scope.publishing = 0;                                   
            
            $.fn.dataTable.ext.errMode = 'throw';
            
            get_insights($scope.current.id, fb_date.yyyymmdd());
            
            $scope.item_clicked = function(n) {
                $scope.pos = n;
                $scope.current = $scope.ad_accounts[n];                
                get_insights($scope.current.id, $("#fb_datepicker").val());
            };
            
            $scope.import = function() {
                $scope.publishing = 1;                             
                var url = server + 'Cron/import_insights/' + $scope.current.id + '/' + $("#fb_datepicker").val();
                
                fb_block(".main");
                $http.get(url, config)
                .success(function (response) {
                    $scope.publishing = 0;
                    fb_unblock(".main");
                    alert("Done!");
                    console.log(response);
                    get_insights($scope.current.id, $("#fb_datepicker").val()); 
                    //location.reload();
                })
                .error(function (data, status, headers, config) {
                    console.log(data);
                });
            }
            
        }]);
    
    jQuery(document).ready(function($) {
        $( "#fb_datepicker" ).datepicker({
            dateFormat: "yy-mm-dd",
            showOn: "both",
            buttonImage: base_url + "assets/images/calendar.png",
            maxDate: new Date(),
            onSelect: function(dateText, inst) {
                var a = $("#adaccount_id").val();
                get_insights(a, $(this).val());
            }
        });
        
        $("#fb_datepicker").datepicker( "setDate", fb_date);
    });
    
     
</script>


<div class="right" ng-app="AdAccountsModule" ng-controller="AdAccountsController">
    <div class="right_content">
        <?php $this->load->view("blocks/nav"); ?>        
        <div class="main_section">
            <?php $this->load->view("blocks/ad_accounts_list"); ?>
            <div class="main_col">
                <div class="main_col_header clearfix" ng-if="!!ad_accounts.length">
                    <div class="main_icon_wrap">
                        <i class="fb_icon fb_picture_wrap" ng-if="!!current.picture['url']"><img ng-src="{{current.picture['url']}}"/></i>
                        <i class="fb_icon fb_icon_ad" ng-if="!current.picture['url']"></i>                        
                    </div>
                    <div class="main_info">
                        <div class="main_title">{{current.name}}</div>
                        <div class="main_info_body">                            
                            <ul class="info_list"><li></li><li style="padding-left: 0px;"><a target="_blank" ng-href="{{current.link}}">{{current.link}}</a></li></ul>
                            <ul class="info_list"><li>Ad account #:</li><li>{{current.id}}</li></ul>
                            <ul class="info_list"><li>Owned by:</li><li>{{current.users[0]['name']}}</li></ul>
                            <ul class="info_list"><li>Currency:</li><li>{{current.currency}}</li></ul>
                            <ul class="info_list"><li>Time zone:</li><li>{{current.time_zone}}</li></ul>
                        </div>
                    </div>
                    <div class="import_wrap right_side">
                        <div class="fb_row">
                            <span class="datepicker_wrap"><span class="fb_label">Date: </label><input type="text" class="datepicker" id="fb_datepicker"></span></span>
                        </div>
                        <div class="fb_row" style="display: none;">
                            <a class="btn import" ng-class="{'disabled' : publishing == 1}" ng-click="import()">{{ publishing === 0 ? "Import" : "Importing Now"}}</a>    
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="main_col_body">
                    <table class="fb_datatable" id="ads_table">
                        <thead>
                            <tr>
                                <th class="num_col">#</th>
                                <th class="ad_name_col">Ad Name</th>
                                <th class="spend_col">Spend</th>
                                <th class="spend_col">Impressions</th>
                                <th class="spend_col">Unique Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!--<tr ng-repeat="ad in ads track by $index">
                                <td>{{$index + 1}}</td>
                                <td>{{ad.ad_id}}</td>
                            </tr>-->
                        </tbody>
                        
                    </table>
                </div>
            </div>
        </div>
    
        <input type="hidden" id="adaccount_id" value="{{current.id}}"/>
    </div>
</div>
        