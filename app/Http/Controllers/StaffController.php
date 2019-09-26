<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use DataTables;
use Spatie\Permission\Models\Role;
use App\Job;
use App\Country;
use App\City;
use App\User;
use App\Staff;
use App\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;


class StaffController extends Controller
{

    use SendsPasswordResetEmails;

    public function getstaff()
    {
        $staff=Staff::offset(0)->limit(10);

        return Datatables::of($staff)->setTotalRecords(Staff::count())
           ->addColumn('role', function ($row) {
                     return $row->user->getRoleNames()->first();
           })
           ->addColumn('image', function ($row) {
                 return '<img src="'.Storage::url($row->image['image']).'" style="height:50px; width:50px;" />';
           })
           ->addColumn('action', function ($data) {
            return  view('staff.actions',compact('data'));

           })
           ->addColumn('status', function ($data) {
            return  view('staff.status',compact('data'));

           })->rawColumns(['role','image','action','status']) ->make(true);

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         return view('staff.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::pluck('name');
        $jobs = Job::pluck('name','id');
        $countries = Country::pluck('name','id');
        return view('staff.create', compact('roles','jobs','countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreStaffRequest $request)
    {
        $user=User::create(
            $request->except('password') + ['password' => Hash::make(Str::random(8))]
        );
        $user->assignRole($request->role);  

        $staff=Staff::create($request->except('user_id') + ['user_id' => $user->id]);
        
        //image upload
        if($request['image']){
            $img=$this->UploadImage($request['image']); 
            
        }else{
            $img=Image::defaultImage();
        }
        $staff->image()->create(['image'=> $img]); 

        $this->sendResetLinkEmail($request);
          
        return redirect()->route('staff.index')->with('success', 'User has been Added');

    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Staff $staff)
    {
        $roles = Role::pluck('name');
        $jobs = Job::pluck('name','id');
        $countries = Country::pluck('name','id');
        return view('staff.edit', compact('staff','roles','jobs','countries'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStaffRequest $request, Staff $staff)
    {
        $staff->update($request->only(['job_id']));
        $staff->user->update($request->all());
        $staff->user->syncRoles($request->role); 

          if ($request->has('image')) {
            $img=$this->UploadImage(request('image'));
            $staff->image()->Update(['image'=>$img]);
          }

        return redirect()->route('staff.index')->with('success', 'Staff has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Staff $staff)
    {
        $staff->user->delete();
        $staff->delete();
        return redirect()->route('staff.index')->with('success', 'Staff deleted');
       
    }

    public function getCities(Request $request)
    {
        $cities = City::where("country_id",$request->country_id)
            ->pluck("city_name","id");
         return response()->json($cities);
    }

    public function UploadImage($reqImg){

        $img = $reqImg->store('uploads', 'public');
        return $img;

    }    

    public function deActive(Staff $staff)
    {

        $staff->user->ban();
        return redirect()->route('staff.index');
    }

    public function Active(Staff $staff)
    {
        $staff->user->unban();
        return redirect()->route('staff.index');
    }
}