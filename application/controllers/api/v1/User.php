<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
//To Solve File REST_Controller not found
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class User extends REST_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Api_model');
//        $this->load->model('Record_model');
//        $this->load->model('Dept_model', 'Dept');
//        $this->config->load('config', true);
    }

    public function index_get()
    {
        $this->load->view('login_view');
    }

    public function testapi_get()
    {
        echo "test api ok...";

        echo APPPATH . "\n";
        echo SELF . "\n";
        echo BASEPATH . "\n";
        echo FCPATH . "\n";
        echo SYSDIR . "\n";
        var_dump($this->config->item('rest_language'));
        var_dump($this->config->item('language'));

        var_dump($this->config);

    }

    public function phpinfo_get()
    {
        phpinfo();
    }

    public function testdb_get()
    {
        $this->load->database();
        $query = $this->db->query("show tables");
        var_dump($query);
        var_dump($query->result());
        var_dump($query->row_array());
//         有结果表明数据库连接正常 reslut() 与 row_array 结果有时不太一样
//        一般加载到时model里面使用。
    }


    /* Helper Methods */
    /**
     * 生成 token
     * @param
     * @return string 40个字符
     */
    private function _generate_token()
    {
        do {
            // Generate a random salt
            $salt = base_convert(bin2hex($this->security->get_random_bytes(64)), 16, 36);

            // If an error occurred, then fall back to the previous method
            if ($salt === FALSE) {
                $salt = hash('sha256', time() . mt_rand());
            }

            $new_key = substr($salt, 0, config_item('rest_key_length'));
        } while ($this->_token_exists($new_key));

        return $new_key;
    }

    /* Private Data Methods */

    private function _token_exists($token)
    {
        return $this->rest->db
                ->where('token', $token)
                ->count_all_results('auth') > 0;
    }

    private function _insert_token($token, $data)
    {
        $data['token'] = $token;
        $data['date_created'] = function_exists('now') ? now() : time();

        return $this->rest->db
            ->set($data)
            ->insert('auth');
    }

    private function _update_token($token, $data)
    {
        return $this->rest->db
            ->where('token', $token)
            ->update('auth', $data);
    }


    function login_post()
    {
        $username = $this->post('username'); // POST param
        $password = $this->post('password'); // POST param
//        var_dump($username);
//        var_dump($password);
        $input_account = $username;
        $input_password = md5($password);
        //        $result = $this->Api_model->app_user_login_validate($input_account, $input_password);
        // 用户名密码正确 生成token 返回
        if (1) {
            $token = $this->_generate_token();
//              "token" => $token
            $message = [
                "code" => 20000,
                "data" => [
                    "token" => "admin-token"
                ]
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
        } else {
            $message = [
                "code" => 60204,
                "message" => 'Account and password are incorrect.'
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
        }
    }

    // 根据token拉取用户信息 get
    function info_get()
    {
//        $result = $this->some_model();
        $result['success'] = TRUE;

        // 获取用户信息成功
        if ($result['success']) {
            $info = [
                "roles" => ["editor"],
                "introduction" => "I am a super administrator",
                "avatar" => "https://wpimg.wallstcn.com/f778738c-e4f8-4870-b634-56703b4acafe.gif",
                "name" => "Super Admin",
                "identify" => "410000000000000000",
                "phone" => "13633838282"
            ];

            $message = [
                "code" => 20000,
                "data" => $info,
                "_SERVER" => $_SERVER,
                "_GET" => $_GET
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
        } else {
            $message = [
                "code" => 50008,
                "message" => 'Login failed, unable to get user details.'
             ];

            $this->set_response($message, REST_Controller::HTTP_OK);
        }

    }

    function logout_post()
    {
        $message = [
            "code" => 20000,
            "data" => 'success'
        ];
        $this->set_response($message, REST_Controller::HTTP_OK);
    }

    function login()
    {
        $this->SET_HEADER;   // 设置php CI 处理 CORS 自定义头部


        if ($_SERVER["REQUEST_METHOD"] == 'POST') {   // 只处理post请求，否则options请求 500错误
            $json_params = file_get_contents('php://input');
            $data = json_decode($json_params, true);

            if (!empty($data)) {
                if (!empty($data['username']) && !empty($data['password'])) {
                    $username = $data['username'];
                    $password = $data['password'];
                    $input_account = $username;
                    $input_password = md5($password);
                    // $results = $this->phpIonicLoginAuthValidateLogin($username, $password);
                    $result = $this->Api_model->app_user_login_validate($input_account, $input_password);

                    //        $token=$_SERVER['x-auth-token'];
                    // 用户名密码正确 生成token 返回
                    $token = $this->createToken(10000);

                    $data = array(
                        "code" => 20000,
                        "data" => array(
                            "token" => "admin-token"
//                    "token" => $token
                        ),
                        "params" => $json_params
                    );

                    echo json_encode($data);
                    // 用户名密码不正确
//        return {
//        code:
//        60204,
//      message: 'Account and password are incorrect.'
//    }


                    if ($result['success']) {
                        echo json_encode($this->saveLoginInfo($result['userinfo']));
                    } else {
                        // 校验失败，写入token
                        $this->output->set_status_header(300);
                        echo '{"success": false,"message": "用户名或密码错误","jump":"","user":"' . $username . '"}';
                    }

                } else {
                    $results = array(
                        "result" => "Error - data incomplete!",
                    );

                    $jsonData = json_encode($results);
                    echo $jsonData;
                }
            } else { // no data post
                $results = array(
                    "result" => "Error - no data!",
                );
                $jsonData = json_encode($results);
                echo $jsonData;
            }
        }
    }

    // 根据token拉取用户信息 get
    function info()
    {
        $this->SET_HEADER;   // 设置php CI 处理 CORS 自定义头部

        if ($_SERVER["REQUEST_METHOD"] == 'GET') {   // 只处理post请求，否则options请求 500错误
            $json_params = file_get_contents('php://input');
            $data = json_decode($json_params, true);

//        $token=$_SERVER['x-auth-token'];

//   获取用户信息成功
            $info = array(
                "roles" => array(
                    "admin"
                ),
                "introduction" => "I am a super administrator",
                "avatar" => "https://wpimg.wallstcn.com/f778738c-e4f8-4870-b634-56703b4acafe.gif",
                "name" => "Super Admin",
            );

            echo json_encode(
                array(
                    "code" => 20000,
                    "data" => $info,
                    "_SERVER" => $_SERVER,
                    "_GET" => $_GET
                )
            );

// 获取用户信息失败
//        return {
//        code: 50008,
//      message: 'Login failed, unable to get user details.'
//    }
//        echo json_encode(
//            array(
//                "code" => 50008,
//                "message" => "Login failed, unable to get user details."
//            )
//        );

            return;

            if (!empty($data)) {
                if (!empty($data['username']) && !empty($data['password'])) {
                    $username = $data['username'];
                    $password = $data['password'];
                    $input_account = $username;
                    $input_password = md5($password);
                    // $results = $this->phpIonicLoginAuthValidateLogin($username, $password);
                    $result = $this->Api_model->app_user_login_validate($input_account, $input_password);

                    if ($result['success']) {
                        echo json_encode($this->saveLoginInfo($result['userinfo']));
                    } else {
                        // 校验失败，写入token
                        $this->output->set_status_header(300);
                        echo '{"success": false,"message": "用户名或密码错误","jump":"","user":"' . $username . '"}';
                    }

                } else {
                    $results = array(
                        "result" => "Error - data incomplete!",
                    );

                    $jsonData = json_encode($results);
                    echo $jsonData;
                }
            } else { // no data post
                $results = array(
                    "result" => "Error - no data!",
                );
                $jsonData = json_encode($results);
                echo $jsonData;
            }
        }
    }

    function logout()
    {
        $this->SET_HEADER;   // 设置php CI 处理 CORS 自定义头部
        if ($_SERVER["REQUEST_METHOD"] == 'POST') {   // 只处理post请求，否则options请求 500错误
            echo json_encode(array(
                "code" => 20000,
                "data" => 'sucess'
            ));
        }
    }

    /*
     * For 微信认证及全登录时保留登录日志信息
     */
    function saveLoginInfo($userinfo)
    {
        $token = md5($userinfo["name"] . date('y-m-d H:i:s', time()));
        $arr2 = array('token' => $token);
        $userinfo["token"] = $token;
        $this->Bas->saveAdd(
            'auth',
            array(
                'token' => $token,
                'expiredAt' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'onlineIp' => $this->input->ip_address(),
                'userLoginInfo' => json_encode($userinfo),
                'creatorId' => $userinfo["name"],
                'createdAt' => date('Y-m-d H:i:s')
            )
        );

        // 返回信息
        $results = array(
            "success" => true,
            "message" => "APP登陆成功",
            "user" => $userinfo,
            'session' => $_SESSION,
        );

        $this->Bas->saveEdit('userinfo', array('LASTLOGIN' => date('y-m-d H:i:s', time()), 'LASTIP' => $this->input->ip_address()), array('USERNAME' => $userinfo["name"]));
        return $results;
    }

    /**
     * 执行CURL请求，并封装返回对象
     */
    private function execCURL($ch)
    {
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $result = array('header' => '',
            'content' => '',
            'curl_error' => '',
            'http_code' => '',
            'last_url' => '');

        if ($error != "") {
            $result['curl_error'] = $error;
            return $result;
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $result['header'] = str_replace(array("\r\n", "\r", "\n"), "<br/>", substr($response, 0, $header_size));
        $result['content'] = substr($response, $header_size);
        $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result['last_url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $result["base_resp"] = array();
        $result["base_resp"]["ret"] = $result['http_code'] == 200 ? 0 : $result['http_code'];
        $result["base_resp"]["err_msg"] = $result['http_code'] == 200 ? "ok" : $result["curl_error"];

        return $result;
    }

    /**
     * GET 请求
     * @param string $url
     */
    private function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_VERBOSE, 1);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);

        // $sContent = curl_exec($oCurl);
        // $aStatus = curl_getinfo($oCurl);
        $sContent = $this->execCURL($oCurl);
        curl_close($oCurl);

        return $sContent;
    }

    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    private function http_post($url, $param, $post_file = false)
    {
        $oCurl = curl_init();

        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (PHP_VERSION_ID >= 50500 && class_exists('\CURLFile')) {
            $is_curlFile = true;
        } else {
            $is_curlFile = false;
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
            }
        }

        if ($post_file) {
            if ($is_curlFile) {
                foreach ($param as $key => $val) {
                    if (isset($val["tmp_name"])) {
                        $param[$key] = new \CURLFile(realpath($val["tmp_name"]), $val["type"], $val["name"]);
                    } else if (substr($val, 0, 1) == '@') {
                        $param[$key] = new \CURLFile(realpath(substr($val, 1)));
                    }
                }
            }
            $strPOST = $param;
        } else {
            $strPOST = json_encode($param);
        }

        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        curl_setopt($oCurl, CURLOPT_VERBOSE, 1);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);

        // $sContent = curl_exec($oCurl);
        // $aStatus  = curl_getinfo($oCurl);

        $sContent = $this->execCURL($oCurl);
        curl_close($oCurl);

        return $sContent;
    }

    function weixinAuth()
    {
        session_start();
        if ($_SERVER["REQUEST_METHOD"] == 'OPTIONS') {
            echo "options";
            die();
        }
        // $corpId = "wwxxxxxxxx";
        // $agentId = "100000";
        // $appSecret = "fsdfsfsdf";
        // $localAuthUrl = "http://dj.xxx.com:7000/hotcode/";

        if (!array_key_exists("code", $_REQUEST)) {
            $redirectUri = urlencode("http://yw.cttha.com/ksh/get-corp-weixin-code.html?redirect_uri=" . urlencode($localAuthUrl));
            $authUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $corpId . "&redirect_uri=" . $redirectUri . "&response_type=code&scope=snsapi_privateinfo&agentid=" . $agentId . "&state=STATE#wechat_redirect";
            echo json_encode(array("success" => false, "authUrl" => $authUrl));
            die();
        }
        $getCorpAccessTokenUrl = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=" . $corpId . "&corpsecret=" . $appSecret;
        $accessToken = "";
        if (false && $_SESSION["weixinAuth_accessToken"] && $_SESSION["weixinAuth_tokenTime"] && $_SESSION["weixinAuth_tokenExpires"] && time() - intval($_SESSION["weixinAuth_tokenTime"]) < intval($_SESSION["tokenExpires"])) {
            $accessToken = $_SESSION["weixinAuth_accessToken"];
        } else {
            $tokenInfo = $this->http_get($getCorpAccessTokenUrl);
            $tokenInfo = json_decode($tokenInfo["content"], true);
            if ($tokenInfo["errcode"] == 0) {
                $accessToken = $tokenInfo["access_token"];
                $_SESSION["weixinAuth_accessToke"] = $accessToken;
                $_SESSION["weixinAuth_tokenTime"] = time();
                $_SESSION["weixinAuth_tokenExpires"] = $tokenInfo["expires_in"];
            } else {
                echo json_encode(array("success" => false, "msg" => $tokenInfo["errmsg"] ? $tokenInfo["errmsg"] : "企业认证失败!"));
                die();
            }
        }
        $getUserIdUrl = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=" . $accessToken . "&code=" . $_REQUEST["code"];
        $ajaxUserIdInfo = $this->http_get($getUserIdUrl);
        // var_dump($ajaxUserIdInfo);die();
        $userIdInfo = json_decode($ajaxUserIdInfo["content"], true);
        if ($userIdInfo["errcode"] == 0) {
            if (array_key_exists("OpenId", $userIdInfo)) {
                echo json_encode(array("success" => false, "msg" => "不是企业成员!请联系企业管理员,添加您的账号的企业通讯录!"));
                die();
                // next(U.error("不是企业成员!请联系企业管理员,添加您的账号的企业通讯录!"));
            } else if (array_key_exists("UserId", $userIdInfo)) {
                $getUserInfoUrl = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserdetail?access_token=" . $accessToken;
                // 44468cd93cdfefb8a7f911b5e1f7dfd0
                $data = array("user_ticket" => $userIdInfo["user_ticket"]);
                $ajaxUserInfo = $this->http_post($getUserInfoUrl, $data);
                $userInfo = json_decode($ajaxUserInfo["content"], true);
                if ($userInfo["errcode"] == 0) {
                    $user = $this->Api_model->getUserByWxUserId($userIdInfo["UserId"]);
                    if ($user["success"]) {
                        echo json_encode($this->saveLoginInfo($user['userinfo']));
                        die();
                    } else {
                        $_SESSION["wxUserInfo"] = $userInfo;
                        echo json_encode(array("sessionid" => session_id(), "status" => -1, "success" => false, "msg" => "此微信账号(" . $userInfo["name"] . ")没有与系统账号关联,请用您的账号密码登录一次，完成首次绑定!"));
                        die();
                    }
                    return;
                } else {
                    echo json_encode(array("success" => false, "msg" => $userInfo["errmsg"]));
                    die();
                }
            }
        } else {
            echo json_encode(array("success" => false, "msg" => $userIdInfo["errmsg"]));
            die();
        }
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */