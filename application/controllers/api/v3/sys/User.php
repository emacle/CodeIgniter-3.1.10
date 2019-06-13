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
        $this->load->model('Base_model');
        $this->load->model('User_model');
        // $this->config->load('config', true);
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

//        $message = [
//            "code" => 20000,
//            "data" => [
//                "__FUNCTION__" =>  __FUNCTION__,
//                "__CLASS__" => __CLASS__,
//                "uri" => $this->uri
//            ],
//
//        ];
//        "data": {
//            "__FUNCTION__": "router_get",
//            "__CLASS__": "User",
//            "uri": {
//                    "keyval": [],
//              "uri_string": "api/v2/user/router",
//              "segments": {
//                        "1": "api",
//                "2": "v2",
//                "3": "user",
//                "4": "router"
//              },
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
                ->count_all_results('sys_user_token') > 0;
    }

    private function _insert_token($token, $data)
    {
        $data['token'] = $token;

        return $this->rest->db
            ->set($data)
            ->insert('sys_user_token');
    }

    private function _update_token($token, $data)
    {
        return $this->rest->db
            ->where('token', $token)
            ->update('auth', $data);
    }

    // 查
    function view_post()
    {
        $uri = $this->uri->uri_string;
        $Token = $this->input->get_request_header('X-Token', TRUE);

        $retPerm = $this->permission->HasPermit($Token, $uri);
        if ($retPerm['code'] != 50000) {
            $this->set_response($retPerm, REST_Controller::HTTP_OK);
            return;
        }

        $parms = $this->post();
        //  $type = $parms['type'];
        $filters = $parms['filters'];
        $sort = $parms['sort'];
        $page = $parms['page'];
        $pageSize = $parms['pageSize'];

        // 1. 找出用户角色拥有的机构查看权限
        // 2. 用户列表只能是这些机构下的用户
        $DeptArr = $this->User_model->getCurrentDeptByToken($Token);
        $DeptListArr = [];
        foreach ($DeptArr as $k => $v) {
            array_push($DeptListArr, $v['id']);
        }

        $deptIdStr = implode(",", $DeptListArr); // string(9) "1,2,3,4,5"

        $UserArr = $this->User_model->getUserList($deptIdStr, $filters, $sort, $page, $pageSize);

        $total = $this->User_model->getUserListCnt($deptIdStr, $filters);

        // 遍历所有用户所属角色的机构信息
        foreach ($UserArr as $k => $v) {
            $UserArr[$k]['role'] = [];
            $UserArr[$k]['roledept'] = [];
            $RoleArr = $this->User_model->getUserRoles($v['id']);
            foreach ($RoleArr as $kk => $vv) {
                array_push($UserArr[$k]['role'], $vv['id']);
                $RoleDeptArr = $this->User_model->getUserRolesDept($v['id'], $vv['id']);

                $UserArr[$k]['roledept'][$vv['name'] . '-' . $vv['id']] = [];
                foreach ($RoleDeptArr as $kkk => $vvv) {
                    array_push($UserArr[$k]['roledept'][$vv['name'] . '-' . $vv['id']], $vvv['dept_id']);
                }
            }
        }

        $message = [
            "code" => 20000,
            "data" => [
                'items' => $UserArr,
                'total' => intval($total)
            ]
        ];
        $this->set_response($message, REST_Controller::HTTP_OK);
    }

    // 增
    function add_post()
    {
        $uri = $this->uri->uri_string;
        $Token = $this->input->get_request_header('X-Token', TRUE);
        $retPerm = $this->permission->HasPermit($Token, $uri);
        if ($retPerm['code'] != 50000) {
            $this->set_response($retPerm, REST_Controller::HTTP_OK);
            return;
        }

        $parms = $this->post();  // 获取表单参数，类型为数组

        // 参数数据预处理
        $failed = false;
        foreach ($parms['roledepts'] as $k => $v) {
            if (!array_key_exists('dept_id', $v) || !count($v['dept_id'])) {
                unset($parms['roledepts'][$k]);
            }
        }

        if (!count($parms['roledepts'])) {
            $failed = true;
        }

        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '所选的角色没有关联对应机构, 请检查'
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $RoleDeptArr = $parms['roledepts'];
        unset($parms['role']);    // 剔除role数组
        unset($parms['roledept']);     // 前台传参过来多余的空数组
        unset($parms['roledepts']);    // 剔除role数组

        // 加入新增时间
        $parms['create_time'] = time();
        $parms['password'] = md5($parms['password']);

        $user_id = $this->Base_model->_insert_key('sys_user', $parms);
        if (!$user_id) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['username'] . ' - 用户新增失败'
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $failed = false;
        $failedArr = [];
        foreach ($RoleDeptArr as $k => $v) {
            foreach ($v['dept_id'] as $kk => $vv) {
                $arr = ['user_id' => $user_id, 'role_id' => $v['role_id'], 'dept_id' => $vv];
                $ret = $this->Base_model->_insert_key('sys_user_role', $arr);
                if (!$ret) {
                    $failed = true;
                    array_push($failedArr, $arr);
                }
            }
        }

        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '用户关联角色机构失败 ' . json_encode($failedArr)
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['username'] . ' - 用户新增成功'
        ];
        $this->set_response($message, REST_Controller::HTTP_OK);
    }

    // 改
    function edit_post()
    {
        $uri = $this->uri->uri_string;
        $Token = $this->input->get_request_header('X-Token', TRUE);
        $retPerm = $this->permission->HasPermit($Token, $uri);
        if ($retPerm['code'] != 50000) {
            $this->set_response($retPerm, REST_Controller::HTTP_OK);
            return;
        }

        // $id = $this->post('id'); // POST param
        $parms = $this->post();  // 获取表单参数，类型为数组
        // var_dump($parms['path']);

        // 参数检验/数据预处理
        // 超级管理员角色不允许修改
        if ($parms['id'] == 1) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['username'] . ' - 超级管理员用户不允许修改'
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $failed = false;
        foreach ($parms['roledepts'] as $k => $v) {
            if (!array_key_exists('dept_id', $v) || !count($v['dept_id'])) {
                unset($parms['roledepts'][$k]);
            }
        }

        if (!count($parms['roledepts'])) {
            $failed = true;
        }

        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '所选的角色没有关联对应机构, 请检查'
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $id = $parms['id'];
        $RoleArr = []; // 前台传参数过来的 role_id, dept_id对
        foreach ($parms['roledepts'] as $k => $v) {
            foreach ($v['dept_id'] as $kk => $vv) {
                array_push($RoleArr, ['user_id' => $id, 'role_id' => $v['role_id'], 'dept_id' => $vv]);
            }
        }

        unset($parms['role']);  // 剔除role数组 多余 使用roledepts替换
        unset($parms['roledept']);  // 剔除role数组 多余 使用roledepts替换
        unset($parms['roledepts']);
        unset($parms['id']);    // 剔除索引id
        unset($parms['password']);    // 剔除密码

        $where = ["id" => $id];

        if (!$this->Base_model->_update_key('sys_user', $parms, $where)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['username'] . ' - 用户更新错误'
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $RoleSqlArr = $this->User_model->getRolesByUserId($id);

        $AddArr = $this->permission->array_diff_assoc2($RoleArr, $RoleSqlArr);
        // var_dump('------------只存在于前台传参 做添加操作-------------');
        // var_dump($AddArr);
        $failed = false;
        $failedArr = [];
        foreach ($AddArr as $k => $v) {
            $ret = $this->Base_model->_insert_key('sys_user_role', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '用户关联角色失败 ' . json_encode($failedArr)
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $DelArr = $this->permission->array_diff_assoc2($RoleSqlArr, $RoleArr);
        // var_dump('------------只存在于后台数据库 删除操作-------------');
        // var_dump($DelArr);
        $failed = false;
        $failedArr = [];
        foreach ($DelArr as $k => $v) {
            $ret = $this->Base_model->_delete_key('sys_user_role', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '用户关联角色失败 ' . json_encode($failedArr)
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['username'] . ' - 用户更新成功'
        ];
        $this->set_response($message, REST_Controller::HTTP_OK);
    }

    // 删
    function del_post()
    {
        $uri = $this->uri->uri_string;
        $Token = $this->input->get_request_header('X-Token', TRUE);
        $retPerm = $this->permission->HasPermit($Token, $uri);
        if ($retPerm['code'] != 50000) {
            $this->set_response($retPerm, REST_Controller::HTTP_OK);
            return;
        }

        $parms = $this->post();  // 获取表单参数，类型为数组
        // var_dump($parms['path']);

        // 参数检验/数据预处理
        // 超级管理员角色不允许删除
        if ($parms['id'] == 1) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['username'] . ' - 超级管理员不允许删除'
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        // 删除外键关联表 sys_user_role
        $this->Base_model->_delete_key('sys_user_role', ['user_id' => $parms['id']]);

        // 删除基础表 sys_user
        if (!$this->Base_model->_delete_key('sys_user', $parms)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['username'] . ' - 用户删除错误'
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['username'] . ' - 用户删除成功'
        ];
        $this->set_response($message, REST_Controller::HTTP_OK);

    }

    function login_post()
    {
        $username = $this->post('username'); // POST param
        $password = $this->post('password'); // POST param
        $verify = $this->post('verify'); // POST param
        $verifycode = $this->post('verifycode'); // POST param

        $this->load->driver('cache');
        if ($verifycode !== $this->cache->redis->get($verify)) {
            $message = [
                "code" => 60205,
                "message" => '验证码错误！'
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $result = $this->User_model->validate($username, md5($password));

        // 用户名密码正确 生成token 返回
        if ($result['success']) {
            $Token = $this->_generate_token();
            $create_time = time();
            $expire_time = $create_time + 2 * 60 * 60;  // 2小时过期

            $lastLoginRet = $this->User_model->getLastLoginRole($result['userinfo']['id']);

            if ($lastLoginRet['code'] == '20000') {
                $CurrentRole = $lastLoginRet['role_id'];
            } else {
                $ret = $this->User_model->getCurrentRole($result['userinfo']['id']);
                if ($ret['code'] !== '20000') {
                    // 自定义code 未分配角色或角色被删除，用户没有可用角色
                    $this->set_response($ret, REST_Controller::HTTP_OK);
                    return;
                }
                $CurrentRole = $ret['role_id'];
            }

            $data = [
                'user_id' => $result['userinfo']['id'],
                'role_id' => $CurrentRole,
                'expire_time' => $expire_time,
                'create_time' => $create_time
            ];

            if (!$this->_insert_token($Token, $data)) {
                $message = [
                    "code" => 20000,
                    "message" => 'Token 创建失败, 请联系管理员.'
                ];
                $this->set_response($message, REST_Controller::HTTP_OK);
                return;
            }

            $message = [
                "code" => 20000,
                "data" => [
                    "token" => $Token
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

    // 根据token拉取用户信息 getUserInfo
    function info_get()
    {
        // $result = $this->some_model(); // 获取用户信息
        // var_dump($this->get('token'));
        $result['success'] = TRUE;

        // 真实token
        $Token = $this->input->get_request_header('X-Token', TRUE);
        // $this->get('token') // get 参数token

        $result = $this->User_model->getUserInfo($Token);

        // 获取用户信息成功
        if ($result['success']) {
            $info = $result['userinfo'];

            if ($Token !== $this->get('token')) {
                // 由前台切换角色 修改sys_user_token 表里 role_id 字段 并替换用户信息中的默认role_id
                // 判断切换后的角色是否有对应机构
                if (!$this->User_model->roleHasDept($this->get('token'), $info['id'])) {
                    $message = [
                        "code" => 20000,
                        "type" => 'error',
                        "message" => '角色切换失败,该角色没有对应的部门机构，请联系管理员分配'
                    ];
                    $this->set_response($message, REST_Controller::HTTP_OK);
                    return;
                }

                if (!$this->Base_model->_update_key('sys_user_token', ['role_id' => $this->get('token')], ['token' => $Token])) {
                    $message = [
                        "code" => 20000,
                        "type" => 'error',
                        "message" => '角色切换失败'
                    ];
                    $this->set_response($message, REST_Controller::HTTP_OK);
                    return;
                }
            }

            // 附加信息
            $info['roles'] = $this->User_model->getUserRolesByToken($Token);
            $info['role_id'] = $this->User_model->getCurrentRoleByToken($Token); // 当前选择角色
            $info['introduction'] = "I am a super administrator";
            // $info['avatar'] = "https://wpimg.wallstcn.com/f778738c-e4f8-4870-b634-56703b4acafe.gif";
            $info['name'] = "Super Admin";
            $info['identify'] = "410000000000000000";
            $info['phone'] = "13633838282";

            // 当前用户的当前角色拥有的部门
//            $info['depts'] = $this->User_model->getCurrentDeptByToken($Token); // 当前选择部门;
//            $info['roleoptions'] = $this->User_model->getRoleOptions($Token); // 角色选择选项;

            $MenuTreeArr = $this->permission->getPermission($Token, 'menu', false);
            $asyncRouterMap = $this->permission->genVueRouter($MenuTreeArr, 'id', 'pid', 0);
            $CtrlPerm = $this->permission->getMenuCtrlPerm($Token);

            $info['ctrlperm'] = $CtrlPerm;
            //                "ctrlperm" => [
            //                    [
            //                        "path" => "/sys/menu/view"
            //                    ],
            //                    [
            //                        "path" => "/sys/menu/add"
            //                    ],
            //                    [
            //                        "path" => "/sys/menu/edit"
            //                    ],
            //                    [
            //                        "path" => "/sys/menu/del"
            //                    ],
            //                    [
            //                        "path" => "/sys/menu/download"
            //                    ]
            //                ],
            $info['asyncRouterMap'] = $asyncRouterMap;
            //        [
            //                [
            //                    "path" => '/sys',
            //                    "name" => 'sys',
            //                    "meta" => [
            //                        "title" => "系统管理",
            //                        "icon" => "sysset2"
            //                    ],
            //                    "component" => 'Layout',
            //                    "redirect" => '/sys/menu',
            //                    "children" => [
            //                        [
            //                            "path" => '/sys/menu',
            //                            "name" => 'menu',
            //                            "meta" => [
            //                                "title" => "菜单管理",
            //                                "icon" => "menu1"
            //                            ],
            //                            "component" => 'sys/menu/index',
            //                            "redirect" => '',
            //                            "children" => [
            //
            //                            ]
            //                        ],
            //                        [
            //                            "path" => '/sys/user',
            //                            "name" => 'user',
            //                            "meta" => [
            //                                "title" => "用户管理",
            //                                "icon" => "user"
            //                            ],
            //                            "component" => 'pdf/index',
            //                            "redirect" => '',
            //                            "children" => [
            //
            //                            ]
            //                        ],
            //                        [
            //                            "path" => '/sys/icon',
            //                            "name" => 'icon',
            //                            "meta" => [
            //                                "title" => "图标管理",
            //                                "icon" => "icon"
            //                            ],
            //                            "component" => 'svg-icons/index',
            //                            "redirect" => '',
            //                            "children" => [
            //
            //                            ]
            //                        ]
            //                    ]
            //                ],
            //                    [
            //                        "path" => '/sysx',
            //                        "name" => 'sysx',
            //                        "meta" => [
            //                            "title" => "其他管理",
            //                            "icon" => "plane"
            //                        ],
            //                        "component" => 'Layout',
            //                        "redirect" => '',
            //                        "children" => [
            //
            //                        ]
            //                    ]
            //                ]

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

    // 根据token拉取 treeselect 下拉选项菜单
    function roleoptions_get()
    {
        // 此 uri 可不做权限/token过期验证，则在菜单里，可以不加入此项路由path /sys/menu/treeoptions。
        //
        //        $uri = $this->uri->uri_string;
        //        $Token = $this->input->get_request_header('X-Token', TRUE);
        //        $retPerm = $this->permission->HasPermit($Token, $uri);
        //        if ($retPerm['code'] != 50000) {
        //            $this->set_response($retPerm, REST_Controller::HTTP_OK);
        //            return;
        //        }

        $Token = $this->input->get_request_header('X-Token', TRUE);
        $RoleArr = $this->User_model->getRoleOptions($Token);

        $message = [
            "code" => 20000,
            "data" => $RoleArr,
        ];
        $this->set_response($message, REST_Controller::HTTP_OK);
    }

    function deptoptions_get()
    {
        // 此 uri 可不做权限/token过期验证，则在菜单里，可以不加入此项路由path /sys/menu/treeoptions。
        //
        //        $uri = $this->uri->uri_string;
        //        $Token = $this->input->get_request_header('X-Token', TRUE);
        //        $retPerm = $this->permission->HasPermit($Token, $uri);
        //        if ($retPerm['code'] != 50000) {
        //            $this->set_response($retPerm, REST_Controller::HTTP_OK);
        //            return;
        //        }

        $Token = $this->input->get_request_header('X-Token', TRUE);
        $DeptArr = $this->User_model->getCurrentDeptByToken($Token);

        $message = [
            "code" => 20000,
            "data" => $DeptArr,
        ];
        $this->set_response($message, REST_Controller::HTTP_OK);
    }

    //    async router test get
    function router_get()
    {
//        $result = $this->some_model();
        $result['success'] = TRUE;

        // 获取用户信息成功
        if ($result['success']) {
//            $info = [
//                "roles" => ["admin", "editor"],
//                "introduction" => "I am a super administrator",
//                "avatar" => "https://wpimg.wallstcn.com/f778738c-e4f8-4870-b634-56703b4acafe.gif",
//                "name" => "Super Admin",
//                "identify" => "410000000000000000",
//                "phone" => "13633838282",
//                "asyncRouterMap" => [
//
//                ]
//            ];

            $message = [
                "code" => 20000,
                "data" => [
                    "asyncRouterMap" => [
                        [
                            "path" => '/sys',
                            "name" => 'sys',
                            "meta" => [
                                "title" => "系统管理",
                                "icon" => "nested"
                            ],
                            "component" => 'Layout',
                            "children" => [
                                [
                                    "path" => '/sys/menu',
                                    "name" => 'menu',
                                    "meta" => [
                                        "title" => "菜单管理",
                                        "icon" => "nested"
                                    ],
                                    "component" => 'index',
                                    "children" => [

                                    ]
                                ]
                            ]
                        ],
                        [
                            "path" => '/sysx',
                            "name" => 'sysx',
                            "meta" => [
                                "title" => "其他管理",
                                "icon" => "nested"
                            ],
                            "component" => 'Layout',
                            "children" => [

                            ]
                        ]
                    ],
                    "__FUNCTION__" => __FUNCTION__,
                    "__CLASS__" => __CLASS__,
                    "uri" => $this->uri
                ],

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

    function list_get()
    {
//        $result = $this->some_model();
        $result['success'] = TRUE;

        if ($result['success']) {
            $List = array(
                array('order_no' => '201805138451313131', 'timestamp' => 'iphone 7 ', 'username' => 'iphone 7 ', 'price' => 399, 'status' => 'success'),
                array('order_no' => '300000000000000000', 'timestamp' => 'iphone 7 ', 'username' => 'iphone 7 ', 'price' => 399, 'status' => 'pending'),
                array('order_no' => '444444444444444444', 'timestamp' => 'iphone 7 ', 'username' => 'iphone 7 ', 'price' => 399, 'status' => 'success'),
                array('order_no' => '888888888888888888', 'timestamp' => 'iphone 7 ', 'username' => 'iphone 7 ', 'price' => 399, 'status' => 'pending'),
            );

            $message = [
                "code" => 20000,
                "data" => [
                    "total" => count($List),
                    "items" => $List
                ]
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
    private
    function execCURL($ch)
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
    private
    function http_post($url, $param, $post_file = false)
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

            // $redirectUri = urlencode("http://yw.xxx.com/xxx/get-corp-weixin-code.html?redirect_uri=" . urlencode($localAuthUrl));
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

    function verifycode_get()
    {
        $this->load->library('Captcha');
        // redis/mysql 保存 code 与 verify
        $code = $this->captcha->getCaptcha();
        $this->load->driver('cache');
        $ret = $this->cache->redis->save($this->get('verify'), $code, 60);
        // response header 出现 redis_hit_verifycode: 1 表示 redis 连接正常且保存k/v
        header("redis_hit_verifycode: " . $ret);
        // header("veryfiycode: " . $this->get('verify') . "/" . $code);
        $this->captcha->showImg();
    }

    function corpauth_get()
    {
        $code = $this->get('code');

        // $corpId = 'xxxxxx';
        // $appSecret = 'xxxxxx';

        // code: 60206 微信认证失败统一代码
        if (!$code) {
            $message = [
                "code" => 60206,
                "data" => ["status" => 'fail', "msg" => 'code参数为空'],
                "message" => "code参数为空"
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        // 根据上面的回调参数获取用户详细信息。 已经传递过来code数据。
        $getCorpAccessTokenUrl = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=' . $corpId . '&corpsecret=' . $appSecret;

        $tokenInfo = $this->http_get($getCorpAccessTokenUrl);
        $tokenInfo = json_decode($tokenInfo["content"], true); // 获取的原始数据解码成json格式，如下
        //        array(4) {
        //        ["errcode"]=>
        //              int(0)
        //              ["errmsg"]=>
        //              string(2) "ok"
        //                    ["access_token"]=>
        //              string(214) "vP2TgGlg8-_N23PleQnq2q9SBnIfqCkkNMGZ71YoZ8V3R0lB8sJOy15ixco4kOxo8GZMlcgiJHm0hDXzbL6lG2BWleAqmJCrMEPdQj9goZaogVNBICmVrr-Fxz8YCIBUdf36BOq4E-Mt64OCrIUw1254Pxupi9RGOEFoWmMrJKgHnR_F0pjD-hJFZfTOIt7W2VujJq6hsle8SD9qTOZwzA"
        //                    ["expires_in"]=>
        //              int(7200)
        //            }
        if ($tokenInfo["errcode"] == 0) {
            $accessToken = $tokenInfo["access_token"];
        } else {
            $message = [
                "code" => 60206,
                "data" => ["status" => 'fail', "msg" => $tokenInfo["errmsg"] ? $tokenInfo["errmsg"] : "企业认证失败!"],
                "message" => $tokenInfo["errmsg"] ? $tokenInfo["errmsg"] : "企业认证失败!"
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

        $getUserIdUrl = 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=' . $accessToken . '&code=' . $code;
        $ajaxUserIdInfo = $this->http_get($getUserIdUrl);
        $userIdInfo = json_decode($ajaxUserIdInfo["content"], true); // 获取的原始数据解码成json格式，如下
        //        array(4) {
        //                ["UserId"]=>
        //                ["DeviceId"]=>
        //          string(0) ""
        //                ["errcode"]=>
        //          int(0)
        //          ["errmsg"]=>
        //          string(2) "ok"
        //        }

        if ($userIdInfo["errcode"] == 0) {
            if (array_key_exists("OpenId", $userIdInfo)) {
                $message = [
                    "code" => 60206,
                    "data" => ["success" => 'fail', "msg" => "不是企业成员!请联系企业管理员,添加您的账号的企业通讯录!"],
                    "message" => "不是企业成员!请联系企业管理员,添加您的账号的企业通讯录!"
                ];
                $this->set_response($message, REST_Controller::HTTP_OK);
                return;

            } else if (array_key_exists("UserId", $userIdInfo)) {
                $getUserInfoUrl = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=' . $accessToken . '&userid=' . $userIdInfo["UserId"];
                $ajaxUserInfo = $this->http_get($getUserInfoUrl);
                $userInfo = json_decode($ajaxUserInfo["content"], true); // 获取的原始数据解码成json格式，如下
                //                array(21) {
                //                    ["errcode"]=>
                //                      int(0)
                //                      ["errmsg"]=>
                //                      string(2) "ok"
                //                                        ["userid"]=>
                //                                        ["name"]=>
                //                      ["position"]=>
                //                      string(0) ""
                //                                        ["mobile"]=>
                //                                        ["gender"]=>
                //                      string(1) "1"
                //                                        ["email"]=>
                //                                        ["avatar"]=>
                //                      string(85) "http://p.qlogo.cn/bizmail/rZXu0u7ma4vOiaEWia7MTnUDmdDnfgh7R6iafX5832RczKmViadvgbVAhw/"
                //                                        ["status"]=>
                //                      int(1)
                //                    }

                if ($userInfo["errcode"] == 0) {
                    $user = $this->User_model->getUserInfoByTel($userInfo["mobile"]);
                    //                    array(1) {
                    //                                            [0]=>
                    //                      array(12) {
                    //                                                ["id"]=>
                    //                        string(1) "1"
                    //                                                ["username"]=>
                    //                        string(5) "admin"
                    //                                                ["tel"]=>
                    //                                                ["email"]=>
                    //                        string(17) "lmxdawn@gmail.com"
                    //
                    //                      }
                    //                    }

                    if (!empty($user)) {
                        // 成功，生成token 并根据token生成loginfo 并且将信息及 token 返回前台登录
                        $Token = $this->_generate_token();
                        $create_time = time();
                        $expire_time = $create_time + 2 * 60 * 60;  // 2小时过期

                        $lastLoginRet = $this->User_model->getLastLoginRole($user[0]['id']);

                        if ($lastLoginRet['code'] == '20000') {
                            $CurrentRole = $lastLoginRet['role_id'];
                        } else {
                            $ret = $this->User_model->getCurrentRole($user[0]['id']);
                            if ($ret['code'] !== '20000') {
                                // 自定义code 未分配角色或角色被删除，用户没有可用角色
                                $this->set_response($ret, REST_Controller::HTTP_OK);
                                return;
                            }
                            $CurrentRole = $ret['role_id'];
                        }

                        $data = [
                            'user_id' => $user[0]['id'],
                            'role_id' => $CurrentRole,
                            'expire_time' => $expire_time,
                            'create_time' => $create_time
                        ];

                        // TODO: 考虑sys_user_token 表加入类型字段判断是微信登录生成 token 还是账号密码登录生成 token
                        if (!$this->_insert_token($Token, $data)) {
                            $message = [
                                "code" => 60206,
                                "data" => ["status" => 'fail', "msg" => "Token 创建失败, 请联系管理员."],
                                "message" => 'Token 创建失败, 请联系管理员.'
                            ];
                            $this->set_response($message, REST_Controller::HTTP_OK);
                            return;
                        }

                        $message = [
                            "code" => 20000,
                            "data" => [
                                "status" => 'ok',
                                "token" => $Token
                            ]
                        ];
                        $this->set_response($message, REST_Controller::HTTP_OK);

                    } else {
                        $message = [
                            "code" => 60206,
                            "data" => ["status" => 'fail', "msg" => "此微信账号(" . $userInfo['name'] . ")没有与系统账号关联,请联系系统管理员!"],
                            "message" => "此微信账号(" . $userInfo['name'] . ")没有与系统账号关联,请联系系统管理员!"
                        ];
                        $this->set_response($message, REST_Controller::HTTP_OK);
                        return;
                    }

                } else {
                    $message = [
                        "code" => 60206,
                        "data" => ["status" => 'fail', "msg" => $userInfo["errmsg"]],
                        "message" => userInfo["errmsg"]
                    ];
                    $this->set_response($message, REST_Controller::HTTP_OK);
                    return;
                }
            }
        } else {
            $message = [
                "code" => 60206,
                "data" => ["status" => 'fail', "msg" => $userIdInfo["errmsg"]],
                "message" => $userIdInfo["errmsg"]
            ];
            $this->set_response($message, REST_Controller::HTTP_OK);
            return;
        }

    }
}
