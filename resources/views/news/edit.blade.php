@extends('layouts.admin')

@section('content')

@if ($errors->any())
      <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
        </ul>
      </div><br />
@endif

<form action="/news/{{$news->id}}" method ="POST" enctype="multipart/form-data">
@csrf
{{ method_field('PATCH')}}

    <div class="form-group" style="width:500px">
      <label for="main_title">Main Title:</label>
      <input type="text" class="form-control" id="main_title" name="main_title" value="{{$news->main_title}}">
    </div>

<input id="news" type="hidden" name="id" value="{{$news->id}}">

    <div class="form-group" style="width:500px">
      <label for="secondary_title">Secondary Title:</label>
      <input type="text" class="form-control" id="secondary_title" name="secondary_title"  value="{{$news->secondary_title}}">
    </div>

    <div class="form-group">
    <label for="type"> Type:</label>
    <select  id="type" name="type">
        @foreach($types as $key =>$value)
        <option value="{{$key}}"{{ ($news->type == $value) ? 'selected' : '' }}>{{$value}}</option>
        @endforeach
    </select>
    </div>

    <div class="form-group">
      <label for="staff_id">Author:</label>
      <select name="staff_id" id="staff_id" class="form-control" style="width:350px">
      @foreach ($authors as $key =>$author)
      <option value="{{$key}}" {{ ($news->staff->id== $key) ? 'selected' : '' }} >{{$author}}<option>
       @endforeach
      </select>
    </div>
      
    </div>

    <div class="form-group">
    <label for="content">Content:</label>
    <textarea name="content" id="content">{{$news->content}}</textarea>
    </div>

    
    <div class="form-group">
            <label for="image">Image Upload(can load more than one)</label>
            <div class="needsclick dropzone" id="image-drop">
            </div>
      </div>

  
    <div class="form-group">
            <label for="file">File Upload(can load more than one)</label>
            <div class="needsclick dropzone" id="file-drop">
            </div>
    </div>  

    <div class="form-group">
    <label>Choose Related News</label>
    <select id="published" data-placeholder="Choose News..." class="chosen-select" multiple style="width:400px;" name="related[]">
    @if(!empty($selectedNews))
      @foreach($selectedNews as $key=>$news)
      <option selected="selected" value="{{$key}}">{{$news}}</option>
      @endforeach
    @endif  
  </select>
    </div>
    



  <button type="submit" class="btn btn-primary">Add</button>

</form>

@push('scripts')
<!-- ckEditor -->
<script src="https://cdn.ckeditor.com/ckeditor5/12.4.0/classic/ckeditor.js"></script>
<script>
        ClassicEditor
            .create( document.querySelector('#content') )    
            .catch( error => {
                console.error( error );
            } );

</script>
<!-- published news -->
<script>
$(".chosen-select").select2({
        ajax: {
            type: "GET",
            url:"{{url('get-published')}}",
            data: function (params) {
                if (params){
                    return {
                        search: params.term,
                    };
                }
            },
            processResults: function (data) {
                let result = data.map(function (item) {
                    return {id: item.id, text: item.main_title};
                });
                return {
                    results: result
                };
            }
        },
    });
  </script>
<!-- staff_id ajax request -->
<script type="text/javascript">
 $('#type').change(function(){
    var jobId = $(this).val();   
    if(jobId){
      $.ajax({
        type:"GET",
           url:"{{url('get-authors')}}?job_id="+jobId,
           success:function(data){  
            if(data){
                $("#staff_id").empty();
                $("#staff_id").append('<option>Select</option>');
                $.each(data,function(key,value){
                    $("#staff_id").append(`<option value='${key}' >${value}</option>`);
                });
           }else{ 
             $("#staff_id").empty(); 
           }             
          } 
      }); 
    }else{
     $("#staff_id").empty(); 
    }       
  });
</script>
<!-- Drop Image -->
<script>
Dropzone.autoDiscover = false;
    var uploadedImages = {}
      let imageDropzone = new Dropzone('#image-drop', {
      url: "{{ route('uploads') }}",
      paramName: "image",
      maxThumbnailFilesize: 1, // MB
      acceptedFiles: ".png,.jpg",
      addRemoveLinks: true,
      headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}"},
      success: function (image, response) {
        $('form').append('<input id="my" type="hidden" name="image[]" value="' + response.id + '">')
        uploadedImages[image.name] = response.id
      },
      removedfile: function (image) {
        image.previewElement.remove()
        let id = '';
        id = uploadedImages[image.name];
          $.ajax({
          type:"GET",
          url:'/delete-image/'+id ,
          });
        $('form').find('input[name="image[]"][value="'+ id +'"]').remove()
      },
      init:function(){
        var newsId = $('#news').val(); 
        myDropzone = this;
        $.ajax({
          type:"GET",
          url:"{{ route('getImages') }}?news_id="+newsId,
          success: function(data){
            if(data){
              data.forEach(myFunction);
              function myFunction(item, index) {
                var mockFile = {name: item.image};
                myDropzone.options.addedfile.call(myDropzone, mockFile)
                myDropzone.options.thumbnail.call(myDropzone, mockFile,`{{ Storage::url('${mockFile.name}') }}`)
                $('form').append('<input id="my" type="hidden" name="image[]" value="' + item.id + '">')
                uploadedImages[mockFile.name]=item.id
              } 
            }else{ 
            $("#form").empty()
            } 
          }
        });
      }, 
})
</script>

<!-- Drop File -->
<script>
  Dropzone.autoDiscover = false;
  var uploadedFiles = {}
  let fileDropzone = new Dropzone('#file-drop', {
    url: "{{ route('uploads') }}",
    maxThumbnailFilesize: 1, // MB
    acceptedFiles: ".pdf,.xlsx",
    addRemoveLinks: true,
    headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}"},
    success: function (file, response) {
      $('form').append('<input id="my" type="hidden" name="file[]" value="' + response.id + '">')
      uploadedFiles[file.name] = response.id
     },
    removedfile: function (file) {
                file.previewElement.remove()
                let id = '';
                    id = uploadedFiles[file.name];
                    $.ajax({
                      type:"GET",
                      url:'/delete-file/'+id ,
                    });
                $('form').find('input[name="file[]"][value="'+ id +'"]').remove()
     },
    init:function(){
        var newsId = $('#news').val();  
        myDropzonefile = this;
        $.ajax({
          type:"GET",
           url:"{{ route('getFiles') }}?news_id="+newsId,
        success: function(data){
          if(data){
            data.forEach(myFunction);
            function myFunction(item, index) {
            var ext = item.file.split('.').pop();
            var mockFile = {name: item.file};
            myDropzonefile.options.addedfile.call(myDropzonefile, mockFile)
            myDropzonefile.options.thumbnail.call(myDropzonefile, mockFile,`{{ Storage::url('${mockFile.name}') }}`)
            if (ext == "pdf") {
              $(mockFile.previewElement).find(".dz-image img").attr("src", "{{ Storage::url('pdf.png') }}")
            }else if(ext == "xlsx"){
              $(mockFile.previewElement).find(".dz-image img").attr("src", "{{ Storage::url('excel.png') }}")
            }
            $('form').append('<input id="my" type="hidden" name="file[]" value="' + item.id + '">')
            uploadedFiles[mockFile.name]=item.id
          }   
        }else{ 
          $("#form").empty()
        }  
       }
      });
    },           
  })
</script>
<!-- js validation -->
<script type="text/javascript" src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{!! JsValidator::formRequest('App\Http\Requests\NewsRequest') !!}
@endpush
@endsection