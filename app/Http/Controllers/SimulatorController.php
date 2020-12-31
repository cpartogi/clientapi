<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;


class SimulatorController extends Controller{	
	
	public function __construct() {		
	}	
	
	public function requestToken(){
		return view('simulator/requestToken'); 
	}	
	
	public function signOn(){
		return view('simulator/signOn'); 
	}	
}
