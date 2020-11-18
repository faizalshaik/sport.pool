<link href="<?php echo base_url('assets/plugins/bootstrap-select/css/bootstrap-select.min.css');?>" rel="stylesheet" />
<script src="<?php echo base_url('assets/plugins/bootstrap-select/js/bootstrap-select.min.js');?>" type="text/javascript"></script>
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
                    <div class="col-sm-6">
                        <h4 class="page-title">Manage Videos</h4>
                        <ol class="breadcrumb"> </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-8">
                        <div class="card-box table-responsive">
                            <h4 class="m-t-0 header-title"><b>Videos</b></h4>
                            <p class="text-muted font-13 m-b-30"></p>
                            <table id="datatable-match" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Id</th>
                                        <th>Link</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Id</th>
                                        <th>Link</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="card-box">
                            <h4 class="m-t-0 header-title"><b>Edit Video</b></h4>
                            <form data-parsley-validate novalidate>
                                <input type="hidden" id="Id">
                                <div class="form-group">
                                    <label for="price">Link</label>
                                    <input type="text" id="link" name="link" parsley-trigger="change" required placeholder="Enter Link" 
                                        class="form-control">
                                </div>
                                <button class="btn btn-default waves-effect waves-light" onclick="onRefreshLink();" type="button">
                                        <i class="fa fa-refresh m-r-5"></i>Refresh
                                </button>
                                <div class="form-group pull-right m-b-0">
                                    <button class="btn btn-primary waves-effect waves-light" type="button" onclick="onSave();">
                                        Save
                                    </button>
                                    <button class="btn btn-default waves-effect waves-light m-l-5" type="button" onclick="clearForm();">
                                        Clear
                                    </button>
                                </div>
                            </form>                            
                        </div>
                        <iframe class="m-t-10" id="videoframe" style="width:100%; min-height: calc(50vh);" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>                      

                </div>
            </div> <!-- container -->
        </div> <!-- content -->    
    </div> <!-- content-page -->
</div>
        <!-- END wrapper -->
<script type="text/javascript">
    var tableMatch;
    var tableName = 'tbl_video';

    var $dom = {
        Id:$("#Id"),        
        link:$("#link")
    }      

    function clearForm()
    {
        $dom.Id.val("");
        $dom.link.val("");  
        $("#videoframe").attr("src", "");
    }

    function onSave()
    {
        let id = $dom.Id.val();
        let link = $dom.link.val();
        $.ajax({
            url : "<?php echo site_url('Cms_api/save_video')?>",
            data: {Id:id, link: link},
            type: "POST",
            dataType: "JSON",
            success: function(data)
            {
                clearForm();
                tableMatch.ajax.reload();
            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                swal("Error!", "", "error");  
            }
        });        
    }


    function onEdit(_idx) 
    {
        $.ajax({
            url : "<?php echo site_url('Cms_api/getDataById')?>",
            data: {Id:_idx, tbl_Name: tableName},
            type: "POST",
            dataType: "JSON",
            success: function(data)
            {
                $dom.Id.val(data.Id);
                $dom.link.val(data.link); 
                $("#videoframe").attr("src", data.link);           
            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                swal("Error!", "", "error");  
            }
        });
    }

    function onDelete(_idx) {
        swal({
            title: "Are you sure?",
            text: "You will not be able to recover this user information!",
            type: "error",
            showCancelButton: true,
            cancelButtonClass: 'btn-white btn-md waves-effect',
            confirmButtonClass: 'btn-danger btn-md waves-effect waves-light',
            confirmButtonText: 'Remove',
            closeOnConfirm: false
        }, function(isConfirm) {
            if(isConfirm) {
                $.ajax({
                    url : "<?php echo site_url('Cms_api/delData')?>",
                    data: {Id:_idx, tbl_Name:tableName},
                    type: "POST",
                    dataType: "JSON",
                    success: function(data)
                    {
                        swal("Remove!", "", "success");
                        clearForm();
                        tableMatch.ajax.reload(null,false);
                    },
                    error: function (jqXHR, textStatus, errorThrown)
                    {
                        // alert('Error get data from ajax');
                        swal("Error!", "", "error");  
                    }
                });
            }
        });
    }

    function onRefreshLink()
    {
        $("#videoframe").attr("src", $dom.link.val());
    }



    var handleDataTableButtonsMatch = function() {
        tableMatch = $("#datatable-match").DataTable({
            dom: "lfBrtip",
            buttons: [{
                extend: "copy",
                className: "btn-sm"
            }, {
                extend: "csv",
                className: "btn-sm"
            }, {
                extend: "excel",
                className: "btn-sm"
            }, {
                extend: "pdf",
                className: "btn-sm"
            }, {
                extend: "print",
                className: "btn-sm"
            }],
            responsive: !0,
            processing: true,
            serverSide: false,
            sPaginationType: "full_numbers",
            language: {
                paginate: {
                      next: '<i class="fa fa-angle-right"></i>',
                      previous: '<i class="fa fa-angle-left"></i>',
                      first: '<i class="fa fa-angle-double-left"></i>',
                      last: '<i class="fa fa-angle-double-right"></i>'
                }
            },
            //Set column definition initialisation properties.
            columnDefs: [
                { 
                    targets: [ 0 ], //first column 
                    orderable: true, //set not orderable
                    className: "dt-center"
                },
                { 
                    targets: [ 1 ], //first column 
                    orderable: false, //set not orderable
                    className: "dt-center"
                },
                { 
                    targets: [ -1 ], //last column
                    orderable: false, //set not orderable
                    className: "actions dt-center"
                }
            ],
            ajax: {
                url: "<?php echo site_url('Cms_api/get_videos')?>",
                type: "POST",
            },
        })
    },
    TableManageButtonsMatch = function() {
        return {
            init: function() {
                handleDataTableButtonsMatch()
            }
        }
    }();
    TableManageButtonsMatch.init();
</script>