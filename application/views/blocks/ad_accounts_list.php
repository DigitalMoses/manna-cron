<div class="sidebar_col">
    <div class="sidebar_col_content">                                
        <a class="item" ng-class="{'selected' : $index == pos}" ng-repeat="ad in ad_accounts track by $index" ng-click="item_clicked($index)">
            <span>
                <i class="fb_icon fb_picture_wrap" ng-if="!!ad.picture['url']"><img ng-src="{{ad.picture['url']}}"/></i>
                <i class="fb_icon fb_icon_ad" ng-if="!ad.picture['url']"></i>
                <span class="fb_value">{{ad.name}}</span>
            </span>
        </a>                                
    </div>
</div>