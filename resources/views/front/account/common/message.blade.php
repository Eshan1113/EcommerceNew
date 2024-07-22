@if (Session::has('error'))
<div class="alert alert-success alert-dismissible fade show">
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
<h4><i class="icon fa fa-ban"></i> Error!</h4>{{Session::get('error')}}
</div>
alertify.success('Success notification message.');
@endif

@if (Session::has('success'))
<div class="alert alert-success alert-dismissible fade show">
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
<h4><i class="icon fa fa-check"></i>success</h4>{{Session::get('success')}}
@endif
</div>