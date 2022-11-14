<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Mail;
use FFMpeg;
use FFMpeg\Filters\Video\VideoFilters;
use ProtoneMedia\LaravelFFMpeg\Filters\WatermarkFactory;
use ProtoneMedia\LaravelFFMpeg;
use Illuminate\Support\Facades\File;
use Image;
use DB;
use Input;
use App\Item;
use Session;
use Response;
use Validator;


class khabarwalaappController extends Controller
{
     public function signup(Request $request){
     	$validate = Validator::make($request->all(), [ 
	      'fullname' => 'required',
	      'email'	 => 'required',
	      'password' => 'required|size:6',
	      'phone'	 => 'required',
	      'city'	 => 'required',
    	]);
    	if ($validate->fails()) {    
			return response()->json("Enter All Fields To Signup", 400);
		}
    	$validateemail = Validator::make($request->all(), [ 
	      'email'	 => 'unique:user,user_email',
	    ]);
     	if ($validateemail->fails()) {    
			return response()->json("Email Already Exist", 400);
		}
     	$verify_token =  $this->generateRandomString(100);
		if ($request->role == 2) {
			$getcategory = $request->category;
		}else{
			$getcategory = "";
		}
		$adds[] = array(
		'user_fullname'			=> $request->fullname,
		'user_email' 			=> $request->email,
		'user_password' 		=> $request->password,
		'user_phoneno' 			=> $request->phone,
		'user_city'			 	=> $request->city,
		'user_profilepicture' 	=> "",
		'user_coverpicture' 	=> "",
		'verify_token'		 	=> $verify_token,
		'role_id' 				=> $request->role,
		'user_category' 		=> $getcategory,
		'status_id' 			=> 1,
		'created_at' 			=> date('Y-m-d h:i:s'),
		);
		$insert = DB::table('user')->insert($adds);
		if ($insert) {
			$senddata = new \stdClass();
			$senddata->name = $adds[0]['user_fullname'];
			$senddata->email = $adds[0]['user_email'];
			$senddata->phone = $adds[0]['user_phoneno'];
			$senddata->id = $adds[0]['verify_token'];
			$senddata->type = $adds[0]['role_id'];
			return json_encode($senddata);
		}else{
			return response()->json("Oops! Something Went Wrong", 400);
		}
	}
    public  function generateRandomString($length = 20){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
	}
    public function login(Request $request){
    	$validate = Validator::make($request->all(), [ 
	      'email' 		=> 'required',
	      'password'	=> 'required',
	    ]);
     	if ($validate->fails()) {    
			return response()->json("Enter Credentials To Signin", 400);
		}
			$getprofileinfo = DB::table('user')
			->select('user_fullname as name','user_email as email','user_phoneno as phone','verify_token as id','role_id as type','user_profilepicture as avatar','user_coverpicture as cover','user.user_city')
			->where('user_email','=',$request->email)
			->where('user_password','=',$request->password)
			->where('status_id','=',1)
			->first();
			if (empty($getprofileinfo)) {
				return response()->json("Invalid Email Or Password", 400);
			}
			$getprofileinterest = DB::table('userinterest')
			->join('intrest','intrest.intrest_id', '=','userinterest.intrest_id')
			->select('intrest.intrest_name')
			->where('userinterest.verify_token','=',$getprofileinfo->id)
			->where('userinterest.status_id','=',1)
			->get();
			$getfollowing = DB::table('follower')
			->join('user','user.user_id', '=','follower.user_id')
			->select('user.user_id','user.user_fullname','user.user_profilepicture')
			->where('follower.verify_token','=',$getprofileinfo->id)
			->where('user.status_id','=',1)
			->count();
			$getid = DB::table('user')
			->select('user.user_id')
			->where('user.verify_token','=',$getprofileinfo->id)
			->where('user.status_id','=',1)
			->first();
			$getfollowers = DB::table('follower')
			->select('follower.verify_token')
			->where('follower.user_id','=',$getid->user_id)
			->where('follower.status_id','=',1)
			->count();
			$getprofileinfo->followers = $getfollowers;
			$getprofileinfo->following = $getfollowing;
			$allData = array("userinfo" => $getprofileinfo,"interest" => $getprofileinterest);	
			if($allData){
				return json_encode($allData);
			}else{
				return response()->json("Invalid Email Or Password", 400);
			}
	}
	public function interest(){
		$getintrest = DB::table('intrest')
		->select('intrest.*')
		->where('status_id','=',1)
		->get();
		if($getintrest){
			return json_encode($getintrest);
		}else{
			return response()->json("Oops! Something Went Wrong", 400);
		}
	}
	public function userinterest(Request $request){
		$validate = Validator::make($request->all(), [ 
	      'interests' => 'required|min:1',
    	]);
    	if ($validate->fails()) {    
			return response()->json("Select At least 1 Interest To Proceed", 400);
		}
		$validatemax = Validator::make($request->all(), [ 
	      'interests' => 'required|max:3',
    	]);
    	if ($validatemax->fails()) {    
			return response()->json("More Than 3 Interest Are Not Allowed", 400);
		}
		$insert;
		$getinterest = $request->interests;
		foreach ((array)$getinterest as $userinterest) {
			$adds[] = array(
			'intrest_id'			=> $userinterest,
			'verify_token' 			=> $request->header('token'),
			'status_id' 			=> 1,
			'created_at' 			=> date('Y-m-d h:i:s'),
			);
		}
		$insert = DB::table('userinterest')->insert($adds);
		if($insert){
			return json_encode(true);
		}else{
			return response()->json("Oops! Something Went Wrong", 400);
		}
	}
	public function profile(Request $request){
		$getparam;
		$checkfollowed = 0;
		if ($request->usertype == "loginUser") {
			$getparam = $request->header('token');
		}else{
			$getusertoken = DB::table('user')
			->select('user.verify_token')
			->where('user_id','=',$request->id)
			->where('status_id','=',1)
			->first();	
			$getparam = $getusertoken->verify_token;
			$checkfollowed = DB::table('follower')
			->select('*')
			->where('follower.user_id','=',$request->id)
			->where('follower.verify_token','=',$request->token)
			->where('follower.status_id','=',1)
			->count();
		}
		$checktoken = DB::table('user')
		->select('user.verify_token')
		->where('verify_token','=',$getparam)
		->where('status_id','=',1)
		->first();
		$allData;
		if(!empty($checktoken)){
		$getprofileinfo = DB::table('user')
		->select('user_fullname as name','user_email as email','user_phoneno as phone','verify_token as id','user_id as userId','role_id as type','user_profilepicture as avatar','user_coverpicture as cover','user.user_city')
		->where('verify_token','=',$getparam)
		->where('status_id','=',1)
		->first();
		$getprofileinterest = DB::table('userinterest')
		->join('intrest','intrest.intrest_id', '=','userinterest.intrest_id')
		->select('intrest.intrest_name')
		->where('userinterest.verify_token','=',$getparam)
		->where('userinterest.status_id','=',1)
		->get();
		$getfollowing = DB::table('follower')
		->join('user','user.user_id', '=','follower.user_id')
		->select('user.user_id','user.user_fullname','user.user_profilepicture')
		->where('follower.verify_token','=',$getparam)
		->where('follower.status_id','=',1)
		->count();
		$getid = DB::table('user')
		->select('user.user_id')
		->where('user.verify_token','=',$getparam)
		->where('user.status_id','=',1)
		->first();
		$getfollowers = DB::table('follower')
		->select('follower.verify_token')
		->where('follower.user_id','=',$getid->user_id)
		->where('follower.status_id','=',1)
		->count();
		$getprofileinfo->followers = $getfollowers;
		$getprofileinfo->following = $getfollowing;
		$getprofileinfo->isFollowing = $checkfollowed;
		$allData = array("userinfo" => $getprofileinfo,"interest" => $getprofileinterest);	
		}else{
			return response()->json("Invalid Request", 400);
		}
		if($allData){
			return json_encode($allData);
		}else{
			return response()->json("Oops! Something Went Wrong", 400);
		}
	}
	public function profilepicture(Request $request){
		$updateprofile;
		$validate = Validator::make($request->all(), [ 
	    	'image'=>'mimes:jpeg,bmp,png,jpg|max:5120',
    	]);
		if ($validate->fails()) {    
			return response()->json("Image Not Valid", 400);
		}
		if ($request->type == "avatar") {
			$foldername  = "profilepicture/";
		}else{
			$foldername  = "coverpicture/";
		}
  		$images = $request->avatar;
    	$filename;
    	$name = $request->name;
        if ($request->has('image')) {
    		if( $request->image->isValid()){
	            $number = rand(1,999);
		        $numb = $number / 7 ;
		        $extension = $request->image->extension();
	            $filename  = date('Y-m-d')."_".$numb."_".$name."_.".$extension;
	            $filename = $request->image->move(public_path($foldername),$filename);
			    $img = Image::make($filename)->resize(800,800, function($constraint) {
	                    $constraint->aspectRatio();
	            });
	            $img->save($filename);
			    $filename = date('Y-m-d')."_".$numb."_".$name."_.".$extension;
	        }
            }else{
    	        $filename = 'no_image.jpg'; 
	        }
	      	if ($request->type == "avatar") {
		      	$updateprofile  = DB::table('user')
				->where('verify_token','=',$request->header('token'))
				->update([
			   	'user_profilepicture' => $filename,
				]);   	
			}else{
				$updateprofile  = DB::table('user')
				->where('verify_token','=',$request->header('token'))
				->update([
			   	'user_coverpicture' => $filename,
				]);   	
			}
		if ($updateprofile) {
			return json_encode($filename);
		}else{
			return response()->json("Oops! Something Went Wrong", 400);	
		}
	}
	public function userlist(){
		$getusers = DB::table('user')
		->select('user.*')
		->where('status_id','=',1)
		->get();
		if($getusers){
			return json_encode($getusers);
		}else{
			return response()->json("Oops! Something Went Wrong", 400);
		}
	}
	public function follow(Request $request){
		$checktoken = DB::table('user')
		->select('user.verify_token')
		->where('verify_token','=',$request->header('token'))
		->where('status_id','=',1)
		->first();
		$checkfollower = DB::table('follower')
		->select('follower.follower_id')
		->where('verify_token','=',$request->header('token'))
		->where('user_id','=',$request->userId)
		->where('status_id','=',1)
		->first();
		$insert;
		$validate = Validator::make($request->all(), [ 
	      'userId' => 'required',
    	]);
    	if ($validate->fails()) {    
			return response()->json("User Id Required", 400);
		}
		$validatetoken = Validator::make($request->header(), [ 
	      'token' => 'required',
    	]);
    	if ($validatetoken->fails()) {    
			return response()->json("Auth Token Does not Exist", 400);
		}
		if(!empty($checktoken)){
			if ($checkfollower) {
				$updatelikee  = DB::table('follower')
					->where('verify_token','=',$request->header('token'))
					->where('user_id','=',$request->userId)
					->where('status_id','=',1)
					->update([
				   	'status_id' 	=> 2,
				   	'updated_at' 	=> date('Y-m-d h:i:s'),
				]);   
				return json_encode("UnFollow Successfuly");	
			}else{
				$adds[] = array(
				'verify_token' 		=> $request->header('token'),
				'user_id'			=> $request->userId,
				'status_id' 		=> 1,
				'created_at' 		=> date('Y-m-d h:i:s'),
				);
				$insert = DB::table('follower')->insert($adds);
			}
		}else{
			return response()->json("Invalid Auth Token", 400);
		}
		if($insert){
			return json_encode(true);
		}else{
			return response()->json("Oops! Something Went Wrong", 400);
		}
	}
	public function following(Request $request){
		$getparam;
		if ($request->usertype == "loginUser") {
			$getparam = $request->header('token');
		}else{
			$getparam = $request->id;
		}
		$getfollowing = DB::table('follower')
		->join('user','user.user_id', '=','follower.user_id')
		->select('user.user_id','user.user_fullname','user.user_profilepicture')
		->where('follower.verify_token','=',$getparam)
		->where('follower.status_id','=',1)
		->get();
			return json_encode($getfollowing);
	}
	public function followers(Request $request){
		$getparam;
		if ($request->usertype == "loginUser") {
			$getparam = $request->header('token');
		}else{
			$getparam = $request->id;
		}
		$getid = DB::table('user')
		->select('user.user_id')
		->where('user.verify_token','=',$getparam)
		->where('user.status_id','=',1)
		->first();
		$getfollowers = DB::table('follower')
		->select('follower.verify_token')
		->where('follower.user_id','=',$getid->user_id)
		->where('follower.status_id','=',1)
		->get();
		$getall = array();
		foreach ($getfollowers as $getfollowerss) {
		$getall[] = DB::table('user')
		->select('user.user_id','user.user_fullname','user.user_profilepicture')
		->where('user.verify_token','=',$getfollowerss->verify_token)
		->where('user.status_id','=',1)
		->first();
		}
			return json_encode($getall);
	}
	public function apppost(Request $request){
		$token = $request->header('token');
		if (!empty($token)) {
        $validatetoken = DB::table('user')
        ->select('user.verify_token')
        ->where('verify_token','=',$token)
        ->where('status_id','=',1)
        ->first();
        if ($validatetoken) {   
        $validate = Validator::make($request->all(), [ 
	      'video' 	=> 'required',
	      'poster'	=> 'required',
	    ]);
    	if ($validate->fails()) {    
			return response()->json("In Complete Data", 400);
		}
        	$videofilename;
            if ($request->has('video')) {
            		if( $request->video->isValid()){
			            $number = rand(1,999);
				        $numb = $number / 7 ;
				        $extension = $request->video->extension();
			            $videofilename  = date('Y-m-d')."_".$numb."_";
			          	$getorigionalfilepath = $request->video->move(storage_path('videos/'),$videofilename);
			          	
					}
	        }else{
            	        return response()->json("Oops! Something Went Wrong", 400);
                }
            $filename;
        	if ($request->has('poster')) {
            		if( $request->poster->isValid()){
			            $number = rand(1,999);
				        $numb = $number / 7 ;
				        $extension = $request->poster->extension();
			            $filename  = date('Y-m-d')."_".$numb."_.".$extension;
			            $filename = $request->poster->move(storage_path('thumbnail/'),$filename);
					    $img = Image::make($filename)->crop(270,480);
			            $img->save($filename);
					    $filename = date('Y-m-d')."_".$numb."_.".$extension;
			        	
            		}
            }else{
    	        $filename = 'no_image.jpg'; 
	        }
			// $durationfilename=$getorigionalfilepath;
			// $getID3 = new \getID3;
			// $file = $getID3->analyze($durationfilename);
			// echo("Duration: ".$file['playtime_string'].
			// " / Dimensions: ".$file['video']['resolution_x']." wide by ".$file['video']['resolution_y']." tall".
			// " / Filesize: ".$file['filesize']." bytes<br />");
			// $lowBitrateFormat = (new \FFMpeg\Format\Video\X264)->setKiloBitrate(120);
	        FFMpeg::fromDisk('videos')
			->open($videofilename)
		    // ->addWatermark(function (WatermarkFactory $watermark) {
		    //     $watermark->open('logo1.png')
		    //         ->right(25)
		    //         ->bottom(25);
		    // })
		    ->addFilter(function (VideoFilters $filters) {
			    $filters->resize(new \FFMpeg\Coordinate\Dimension(270, 480));
			})
		    ->export()
			->toDisk('videos')
			->inFormat((new \FFMpeg\Format\Video\WebM)->setKiloBitrate(120))
			// ->inFormat(new \FFMpeg\Format\Video\X264)
			// ->inFormat($lowBitrateFormat)
			// ->inFormat(new \FFMpeg\Format\Video\X264)
			->save($videofilename.'.mp4');
			File::delete($getorigionalfilepath);
			$adds[] = array(
			'post_title'		=> $request->title,
			'post_video'		=> $videofilename,
			'post_thumbnail'	=> $filename,
			'verify_token' 		=> $request->header('token'),
			'status_id' 		=> 1,
			'created_at' 		=> date('Y-m-d h:i:s'),
			);
			$insert = DB::table('post')->insert($adds);
			if($insert){
				return json_encode($adds);
			}else{
				return response()->json("Oops! Something Went Wrong", 400);
			}
        }else{
            return response()->json("Invalid Auth Token", 400);
        }
        }else{
            return response()->json("Auth Token Does not Exist", 400);
        }
	}
	public function getpost(Request $request){
	 $getpostid = DB::table('post')
        ->select('post.post_id')
        ->where('post.status_id','=',1)
        ->orderByDesc('post_id')
        ->first();	
        if ($request->post_id) {
        	$post_id = $request->post_id;
        }else{
        	$post_id = $getpostid->post_id;
        }
		if ($request->posttype == "public") {
		$checklike;
		$totalchecklike;
		$checktotalcomment;
		$getpublicpost;
        if ($request->usertype == 'loginUser') {
        $getuserid = DB::table('user')
       	->select('user.user_id')
        ->where('user.verify_token','=',$request->header('token'))
        ->first();
        $getblockuserlist = DB::table('blockuser')
       	->join('user','user.user_id','=','blockuser.user_id')
        ->select('user.verify_token')
        ->where('blockuser.verify_token','=',$request->header('token'))
        ->where('blockuser.status_id','=',1)
        ->get()
        ->toArray();
        $blockbyme = array();
        foreach ($getblockuserlist as $getblockuserlists) {
        	$blockbyme[] = $getblockuserlists->verify_token;
        }
        $getuserblockme = DB::table('blockuser')
       	->join('user','user.verify_token','=','blockuser.verify_token')
        ->select('user.verify_token')
        ->where('blockuser.user_id','=',$getuserid->user_id)
        ->where('blockuser.status_id','=',1)
        ->get()
        ->toArray();
        $blockme = array();
        foreach ($getuserblockme as $getuserblockmes) {
        	$blockme[] = $getuserblockmes->verify_token;
        }
            $getpublicpost = DB::table('post')
	        ->select('post.post_title','post.post_video','post.post_thumbnail','user.user_fullname','user.user_profilepicture','user.user_id','post.post_id')
	        ->join('user','user.verify_token', '=','post.verify_token')
	        ->where('post.post_id','<',$post_id)
	        ->where('post.status_id','=',1)
	        ->whereNotIn('post.verify_token',$blockme)
	        ->whereNotIn('post.verify_token',$blockbyme)
	        ->orderByDesc('post_id')
	        ->limit(3)
	        ->get();
        $index = 0;
        foreach ($getpublicpost as $getpublicposts) {
        	$checklike = DB::table('likee')
			->select('*')
			->where('likee.post_id','=',$getpublicposts->post_id)
			->where('likee.verify_token','=',$request->header('token'))
			->where('likee.status_id','=',1)
			->count();
			$getpublicpost[$index]->like = $checklike;
			$totalchecklike = DB::table('likee')
			->select('*')
			->where('likee.post_id','=',$getpublicposts->post_id)
			->where('likee.status_id','=',1)
			->count();
			$getpublicpost[$index]->totalLikes = $totalchecklike;
			$checktotalcomment = DB::table('comment')
			->select('*')
			->where('comment.post_id','=',$getpublicposts->post_id)
			->where('comment.status_id','=',1)
			->count();
			$getpublicpost[$index]->totalComments = $checktotalcomment;
			$index++;
        }
        }else{
        	 $getpublicpost = DB::table('post')
	        ->select('post.post_title','post.post_video','post.post_thumbnail','user.user_fullname','user.user_profilepicture','user.user_id','post.post_id')
	        ->join('user','user.verify_token', '=','post.verify_token')
	        ->where('post.post_id','<',$post_id)
	        ->where('post.status_id','=',1)
	        ->orderByDesc('post_id')
	        ->limit(3)
	        ->get();
        	$checklike = 0;
        	$totalchecklike = 0;
        	$checktotalcomment = 0;
        }
        return json_encode($getpublicpost);
		}else{
		$checklike;
		$totalchecklike;
		$checktotalcomment;
		$token = $request->header('token');
		if (!empty($token)) {
        $validatetoken = DB::table('user')
        ->select('user.verify_token')
        ->where('verify_token','=',$token)
        ->where('status_id','=',1)
        ->first();
        if ($validatetoken) {   
        $getuserid = DB::table('user')
       	->select('user.user_id')
        ->where('user.verify_token','=',$request->header('token'))
        ->first();
        $getblockuserlist = DB::table('blockuser')
       	->join('user','user.user_id','=','blockuser.user_id')
        ->select('user.verify_token')
        ->where('blockuser.verify_token','=',$request->header('token'))
        ->where('blockuser.status_id','=',1)
        ->get()
        ->toArray();
        $blockbyme = array();
        foreach ($getblockuserlist as $getblockuserlists) {
        	$blockbyme[] = $getblockuserlists->verify_token;
        }
        $getuserblockme = DB::table('blockuser')
       	->join('user','user.verify_token','=','blockuser.verify_token')
        ->select('user.verify_token')
        ->where('blockuser.user_id','=',$getuserid->user_id)
        ->where('blockuser.status_id','=',1)
        ->get()
        ->toArray();
        $blockme = array();
        foreach ($getuserblockme as $getuserblockmes) {
        	$blockme[] = $getuserblockmes->verify_token;
        }
        $getfolloweruserid = DB::table('follower')
		->select('follower.user_id')
		->where('follower.verify_token','=',$request->header('token'))
		->where('follower.status_id','=',1)
		->get();
		$getfollower = array();
		foreach ($getfolloweruserid as $getfolloweruserids) {
			$getfollower[] = DB::table('user')
	        ->select('user.verify_token')
	        ->where('user_id','=',$getfolloweruserids->user_id)
	        ->where('status_id','=',1)
	        ->first();
		}
		$getfollowerspost = array();
		foreach ($getfollower as $getfollowers) {
        	$getfollowerspost = DB::table('post')
	        ->select('post.post_title','post.post_video','post.post_thumbnail','user.user_fullname','user.user_profilepicture','user.user_id','post.post_id')
	        ->join('user','user.verify_token', '=','post.verify_token')
	        ->where('post.verify_token','=',$getfollowers->verify_token)
	        ->where('post.post_id','<',$post_id)
	        ->where('post.status_id','=',1)
	        ->whereNotIn('post.verify_token',$blockme)
	        ->whereNotIn('post.verify_token',$blockbyme)
	        ->orderByDesc('post_id')
	        ->limit(3)
	        ->get();
	    }
	   if ($request->usertype == 'loginUser') {
        $index = 0;
        foreach ($getfollowerspost as $getfollowersposts) {
        	$checklike = DB::table('likee')
			->select('*')
			->where('likee.post_id','=',$getfollowersposts->post_id)
			->where('likee.verify_token','=',$request->header('token'))
			->where('likee.status_id','=',1)
			->count();
        	$getfollowerspost[$index]->like = $checklike;
        	$totalchecklike = DB::table('likee')
			->select('*')
			->where('likee.post_id','=',$getfollowersposts->post_id)
			->where('likee.status_id','=',1)
			->count();
			$getfollowerspost[$index]->totalLikes = $totalchecklike;
			$checktotalcomment = DB::table('comment')
			->select('*')
			->where('comment.post_id','=',$getfollowersposts->post_id)
			->where('comment.status_id','=',1)
			->count();
			$getfollowerspost[$index]->totalComments = $checktotalcomment;
			$index++;
        }
        }else{
        	$checklike = 0;
        	$totalchecklike = 0;
        	$checktotalcomment = 0;
        }
		return json_encode($getfollowerspost);
        
        }else{
            return response()->json("Invalid Auth Token", 400);
        }
        }else{
            return response()->json("Auth Token Does not Exist", 400);
        }
		}
	}
	public function like(Request $request){
		 $validate = Validator::make($request->all(), [ 
	      'postId' 	=> 'required',
	    ]);
    	if ($validate->fails()) {    
			return response()->json("In Valid Post Id", 400);
		}
		$token = $request->header('token');
		if (!empty($token)) {
        $validatetoken = DB::table('user')
        ->select('user.verify_token')
        ->where('verify_token','=',$token)
        ->where('status_id','=',1)
        ->first();
        if ($validatetoken) {
        $checkiflike = DB::table('likee')
        ->select('likee.likee_id')
        ->where('post_id','=',$request->postId)
        ->where('verify_token','=',$request->header('token'))
        ->where('status_id','=',1)
        ->count();
        if ($checkiflike == 0) {
        $adds[] = array(
			'post_id'		=> $request->postId,
			'verify_token' 	=> $request->header('token'),
			'status_id' 	=> 1,
			'created_at' 	=> date('Y-m-d h:i:s'),
		);

		$insert = DB::table('likee')->insert($adds);
		return json_encode($adds);
        }else{
    	$updatelikee  = DB::table('likee')
			->where('verify_token','=',$request->header('token'))
			->where('post_id','=',$request->postId)
			->update([
		   	'status_id' => 2,
		]);   
		return json_encode("Post Unlike");
        }
        }else{
            return response()->json("Invalid Auth Token", 400);
        }
        }else{
            return response()->json("Auth Token Does not Exist", 400);
        }
		}
	public function comment(Request $request){
		$validate = Validator::make($request->all(), [ 
	      'postId' 	=> 'required',
	    ]);
    	if ($validate->fails()) {    
			return response()->json("In Valid Post Id", 400);
		}
		$validatetoken = Validator::make($request->header(), [ 
	      'token' => 'required',
    	]);
    	if ($validatetoken->fails()) {    
			return response()->json("Auth Token Does not Exist", 400);
		}
		$validatecomment = Validator::make($request->all(), [ 
	      'comment' => 'required',
    	]);
    	if ($validatecomment->fails()) {    
			return response()->json("Enter Comment", 400);
		}
		$token = $request->header('token');
		if (!empty($token)) {
        $validatetoken = DB::table('user')
        ->select('user.verify_token')
        ->where('verify_token','=',$token)
        ->where('status_id','=',1)
        ->first();
        if ($validatetoken) {
        $adds[] = array(
			'comment_text'	=> $request->comment,
			'post_id'		=> $request->postId,
			'verify_token' 	=> $request->header('token'),
			'status_id' 	=> 1,
			'created_at' 	=> date('Y-m-d h:i:s'),
		);
		$insert = DB::table('comment')->insert($adds);
		$lastid = DB::getPdo()->lastInsertId();
		$getsubmitedcomment = DB::table('comment')
        ->select('comment.comment_text','user.user_fullname','user.user_profilepicture')
        ->join('user','user.verify_token', '=','comment.verify_token')
        ->where('comment.comment_id','=',$lastid)
        ->where('comment.status_id','=',1)
        ->first();
		return json_encode($getsubmitedcomment);
        }else{
            return response()->json("Invalid Auth Token", 400);
        }
        }else{
            return response()->json("Auth Token Does not Exist", 400);
        }
	}
	public function getpostcomment(Request $request){
		$validate = Validator::make($request->all(), [ 
	      'postId' 	=> 'required',
	    ]);
    	if ($validate->fails()) {    
			return response()->json("In Valid Post Id", 400);
		}
		$getfollowerspost = DB::table('comment')
        ->select('comment.comment_text','user.user_fullname','user.user_profilepicture')
        ->join('user','user.verify_token', '=','comment.verify_token')
        ->where('comment.post_id','=',$request->postId)
        ->where('comment.status_id','=',1)
        ->orderByDesc('comment_id')
        ->get();
		return json_encode($getfollowerspost);
    }	
   // User Complete Profile Edit Start
 //    public function editprofile(Request $request){
 //     	$validate = Validator::make($request->all(), [ 
	//       'fullname' => 'required',
	//       'email'	 => 'required',
	//       'password' => 'required|size:6',
	//       'phone'	 => 'required',
	//       'city'	 => 'required',
 //    	]);
 //    	if ($validate->fails()) {    
	// 		return response()->json("Enter All Fields To Signup", 400);
	// 	}
 //    	$validateemail = Validator::make($request->all(), [ 
	//       'email'	 => 'unique:user,user_email',
	//     ]);
 //     	if ($validateemail->fails()) {    
	// 		return response()->json("Email Already Exist", 400);
	// 	}
	// 	$validateinterest = Validator::make($request->all(), [ 
	//       'interests' => 'required|min:1',
 //    	]);
 //    	if ($validateinterest->fails()) {    
	// 		return response()->json("Select At least 1 Interest To Proceed", 400);
	// 	}
	// 	$validatemax = Validator::make($request->all(), [ 
	//       'interests' => 'required|max:3',
 //    	]);
 //    	if ($validatemax->fails()) {    
	// 		return response()->json("More Than 3 Interest Are Not Allowed", 400);
	// 	}
 //     	if ($request->role == 2) {
	// 		$getcategory = $request->category;
	// 	}else{
	// 		$getcategory = "";
	// 	}
	// 	$updateprofileinfo  = DB::table('user')
	// 	->where('verify_token','=',$request->header('token'))
	// 	->update([
 //   			'user_fullname'		=> $request->fullname,
	// 		'user_email' 		=> $request->email,
	// 		'user_password' 	=> $request->password,
	// 		'user_phoneno' 		=> $request->phone,
	// 		'user_city'			=> $request->city,
	// 		'role_id' 			=> $request->role,
	// 		'user_category' 	=> $getcategory,
	// 		'updated_at' 		=> date('Y-m-d h:i:s'),
	// 		'updated_by' 		=> $request->userId,
	// 	]); 
	// 	$getuserinterest = DB::table('userinterest')
 //        ->select('*')
 //        ->where('userinterest.verify_token','=',$request->header('token'))
 //        ->where('userinterest.status_id','=',1)
 //        ->get();
 //        if (!empty($getuserinterest)) {
 //        $inactiveuserinterest  = DB::table('userinterest')
	// 	->where('verify_token','=',$request->header('token'))
	// 	->where('status_id','=',1)
	// 	->update([
 //   			'status_id' 		=> 2,
	// 	]); 
 //        }
	// 	$getinterest = $request->interests;
	// 	foreach ((array)$getinterest as $userinterest) {
	// 		$adds[] = array(
	// 		'intrest_id'		=> $userinterest,
	// 		'verify_token' 		=> $request->header('token'),
	// 		'status_id' 		=> 1,
	// 		'created_at' 		=> date('Y-m-d h:i:s'),
	// 		);
	// 	}
	// 	$insert = DB::table('userinterest')->insert($adds);
	// 	if ($updateprofileinfo) {
	// 		return json_encode(true);
	// 	}else{
	// 		return response()->json("Oops! Something Went Wrong", 400);	
	// 	}
	// }
	// User Complete Profile Edit End
	public function editprofile(Request $request){
     	$validate = Validator::make($request->all(), [ 
	      'fullname' => 'required',
	      'phone'	 => 'required',
	      'city'	 => 'required',
    	]);
    	$updateprofileinfo  = DB::table('user')
		->where('verify_token','=',$request->header('token'))
		->update([
   			'user_fullname'		=> $request->fullname,
			'user_phoneno' 		=> $request->phone,
			'user_city'			=> $request->city,
			'updated_at' 		=> date('Y-m-d h:i:s'),
			'updated_by' 		=> $request->userId,
		]); 
		if ($updateprofileinfo) {
		$getparam = $request->header('token');
		$getprofileinfo = DB::table('user')
		->select('user_fullname as name','user_email as email','user_phoneno as phone','verify_token as id','user_id as userId','role_id as type','user_profilepicture as avatar','user_coverpicture as cover','user.user_city')
		->where('verify_token','=',$getparam)
		->where('status_id','=',1)
		->first();
		$getprofileinterest = DB::table('userinterest')
		->join('intrest','intrest.intrest_id', '=','userinterest.intrest_id')
		->select('intrest.intrest_name')
		->where('userinterest.verify_token','=',$getparam)
		->where('userinterest.status_id','=',1)
		->get();
		$getfollowing = DB::table('follower')
		->join('user','user.user_id', '=','follower.user_id')
		->select('user.user_id','user.user_fullname','user.user_profilepicture')
		->where('follower.verify_token','=',$getparam)
		->where('user.status_id','=',1)
		->count();
		$getid = DB::table('user')
		->select('user.user_id')
		->where('user.verify_token','=',$getparam)
		->where('user.status_id','=',1)
		->first();
		$getfollowers = DB::table('follower')
		->select('follower.verify_token')
		->where('follower.user_id','=',$getid->user_id)
		->where('follower.status_id','=',1)
		->count();
		$getprofileinfo->followers = $getfollowers;
		$getprofileinfo->following = $getfollowing;
		$allData = array("userinfo" => $getprofileinfo,"interest" => $getprofileinterest);
			return json_encode($allData);
		}else{
			return response()->json("Oops! Something Went Wrong", 400);	
		}
	}
	public function postreporttypes(Request $request){
		$getpostreporttypes = DB::table('postreporttype')
        ->select('postreporttype.postreporttype_id','postreporttype.postreporttype_name')
        ->where('postreporttype.status_id','=',1)
        ->get();
		if($getpostreporttypes){
			return json_encode($getpostreporttypes);
		}else{
			return response()->json("Oops! Something Went Wrong", 400);
		}
    }
    public function reportpost(Request $request){
		$validate = Validator::make($request->all(), [ 
	      'postId' 	=> 'required',
	    ]);
    	if ($validate->fails()) {    
			return response()->json("Invalid Post Id", 400);
		}
		$validatetoken = Validator::make($request->header(), [ 
	      'token' => 'required',
    	]);
    	if ($validatetoken->fails()) {    
			return response()->json("Auth Token Does not Exist", 400);
		}
		$validatetype = Validator::make($request->all(), [ 
	      'typeId' => 'required',
    	]);
    	if ($validatetype->fails()) {    
			return response()->json("Invalid Report Type", 400);
		}
		$token = $request->header('token');
		if (!empty($token)) {
        $validatetoken = DB::table('user')
        ->select('user.verify_token')
        ->where('verify_token','=',$token)
        ->where('status_id','=',1)
        ->first();
        if ($validatetoken) {
        $adds[] = array(
			'reportpost_comment'	=> $request->comment,
			'postreporttype_id'		=> $request->typeId,
			'post_id'				=> $request->postId,
			'verify_token' 			=> $request->header('token'),
			'status_id' 			=> 1,
			'created_at' 			=> date('Y-m-d h:i:s'),
		);
		$insert = DB::table('reportpost')->insert($adds);
		return json_encode($adds);
        }else{
            return response()->json("Invalid Auth Token", 400);
        }
        }else{
            return response()->json("Auth Token Does not Exist", 400);
        }
	}
	public function blockuser(Request $request){
		$validate = Validator::make($request->all(), [ 
	      'userId' 	=> 'required',
	    ]);
    	if ($validate->fails()) {    
			return response()->json("In Valid User Id", 400);
		}
		$validatetoken = Validator::make($request->header(), [ 
	      'token' => 'required',
    	]);
    	if ($validatetoken->fails()) {    
			return response()->json("Auth Token Does not Exist", 400);
		}
		$token = $request->header('token');
		if (!empty($token)) {
        $validatetoken = DB::table('user')
        ->select('user.verify_token')
        ->where('verify_token','=',$token)
        ->where('status_id','=',1)
        ->first();
        if ($validatetoken) {
        if ($request->type ==  "block") {
        $adds[] = array(
			'user_id'				=> $request->userId,
			'verify_token' 			=> $request->header('token'),
			'status_id' 			=> 1,
			'created_at' 			=> date('Y-m-d h:i:s'),
		);
		$insert = DB::table('blockuser')->insert($adds);
        return json_encode($adds);
        }else{
        $unblockuser  = DB::table('blockuser')
			->where('user_id','=',$request->userId)
			->where('verify_token','=',$request->header('token'))
			->where('status_id','=',1)
			->update(['status_id' => 2,
					  'updated_at' => date('Y-m-d h:i:s'),
		]); 
		return json_encode("User Unblock");
        }
        }else{
            return response()->json("Invalid Auth Token", 400);
        }
        }else{
            return response()->json("Auth Token Does not Exist", 400);
        }
	}
	public function blockuserlist(Request $request){
		$validatetoken = Validator::make($request->header(), [ 
	      'token' => 'required',
    	]);
    	if ($validatetoken->fails()) {    
			return response()->json("Auth Token Does not Exist", 400);
		}
		$token = $request->header('token');
		if (!empty($token)) {
        $validatetoken = DB::table('user')
        ->select('user.verify_token')
        ->where('verify_token','=',$token)
        ->where('status_id','=',1)
        ->first();
        if ($validatetoken) {
       	$getblockuserlist = DB::table('blockuser')
       	->join('user','user.user_id','=','blockuser.user_id')
        ->select('user.user_id','user.user_fullname','user.user_profilepicture')
        ->where('blockuser.verify_token','=',$request->header('token'))
        ->where('blockuser.status_id','=',1)
        ->get();
        if ($getblockuserlist) {
        	return json_encode($getblockuserlist);
        }else{
        	return response()->json("Oops! Something Went Wrong", 400);
        }
        }else{
            return response()->json("Invalid Auth Token", 400);
        }
        }else{
            return response()->json("Auth Token Does not Exist", 400);
        }
	}
	public function popularuserlist(Request $request){
		$token = $request->header('token');
		if (!empty($token)) {
		$validatetoken = DB::table('user')
        ->select('user.verify_token')
        ->where('verify_token','=',$token)
        ->where('status_id','=',1)
        ->first();
        if ($validatetoken) {
			$getmaxfollowers = DB::table('follower')
			->join('user','user.user_id','=','follower.user_id')
			->select('user.user_id','user.user_fullname','user.user_profilepicture',DB::raw('count(*) as total'))
			->where('user.status_id','=',1)
			->groupBy('follower.user_id')
			->orderByDesc('total')
			->limit(10)
			->get();
			$index = 0;
			foreach ($getmaxfollowers as $getmaxfollowerss) {
        	$checkfollower = DB::table('follower')
			->select('follower.verify_token')
			->where('follower.user_id','=',$getmaxfollowerss->user_id)
			->where('follower.verify_token','=',$request->header('token'))
			->where('follower.status_id','=',1)
			->count();
			$getmaxfollowers[$index]->follow = $checkfollower;
			$index++;
			}
			return json_encode($getmaxfollowers);
			}else{
				return response()->json("Invalid Auth Token", 400);
			}
        	
        }else{
	        $getmaxfollowers = DB::table('follower')
			->join('user','user.user_id','=','follower.user_id')
			->select('user.user_id','user.user_fullname','user.user_profilepicture',DB::raw('count(*) as total'))
			->where('user.status_id','=',1)
			->groupBy('follower.user_id')
			->orderByDesc('total')
			->limit(10)
			->get();
			return json_encode($getmaxfollowers);
        }
    }
	public function searchuser(Request $request){
		$validatesearch = Validator::make($request->all(), [ 
	      'search' => 'required',
    	]);
    	if ($validatesearch->fails()) {    
			return response()->json("Type To Search", 400);
		}
			$getsearchuserfollower;
			$token = $request->header('token');
			if (!empty($token)) {
			$validatetoken = DB::table('user')
	        ->select('user.verify_token')
	        ->where('verify_token','=',$token)
	        ->where('status_id','=',1)
	        ->first();
	        if ($validatetoken) {
			$getsearchuserfollower = DB::table('user')
			->select('user.user_id','user.user_fullname','user.user_profilepicture')
			->where('user.user_fullname','like','%'.$request->search.'%')
			->where('user.verify_token','!=',$request->header('token'))
			->where('user.status_id','=',1)
			->get();
			$index = 0;
			foreach ($getsearchuserfollower as $getsearchuserfollowers) {
        	$checkfollower = DB::table('follower')
			->select('follower.verify_token')
			->where('follower.user_id','=',$getsearchuserfollowers->user_id)
			->where('follower.verify_token','=',$request->header('token'))
			->where('follower.status_id','=',1)
			->count();
			$getsearchuserfollower[$index]->follow = $checkfollower;
			$index++;
        	}
			}else{
				return response()->json("Invalid Auth Token", 400);
			}
			}else{
			$getsearchuserfollower = DB::table('user')
			->select('user.user_id','user.user_fullname','user.user_profilepicture')
			->where('user.user_fullname','like','%'.$request->search.'%')
			->where('user.status_id','=',1)
			->get();
			}
			return json_encode($getsearchuserfollower);
      
   	}
   	public function userpost(Request $request){
   		$validatetype = Validator::make($request->all(), [ 
	      'type' => 'required',
    	]);
    	if ($validatetype->fails()) {    
			return response()->json("Invalid Request", 400);
		}
		if ($request->type == "user") {
		$validateuserId = Validator::make($request->all(), [ 
	      'userId' => 'required',
    	]);
    	if ($validateuserId->fails()) {    
			return response()->json("Invalid User", 400);
		}
		$checklike;
		$totalchecklike;
		$checktotalcomment;
		$getuserpost;
		$gettoken = DB::table('user')
        ->select('user.verify_token')
        ->where('user_id','=',$request->userId)
        ->where('status_id','=',1)
        ->first();
        if (!empty($gettoken)) {
        $getuserpost = DB::table('post')
	        ->select('post.post_title','post.post_video','post.post_thumbnail','user.user_fullname','user.user_profilepicture','user.user_id','post.post_id')
	        ->join('user','user.verify_token', '=','post.verify_token')
	        ->where('post.status_id','=',1)
	        ->where('post.verify_token','=',$gettoken->verify_token)
	        ->orderByDesc('post.post_id')
	        ->get();
        $index = 0;
        foreach ($getuserpost as $getuserposts) {
        	$checklike = DB::table('likee')
			->select('*')
			->where('likee.post_id','=',$getuserposts->post_id)
			->where('likee.verify_token','=',$request->header('token'))
			->where('likee.status_id','=',1)
			->count();
			$getuserpost[$index]->like = $checklike;
			$totalchecklike = DB::table('likee')
			->select('*')
			->where('likee.post_id','=',$getuserposts->post_id)
			->where('likee.status_id','=',1)
			->count();
			$getuserpost[$index]->totalLikes = $totalchecklike;
			$checktotalcomment = DB::table('comment')
			->select('*')
			->where('comment.post_id','=',$getuserposts->post_id)
			->where('comment.status_id','=',1)
			->count();
			$getuserpost[$index]->totalComments = $checktotalcomment;
			$index++;
        }
        return json_encode($getuserpost);
		}else{
            return response()->json("Invalid Auth Token", 400);
        }
		}else{
		$checklike;
		$totalchecklike;
		$checktotalcomment;
		$token = $request->header('token');
		if (!empty($token)) {
        $validatetoken = DB::table('user')
        ->select('user.verify_token')
        ->where('verify_token','=',$token)
        ->where('status_id','=',1)
        ->first();
        if ($validatetoken) {   
        $validatetoken = Validator::make($request->header(), [ 
	      'token' => 'required',
    	]);
    	if ($validatetoken->fails()) {    
			return response()->json("Invalid Token", 400);
		}
		$checklike;
		$totalchecklike;
		$checktotalcomment;
		$getloginuserpost;
	    $getloginuserpost = DB::table('post')
	        ->select('post.post_title','post.post_video','post.post_thumbnail','user.user_fullname','user.user_profilepicture','user.user_id','post.post_id')
	        ->join('user','user.verify_token', '=','post.verify_token')
	        ->where('post.status_id','=',1)
	        ->where('post.verify_token','=',$token)
	        ->orderByDesc('post.post_id')
	        ->get();
        $index = 0;
        foreach ($getloginuserpost as $getloginuserposts) {
        	$checklike = DB::table('likee')
			->select('*')
			->where('likee.post_id','=',$getloginuserposts->post_id)
			->where('likee.verify_token','=',$request->header('token'))
			->where('likee.status_id','=',1)
			->count();
			$getloginuserpost[$index]->like = $checklike;
			$totalchecklike = DB::table('likee')
			->select('*')
			->where('likee.post_id','=',$getloginuserposts->post_id)
			->where('likee.status_id','=',1)
			->count();
			$getloginuserpost[$index]->totalLikes = $totalchecklike;
			$checktotalcomment = DB::table('comment')
			->select('*')
			->where('comment.post_id','=',$getloginuserposts->post_id)
			->where('comment.status_id','=',1)
			->count();
			$getloginuserpost[$index]->totalComments = $checktotalcomment;
			$index++;
        }
        return json_encode($getloginuserpost);
	    }else{
            return response()->json("Invalid Auth Token", 400);
        }
        }else{
            return response()->json("Auth Token Does not Exist", 400);
        }
		}
	}
	public function deletepost(Request $request){
		$validatetoken = Validator::make($request->header(), [ 
	      'token' => 'required',
    	]);
    	if ($validatetoken->fails()) {    
			return response()->json("Invalid Auth Token", 400);
		}
		$validatepostid = Validator::make($request->all(), [ 
	      'id' => 'required',
    	]);
    	if ($validatepostid->fails()) {    
			return response()->json("Invalid Post", 400);
		}
			$token = $request->header('token');
			if (!empty($token)) {
			$validatetoken = DB::table('user')
	        ->select('user.verify_token')
	        ->where('verify_token','=',$token)
	        ->where('status_id','=',1)
	        ->first();
	        if ($validatetoken) {
				$deletepost  = DB::table('post')
				->where('verify_token','=',$request->header('token'))
				->where('post_id','=',$request->all('id'))
				->update([
			   	'status_id' => 2,
				]);   	
				return json_encode($deletepost);
			}else{
            	return response()->json("Invalid Auth Token", 400);
        	}
	   		}else{
	            return response()->json("Auth Token Does not Exist", 400);
	        }
	}
}