<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use \Firebase\JWT\JWT;
use chriskacerguis\RestServer\RestController;

class ManageAuth
{
    private $CI;

    function __construct()
    {
        $this->CI = &get_instance();  //获取CI对象
    }

    // var_dump(uri_string()); => api/v2/sys/user/login

    //token及权限认证
    public function auth()
    {
        $uri_no_prefix = str_replace(config_item('jwt_api_prefix'), '', uri_string());  // /sys/user/login 不带 api/v2 前缀

        if (!in_array($uri_no_prefix, config_item('jwt_white_list'))) { // 不在白名单里需要校验 token
            $headers = $this->CI->input->request_headers();
            $Token = $headers['X-Token'];

            try {
                $decoded = JWT::decode($Token, config_item('jwt_key'), ['HS256']); //HS256方式，这里要和签发的时候对应
                $userId = $decoded->user_id;
                $retPerm = $this->CI->permission->HasPermit($userId, uri_string());
                if ($retPerm['code'] != 50000) {
                    $this->CI->response($retPerm, RestController::HTTP_OK);
                }
            } catch (\Firebase\JWT\ExpiredException $e) {  // access_token过期
                $message = [
                    "code" => 50014,
                    "message" => $e->getMessage()
                ];
                $this->CI->response($message, RestController::HTTP_UNAUTHORIZED);
            } catch (Exception $e) {  //其他错误
                $message = [
                    "code" => 50015,
                    "message" => $e->getMessage()
                ];
                $this->CI->response($message, RestController::HTTP_UNAUTHORIZED);
            }
        }
    } // public function auth() end
} // class ManageAuth end