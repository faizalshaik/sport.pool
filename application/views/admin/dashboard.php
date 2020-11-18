		<script src="<?php echo base_url('assets/js/jquery.app.js');?>"></script>

		<!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="content-page">
                <!-- Start content -->
                <div class="content">
                    <div class="container">
                    	<!-- Page-Title -->
                        <div class="row">
                            <div class="col-sm-12">
                                <h4 class="page-title">Dashboard</h4>
                                <p class="text-muted page-title-alt">Welcome to Admin Pannel!</p>
                            </div>
                        </div>

                    	<div class="row">

                            <div class="col-md-6 col-lg-3">
                                <div class="widget-bg-color-icon card-box fadeInDown animated">
                                    <div class="bg-icon bg-icon-info pull-left">
                                        <i class="md md-account-child text-custom"></i>
                                    </div>
                                    <div class="text-right">
                                        <h3 class="text-dark"><b class="counter"><?php echo $user_cnt;?></b></h3>
                                        <p class="text-muted">Total Users</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="widget-bg-color-icon card-box fadeInDown animated">
                                    <div class="bg-icon bg-icon-info pull-left">
                                        <i class="fa fa-question text-info"></i>
                                    </div>
                                    <div class="text-right">
                                        <h3 class="text-dark"><b class="counter"><?php echo $prayers_cnt;?></b></h3>
                                        <p class="text-muted">Total Prayers</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="widget-bg-color-icon card-box fadeInDown animated">
                                    <div class="bg-icon bg-icon-success pull-left">
                                        <i class="md md-question-answer text-success"></i>
                                    </div>
                                    <div class="text-right">
                                        <h3 class="text-dark"><b class="counter"><?php echo $full_grown_cnt;?></b></h3>
                                        <p class="text-muted">Full Grown</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="widget-bg-color-icon card-box fadeInDown animated">
                                    <div class="bg-icon bg-icon-purple pull-left">
                                        <i class="md md-comment text-purple"></i>
                                    </div>
                                    <div class="text-right">
                                        <h3 class="text-dark"><b class="counter"><?php echo $in_growing_cnt;?></b></h3>
                                        <p class="text-muted">Growing</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>                            
                        </div>

                        <!-- profile -->
                        <div class="row">
                        	<div class="col-sm-12">
                        		<div class="card-box">
                        			<h4 class="m-t-0 header-title"> My Account </h4>
                        			<p class="text-muted font-13 m-b-30">
	                                    You can modify admin account here.
	                                </p>
	                                <div class="row">
	                                	<div class="col-lg-9">
	                                		<form action="<?php echo base_url().'Cms/updateAccount'?>" data-parsley-validate="" novalidate="" class="form-horizontal" method="post">
	                                			<div class="form-group">
													<label class="col-lg-4 control-label" for="emailAddress">
														Email address*
													</label>
													<div class="col-lg-8">
														<input name="email" parsley-trigger="change" required="" placeholder="Enter email" class="form-control" id="emailAddress" type="email">
													</div>
												</div>
												<div class="form-group">
													<label class="col-lg-4 control-label" for="pass1">Password*</label>
													<div class="col-lg-8">
														<input id="pass1" placeholder="Password" required="" class="form-control" type="password" name="password">
													</div>
												</div>
												<div class="form-group">
													<label class="col-lg-4 control-label" for="passWord2">
														Confirm Password *
													</label>
													<div class="col-lg-8">
														<input data-parsley-equalto="#pass1" required="" placeholder="Password" 
														class="form-control" id="passWord2" type="password">
													</div>
												</div>
												<div class="form-group text-left m-b-10">
													<div class="col-lg-8 col-lg-offset-4">
														<button class="btn btn-primary waves-effect waves-light" type="submit">
															Submit
														</button>
													</div>
												</div>
	                                		</form>		
	                                	</div>
	                                </div>
                        		</div>
                        	</div>
                        </div><!-- profile -->
                    </div> <!-- container -->

                </div> <!-- content -->    
            </div> <!-- content-page -->
		</div>
        <!-- END wrapper -->
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.counter').counterUp({
                    delay: 100,
                    time: 1200
                });
                var msg = "<?php if($this->session->flashdata('messagePr')) { echo $this->session->flashdata('messagePr'); 
                    $this->session->unset_userdata('messagePr');} else echo 'no'?>";
                if(msg !='no') {
                    if(msg.includes('Successfully')) 
                        $.Notification.notify('success','bottom right','Success', msg);
                    else
                        $.Notification.notify('error','bottom right','Error', msg);
                }
            });
        </script>
	</body>
</html>