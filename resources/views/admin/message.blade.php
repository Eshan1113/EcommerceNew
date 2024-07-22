

@if (Session::has('error'))
<script>
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
<h4><i class="icon fa fa-ban"></i> Error!</h4>{{Session::get('error')}}
</div>
alertify.success('Success notification message.');
</script> 
@endif

@if (Session::has('success'))
<div class="alert alert-success alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
<h4><i class="icon fa fa-check"></i>success</h4>{{Session::get('success')}}
@endif
</div>