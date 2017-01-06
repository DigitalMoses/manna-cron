<!DOCTYPE html>
<html ng-app="sputnikApp">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Sputnik</title>

    <script src="<?php echo base_url('assets/bower_components/jquery/dist/jquery.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/jquery-ui/jquery-ui.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/bootstrap/dist/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/datatables.net/js/jquery.dataTables.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/angular/angular.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/angular-animate/angular-animate.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/angular-touch/angular-touch.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/angular-bootstrap/ui-bootstrap-tpls.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/sputnik.js'); ?>"></script>

    <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/datatables.net-dt/css/jquery.dataTables.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/jquery-ui/themes/smoothness/jquery-ui.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap/dist/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap/dist/css/bootstrap-theme.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/sputnik.css'); ?>">
</head>
<body>

<?php if (isset($user)) { ?>
<nav class="navbar navbar-default">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <a class="navbar-brand" href="http://www.facebook.com/<?php echo $user['id'] ?>">
                <img src="<?php echo $user['picture']['url']; ?>"/>
            </a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <ul class="nav navbar-nav">
            <li>
                <a href="http://www.facebook.com/<?php echo $user['id'] ?>">
                    <?php echo $user['name']; ?>
                </a>
            </li>

            <!--
            <li class="active"><a href="<?php echo base_url(); ?>home">Statistic</a></li>
            <li><a href="<?php echo base_url(); ?>home">Settings</a></li>
            -->
        </ul>

        <ul class="nav navbar-nav navbar-right">
            <li><a href="<?php echo base_url(); ?>logout">Log Out</a></li>
        </ul>
    </div><!-- /.container-fluid -->
</nav>
<?php } ?>

<div class="container">
