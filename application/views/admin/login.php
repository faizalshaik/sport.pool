<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="FlexAR">
		<meta name="author" content="Lance Bunch">

		<!-- <link rel="shortcut icon" href="<?php echo base_url('assets/images/favicon.ico');?>"> -->

		<title>Admin</title>

		<link href="<?php echo base_url('assets/css/bootstrap.min.css');?>" rel="stylesheet" type="text/css" />
		<link href="<?php echo base_url('assets/css/core.css');?>" rel="stylesheet" type="text/css" />
		<link href="<?php echo base_url('assets/css/components.css');?>" rel="stylesheet" type="text/css" />
		<link href="<?php echo base_url('assets/css/icons.css');?>" rel="stylesheet" type="text/css" />
		<link href="<?php echo base_url('assets/css/pages.css');?>" rel="stylesheet" type="text/css" />
		<link href="<?php echo base_url('assets/css/responsive.css');?>" rel="stylesheet" type="text/css" />

		

		<!-- HTML5 shim and Respond.js IE8 support of HTML5 tooltipss and media queries -->
		<!--[if lt IE 9]>
		  <script src="<?php echo base_url('js/html5shiv.js');?>"></script>
		  <script src="<?php echo base_url('js/respond.min.js');?>"></script>
		<![endif]-->
		<script src="<?php echo base_url('assets/js/modernizr.min.js');?>"></script>
	</head>
	<body>

		<div class="account-pages"></div>
		<div class="clearfix"></div>
		<div class="wrapper-page">
			<div class=" card-box">
			<div class="panel-heading"> 
				<h3 class="text-center"> Sign In to <strong class="text-custom">Admin</strong> </h3>
			</div> 


			<div class="panel-body">
			<form class="form-horizontal m-t-20" action="<?php echo base_url().'Cms/auth_user'?>" data-parsley-validate novalidate method="post">
				<div class="form-group ">
					<div class="col-xs-12">
						<input class="form-control" parsley-trigger="change"  type="email" required placeholder="Email" 
						name="email" autocomplete="off" value="admin@admin.com">
					</div>
				</div>

				<div class="form-group">
					<div class="col-xs-12">
						<input class="form-control" type="password" required placeholder="Password" name="password"  autocomplete="off" value="123456">
					</div>
				</div>
				
				<div class="form-group text-center m-t-40">
					<div class="col-xs-12">
						<button class="btn btn-pink btn-block text-uppercase waves-effect waves-light" type="submit">Log In</button>
					</div>
				</div>
			</form> 
			</div>   
		</div>
		
		<script>
			var resizefunc = [];
		</script>

		<!-- jQuery  -->
		<script src="<?php echo base_url('assets/js/jquery.min.js');?>"></script>
		<script src="<?php echo base_url('assets/js/bootstrap.min.js');?>"></script>
		<script src="<?php echo base_url('assets/js/detect.js');?>"></script>
		<script src="<?php echo base_url('assets/js/fastclick.js');?>"></script>
		<script src="<?php echo base_url('assets/js/jquery.slimscroll.js');?>"></script>
		<script src="<?php echo base_url('assets/js/jquery.blockUI.js');?>"></script>
		<script src="<?php echo base_url('assets/js/waves.js');?>"></script>
		<script src="<?php echo base_url('assets/js/wow.min.js');?>"></script>
		<script src="<?php echo base_url('assets/js/jquery.nicescroll.js');?>"></script>
		<script src="<?php echo base_url('assets/js/jquery.scrollTo.min.js');?>"></script>

		<script src="<?php echo base_url('assets/plugins/notifyjs/js/notify.js');?>"></script>
		<script src="<?php echo base_url('assets/plugins/notifications/notify-metro.js');?>"></script>

		<script src="<?php echo base_url('assets/js/jquery.core.js');?>"></script>
		<script src="<?php echo base_url('assets/js/jquery.app.js');?>"></script>

		<script type="text/javascript" src="<?php echo base_url('assets/plugins/parsleyjs/parsley.min.js');?>"></script>
		<script type="text/javascript">
		$(document).ready(function() {
			$('form').parsley();
		});


		var msg = "<?php if($this->session->flashdata('messagePr')) { echo $this->session->flashdata('messagePr'); $this->session->unset_userdata('messagePr');} else echo 'no'; ?>";
    	if(msg !='no') {
			if(msg.includes('Successfully')) 
				$.Notification.notify('success','bottom right','Success', msg);
			else
				$.Notification.notify('error','bottom right','Error', msg);
		}

		</script>
	</body>
</html>