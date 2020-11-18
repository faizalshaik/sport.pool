<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FlexAR">
    <meta name="author" content="Lance Bunch">

    <!-- <link rel="shortcut icon" href="<?php echo base_url('assets/images/favicon.ico'); ?>"> -->

    <title>Admin</title>
    <?php if ($kind == 'table') { ?>
        <link href="<?php echo base_url('assets/plugins/datatables/jquery.dataTables.min.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url('assets/plugins/datatables/buttons.bootstrap.min.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url('assets/plugins/datatables/responsive.bootstrap.min.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url('assets/plugins/datatables/scroller.bootstrap.min.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url('assets/plugins/datatables/dataTables.bootstrap.min.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url('assets/plugins/datatables/fixedColumns.dataTables.min.css'); ?>" rel="stylesheet" type="text/css" />
    <? } ?>
    <link href="<?php echo base_url('assets/plugins/bootstrap-sweetalert/sweet-alert.css'); ?>" rel="stylesheet" type="text/css">
    <link href="<?php echo base_url('assets/plugins/morris/morris.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/core.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/components.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/icons.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/pages.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/responsive.css'); ?>" rel="stylesheet" type="text/css" />



    <!-- HTML5 shim and Respond.js IE8 support of HTML5 tooltipss and media queries -->
    <!--[if lt IE 9]>
		  <script src="<?php echo base_url('js/html5shiv.js'); ?>"></script>
		  <script src="<?php echo base_url('js/respond.min.js'); ?>"></script>
		<![endif]-->
    <script src="<?php echo base_url('assets/js/modernizr.min.js'); ?>"></script>
    <script>
        var resizefunc = [];
    </script>
    <!-- jQuery  -->
    <script src="<?php echo base_url('assets/js/jquery.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/jquery.form.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/detect.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/fastclick.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/jquery.slimscroll.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/jquery.blockUI.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/waves.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/wow.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/jquery.nicescroll.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/jquery.scrollTo.min.js'); ?>"></script>

    <!-- Custom box scc -->
    <link href="<?php echo base_url('assets/plugins/custombox/css/custombox.css'); ?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/core.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/components.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/icons.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/pages.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/css/responsive.css'); ?>" rel="stylesheet" type="text/css" />

    <!-- date range picker-->
    <link href="<?php echo base_url('assets/plugins/bootstrap-daterangepicker/daterangepicker.css'); ?>" rel="stylesheet">

    <script src="<?php echo base_url('assets/plugins/waypoints/lib/jquery.waypoints.js'); ?>"></script>
    <script src="<?php echo base_url('assets/plugins/counterup/jquery.counterup.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/plugins/parsleyjs/parsley.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/plugins/notifyjs/js/notify.js'); ?>"></script>
    <script src="<?php echo base_url('assets/plugins/notifications/notify-metro.js'); ?>"></script>
    <script src="<?php echo base_url('assets/plugins/bootstrap-sweetalert/sweet-alert.min.js'); ?>"></script>
    <?php if ($kind == 'table') { ?>
        <script src="<?php echo base_url('assets/plugins/datatables/jquery.dataTables.min.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/dataTables.bootstrap.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/dataTables.buttons.min.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/buttons.bootstrap.min.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/jszip.min.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/pdfmake.min.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/vfs_fonts.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/buttons.html5.min.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/buttons.print.min.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/dataTables.responsive.min.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/responsive.bootstrap.min.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/dataTables.colVis.js'); ?>"></script>
        <script src="<?php echo base_url('assets/plugins/datatables/dataTables.fixedColumns.min.js'); ?>"></script>
    <? } ?>

    <script src="<?php echo base_url('assets/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/plugins/bootstrap-daterangepicker/daterangepicker.js'); ?>"></script>


    <?php if ($kind == 'editor') { ?>
        <script src="<?php echo base_url('assets/plugins/tinymce/tinymce.min.js'); ?>"></script>
    <? } ?>

    <script src="<?php echo base_url('assets/js/jquery.core.js'); ?>"></script>

</head>

<body class="fixed-left">
    <!-- Begin page -->
    <div id="wrapper">
        <!-- Top Bar Start -->
        <div class="topbar">

            <!-- LOGO -->
            <div class="topbar-left">
                <div class="text-center">
                    <a href="<?php echo base_url() . 'Cms/dashboard' ?>" class="logo"><i class="icon-camrecorder icon-c-logo"></i><span>Admin</span></a>
                    <!-- Image Logo here -->
                    <!--<a href="index.html" class="logo">-->
                    <!--<i class="icon-c-logo"> <img src="assets/images/logo_sm.png" height="42"/> </i>-->
                    <!--<span><img src="assets/images/logo_light.png" height="20"/></span>-->
                    <!--</a>-->
                </div>
            </div>

            <!-- Button mobile view to collapse sidebar menu -->
            <div class="navbar navbar-default" role="navigation">
                <div class="container">
                    <div class="">
                        <div class="pull-left">
                            <button class="button-menu-mobile open-left waves-effect waves-light">
                                <i class="md md-menu"></i>
                            </button>
                            <span class="clearfix"></span>
                        </div>

                        <ul class="nav navbar-nav navbar-right pull-right">
                            <li class="dropdown top-menu-item-xs">
                                <a href="" class="dropdown-toggle profile waves-effect waves-light" data-toggle="dropdown" aria-expanded="true"><img src="<?php echo base_url('assets/images/def_avatar.jpg'); ?>" alt="user-img" class="img-circle"> </a>
                                <ul class="dropdown-menu">
                                    <li><a href="<?php echo base_url() . 'Cms/logout'; ?>"><i class="ti-power-off m-r-10 text-danger"></i> Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <!--/.nav-collapse -->
                </div>
            </div>
        </div>
        <!-- Top Bar End -->
        <div class="left side-menu">
            <div class="sidebar-inner slimscrollleft">
                <!--- Divider -->
                <div id="sidebar-menu">
                    <ul>
                        <li class="text-muted menu-title">Dashboard</li>
                        <li><a href="<?php echo base_url() . 'Cms/users'; ?>">Users</a></li>
                        <li><a href="<?php echo base_url() . 'Cms/videos'; ?>">Videos</a></li>
                        <!-- <li class="has_sub">
                            <a href="javascript:;" class="waves-effect"><i class=" ti-shopping-cart-full"></i> <span> Members </span> <span class="menu-arrow"></span></a>
                            <ul class="list-unstyled">
                                <li><a href="<?php echo base_url() . 'Cms/users'; ?>">Brokers</a></li>
                                <li><a href="<?php echo base_url() . 'Cms/videos'; ?>" >Agents</a></li>
                            </ul>
                        </li> -->
                    </ul>

                    <div class="clearfix"></div>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
        <!-- Left Sidebar End -->
        <!-- ========== Left Sidebar Start ========== -->