<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\NewsRequest;
use App\Traits\ManageUploads;
use App\Traits\toggle;
use DataTables;
use App\Staff;
use App\News;
use App\Image;
use App\File;

class NewsController extends Controller
{
    use ManageUploads,toggle;


    public function __construct()
    {
        $this->authorizeResource(News::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $news=News::query();
                      
            return Datatables::of($news)
              ->addColumn('action', function ($row) {
                return  view('news.actions', compact('row'));
              })
              ->rawColumns(['action']) ->make(true);
        }
        return view('news.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('news.create', compact('relNews'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(NewsRequest $request)
    {
        $news=News::create($request->all());

        if ($ids = $request->input('image')) {
            $images = Image::whereIn('id', $ids)->get();
            $news->image()->saveMany($images->getDictionary());
        }

        if ($ids = $request->input('file')) {
            $files = File::whereIn('id', $ids)->get();
            $news->file()->saveMany($files->getDictionary());
        }

        if ($rel_news = $request['related']) {
            $news->related()->sync($rel_news);
        }

        return redirect()->route('news.index')->with('success', 'News Added');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(News $news)
    {
        return view('news.show', compact('news'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(News $news)
    {
        $selectedNews =$news->related()->get();  
        $selectedNews=$selectedNews->pluck('main_title','id')->toArray();

        $authors = $this->returnAuthors($news->staff->job_id);
        
        return view('news.edit', compact('news','selectedNews', 'authors'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(NewsRequest $request, News $news)
    {
        $news->update($request->all());

        if ($ids = $request->input('image')) {
            $images = Image::whereIn('id', $ids)->get();
            $news->image()->saveMany($images->getDictionary());
        }

        if ($ids = $request->input('file')) {
            $files = File::whereIn('id', $ids)->get();
            $news->file()->saveMany($files->getDictionary());
        }
         
        if ($rel_news = $request['related']) {
            $news->related()->sync($rel_news);
        }
         return redirect()->route('news.index')->with('success', 'news has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(News $news)
    {
        $news->delete();
        return redirect()->route('news.index')->with('success', 'news deleted');
    }

    public function getAuthors(Request $request)
    {
        $authors = $this->returnAuthors($request->job_id);
        return response()->json($authors);
    }

    public function returnAuthors($id)
    {
        $authors = Staff::where('job_id', $id)->get();
        $authors=$authors->pluck('user.first_name', 'id');
        return $authors;
    }

    

    public function getPublishedNews(Request $request){
        $query = $request['search'];
        $news= News::where('is_publish', true)
        ->where('main_title', 'like', "%$query%")
        ->select('main_title', 'id')->get();

        return response()->json($news);
    }

    public function toggleStatus(News $news)
    {
        $this->publish($news);
        return redirect()->route('news.index');
    }

    public function uploads(Request $request)
    {
        $model =new News;
        $url =$this->serverUpload($request,$model);

        if ($request->file('image')) {
             $file=Image::create(['image'=>$url]);
        }elseif ($request->file('file')) {
             $file=File::create(['file'=>$url]);
        }
        return response()->json(['id' => $file->id,'name'=>$url]);
    }

}
