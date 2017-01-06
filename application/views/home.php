<div class="row" ng-controller="homeController">
    <div class="col-md-12">
        <form name="outerForm" class="tab-form-demo">
            <uib-tabset active="activeForm">
                <uib-tab index="0" heading="Settings">
                    <ng-form name="nestedForm">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" class="form-control" required ng-model="model.name"/>
                        </div>
                    </ng-form>
                </uib-tab>
                <uib-tab index="1" heading="Statistics">
                </uib-tab>
            </uib-tabset>
        </form>
    </div>
</div>