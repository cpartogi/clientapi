<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Users extends Model
{
    protected $table = 'tb_member_access_token';	

    public static function updateToken($member){
    	$id_member = $member[0]->id_member;
        $result = self::where('id_member', $id_member)->first();    
        $token =  md5($id_member.$member[0]->phone.config('config.chiper_text').time());
        if (empty($result)){
        	$memberToken = new Users;
        	$memberToken->id_member = $id_member;
        	$memberToken->token = $token;
        	$memberToken->save();
        }else{
        	$result->token = $token;
        	$result->save();
        }
        return $token;
    }

    public static function checkTokenUser($phone, $token){    	    	
    	$data =self::join("tb_member","tb_member.id_member",'=',"tb_member_access_token.id_member")
    	->where("tb_member.phone",'=',$phone)
    	->where("tb_member_access_token.token",'=',$token)
    	->select("tb_member.*")
    	->first();    	
    	return $data;    	
    }

    public static function getUserByParams( $params = array() ){
        $db = DB::table("tb_member");
        if (count($params)<0){
        }else{
            if ( isset($params["phone"]) ){
                $db->where("phone",'=',$params["phone"]);
            }
        }
        $data = $db->first();
        return $data;
    }

     public static function getTransactionByParams( $params = array() ){
        $db = DB::table("tb_transaction");
        if (isset($params["invoice_id"])){
            $db->where("invoice_id",'=',$params["invoice_id"]);
        }
        $data = $db->count();
        return $data;
    }

    public static function getTransfByParams( $params = array() ){
        $db = DB::table("tb_bank_transfer_payment");
        if (isset($params["invoice_id"])){
            $db->where("invoice_id",'=',$params["invoice_id"]);
        }
        $data = $db->count();
        return $data;
    }
        
		
}
