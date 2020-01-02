<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Role extends RestController
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Base_model');
        $this->load->model('Role_model');
        // $this->config->load('config', true);
    }

    public function index_get()
    {
        $this->load->view('login_view');
    }

    public function insertx_post()
    {
        // $id = $this->post('id'); // POST param
        $parms = $this->post();  // 获取表单参数，类型为数组
        var_dump($parms);
        $result = $this->Base_model->_insert_key('sys_role_perm', $parms);
        var_dump($result);
    }

    public function gettest_post()
    {
        $result = $this->Base_model->_get_key('sys_perm', 'perm_type,r_id rid', 'perm_type="role" and r_id=1');
        var_dump($result);
        var_dump($result[0]['perm_type']);
        var_dump($this->uri->uri_string);
        var_dump($this->uri);

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

    // 增
    function add_post()
    {
        $uri = $this->uri->uri_string;
        $Token = $this->input->get_request_header('X-Token', TRUE);
        $retPerm = $this->permission->HasPermit($Token, $uri);
        if ($retPerm['code'] != 50000) {
            $this->response($retPerm, RestController::HTTP_OK);
        }

        $parms = $this->post();  // 获取表单参数，类型为数组

        if ($this->Base_model->_key_exists('sys_role', ['name' => $parms['name']])) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 角色名重复'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 加入新增时间
        $parms['create_time'] = time();

        $role_id = $this->Base_model->_insert_key('sys_role', $parms);
        if (!$role_id) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 角色新增失败'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 生成该角色对应的权限: sys_perm, 权限类型为: role, 生成唯一的 perm_id
        $perm_id = $this->Base_model->_insert_key('sys_perm', ['perm_type' => 'role', "r_id" => $role_id]);
        if (!$perm_id) {
            var_dump($this->uri->uri_string . ' 生成该角色对应的权限: sys_perm, 失败...');
            var_dump(['perm_type' => 'role', "r_id" => $role_id]);
            return;
        }

        // 超级管理员角色自动拥有该权限 perm_id
        $role_perm_id = $this->Base_model->_insert_key('sys_role_perm', ["role_id" => 1, "perm_id" => $perm_id]);
        if (!$role_perm_id) {
            var_dump($this->uri->uri_string . ' 超级管理员角色自动拥有该权限 perm_id, 失败...');
            var_dump(["role_id" => 1, "perm_id" => $perm_id]);
            return;
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['name'] . ' - 角色新增成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 改
    function edit_post()
    {
        $uri = $this->uri->uri_string;
        $Token = $this->input->get_request_header('X-Token', TRUE);
        $retPerm = $this->permission->HasPermit($Token, $uri);
        if ($retPerm['code'] != 50000) {
            $this->response($retPerm, RestController::HTTP_OK);
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
                "message" => $parms['name'] . ' - 角色不允许修改'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $id = $parms['id'];
        unset($parms['id']); // 剃除索引id

        // 加入更新时间
        $parms['update_time'] = time();
        $where = ["id" => $id];

        if (!$this->Base_model->_update_key('sys_role', $parms, $where)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 角色更新错误'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['name'] . ' - 角色更新成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 删
    function del_post()
    {
        $uri = $this->uri->uri_string;
        $Token = $this->input->get_request_header('X-Token', TRUE);
        $retPerm = $this->permission->HasPermit($Token, $uri);
        if ($retPerm['code'] != 50000) {
            $this->response($retPerm, RestController::HTTP_OK);
        }

        $parms = $this->post();  // 获取表单参数，类型为数组
        // var_dump($parms['path']);

        // 参数检验/数据预处理
        // 超级管理员角色不允许删除
        if ($parms['id'] == 1) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 角色不允许删除'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 删除外键关联表 sys_role_perm , sys_perm, sys_role, sys_user_role
        // 1. 根据sys_role id及'role' 查找 perm_id
        // 2. 删除sys_role_perm 中perm_id记录
        // 3. 删除sys_perm中 perm_type='role' and r_id = role_id 记录,即第1步中获取的 perm_id， 一一对应
        // 4. 删除sys_user_role role_id 记录
        // 5. 删除sys_role 中 id = role_id 的记录
        $where = 'perm_type="role" and r_id=' . $parms['id'];
        $arr = $this->Base_model->_get_key('sys_perm', '*', $where);
        if (empty($arr)) {
            var_dump($this->uri->uri_string . ' 未查找到 sys_perm 表中记录');
            var_dump($where);
            return;
        }

        $perm_id = $arr[0]['id']; // 正常只有一条记录
        $this->Base_model->_delete_key('sys_role_perm', ['perm_id' => $perm_id]); // 必须删除权限id 因为超级管理员角色自动拥有该权限否则会造成删除关联错误
        $this->Base_model->_delete_key('sys_role_perm', ['role_id' => $parms['id']]); // 再删除该角色对应的权限id（原有的菜单）
        $this->Base_model->_delete_key('sys_perm', ['id' => $perm_id]);

        $this->Base_model->_delete_key('sys_user_role', ['role_id' => $parms['id']]);
        // 删除基础表 sys_role
        if (!$this->Base_model->_delete_key('sys_role', $parms)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 角色删除错误'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['name'] . ' - 角色删除成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 查
    function view_post()
    {
        $uri = $this->uri->uri_string;
        $Token = $this->input->get_request_header('X-Token', TRUE);

        $retPerm = $this->permission->HasPermit($Token, $uri);
        if ($retPerm['code'] != 50000) {
            $this->response($retPerm, RestController::HTTP_OK);
        }

        $RoleArr = $this->Role_model->getRoleList();
        $message = [
            "code" => 20000,
            "data" => $RoleArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 获取所有菜单 不需权限验证
    function allmenus_get()
    {
        $MenuTreeArr = $this->Role_model->getAllMenus();
        if (empty($MenuTreeArr)) {
            $message = [
                "code" => 20000,
                "data" => $MenuTreeArr,
                "message" => "数据库表中没有菜单"
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $MenuTree = $this->permission->genVueMenuTree($MenuTreeArr, 'id', 'pid', 0);
        $message = [
            "code" => 20000,
            "data" => $MenuTree,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 获取所有角色带perm_id 不需权限验证
    function allroles_get()
    {
        $AllRolesArr = $this->Role_model->getAllRoles();
        if (empty($AllRolesArr)) {
            $message = [
                "code" => 20000,
                "data" => $AllRolesArr,
                "message" => "数据库表中没有角色"
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "data" => $AllRolesArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    //  获取角色拥有的菜单权限 不需权限验证
    function rolemenu_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组
        $RoleId = $parms['roleId'];

        $MenuTreeArr = $this->Role_model->getRoleMenu($RoleId);
        $message = [
            "code" => 20000,
            "data" => $MenuTreeArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 获取角色拥有的角色权限 不需权限验证
    function rolerole_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组
        $RoleId = $parms['roleId'];

        $RoleRoleArr = $this->Role_model->getRoleRole($RoleId);
        $message = [
            "code" => 20000,
            "data" => $RoleRoleArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 保存角色对应权限
    function saveroleperm_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组
        //        var_dump($parms['roleId']);
        //        var_dump($parms['rolePerms']);
        // 参数检验/数据预处理
        // 超级管理员角色不允许删除
        if ($parms['roleId'] == 1) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '超级管理员角色拥有所有权限，不允许修改！'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $RolePermArr = $this->Role_model->getRolePerm($parms['roleId']);

        $AddArr = $this->permission->array_diff_assoc2($parms['rolePerms'], $RolePermArr);
        // var_dump('------------只存在于前台传参 做添加操作-------------');
        // var_dump($AddArr);
        $failed = false;
        $failedArr = [];
        foreach ($AddArr as $k => $v) {
            $ret = $this->Base_model->_insert_key('sys_role_perm', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '授权失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $DelArr = $this->permission->array_diff_assoc2($RolePermArr, $parms['rolePerms']);
        // var_dump('------------只存在于后台数据库 删除操作-------------');
        // var_dump($DelArr);
        $failed = false;
        $failedArr = [];
        foreach ($DelArr as $k => $v) {
            $ret = $this->Base_model->_delete_key('sys_role_perm', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '授权失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "data" => $parms,
            "message" => '授权操作成功',
        ];
        $this->response($message, RestController::HTTP_OK);
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
            $this->response($message, RestController::HTTP_OK);
        } else {
            $message = [
                "code" => 50008,
                "message" => 'Login failed, unable to get user details.'
            ];

            $this->response($message, RestController::HTTP_OK);
        }

    }

}
