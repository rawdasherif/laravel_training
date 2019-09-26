@extends('layouts.admin')
@section('content')

@include('flash-message')

<h1>Jobs</h1>
@if(auth()->user()->can('job-create'))
<a class="btn btn-primary btn-sm" href="jobs/create"><i class="fa fa-plus"></i><span>Add New Job</span></a><br><br>
@endif
<table id="table" >
    <thead>
    <tr>
    <th>Id</th>
            <th> Name</th>
            <th>Description</th>
            @if(auth()->user()->can('job-edit') || auth()->user()->can('job-delete'))
            <th>Actions</th>
            @endif
            </tr>
            
    </thead>
    </table>

<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script>
    $('#table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,

        ajax: {
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "/jobs_list",
            dataType: 'json',
            type: 'get',
            
        },
        columns: [{
                data: 'id'
            },
            {
                data: 'name'
            },
            {
                data: 'description'
            },
            @if(auth()->user()->can('city-edit') || auth()->user()->can('city-delete')) {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            },
            @endif

   
        ],

    });

</script>


</div>
@endsection