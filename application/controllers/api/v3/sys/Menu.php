<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Menu extends RestController
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Base_model');
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

        // $id = $this->post('id'); // POST param
        $parms = $this->post();  // 获取表单参数，类型为数组
        //         var_dump($parms);
        //         var_dump($parms['path']);

        // 参数检验/数据预处理
        // 菜单类型为目录
        if (!$parms['type']) {
            $parms['component'] = 'Layout';
        }

        $menu_id = $this->Base_model->_insert_key('sys_menu', $parms);
        if (!$menu_id) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['title'] . ' - 菜单添加失败'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 生成该菜单对应的权限: sys_perm, 权限类型为: menu, 生成唯一的 perm_id
        $perm_id = $this->Base_model->_insert_key('sys_perm', ['perm_type' => 'menu', "r_id" => $menu_id]);
        if (!$perm_id) {
            var_dump($this->uri->uri_string . ' 生成该菜单对应的权限: sys_perm, 失败...');
            var_dump(['perm_type' => 'menu', "r_id" => $menu_id]);
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
            "message" => $parms['title'] . ' - 菜单添加成功'
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
        if ($parms['id'] == $parms['pid']) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '父节点不能是自己'
            ];
            $this->response($message, RestController::HTTP_OK);
        }
        // 菜单类型为目录
        if ($parms['type'] == 0) {
            $parms['component'] = 'Layout';
        }
        // 菜单类型为功能按钮时
        if ($parms['type'] == 2) {
            $parms['component'] = '';
            $parms['icon'] = '';
        }

        $id = $parms['id'];
        unset($parms['id']); // 择出索引id
        $where = ["id" => $id];

        if (!$this->Base_model->_update_key('sys_menu', $parms, $where)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['title'] . ' - 菜单更新错误'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['title'] . ' - 菜单更新成功'
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
        // 存在子节点 不能删除返回
        $hasChild = $this->Base_model->hasChildMenu($parms['id']);
        if ($hasChild) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['title'] . ' - 存在子节点不能删除'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 删除外键关联表 sys_role_perm , sys_perm, sys_menu
        // 1. 根据sys_menu id及'menu' 查找 perm_id
        // 2. 删除sys_role_perm 中perm_id记录
        // 3. 删除sys_perm中 perm_type='menu' and r_id = menu_id 记录,即第1步中获取的 perm_id， 一一对应
        // 4. 删除sys_menu 中 id = menu_id 的记录
        $where = 'perm_type="menu" and r_id=' . $parms['id'];
        $arr = $this->Base_model->_get_key('sys_perm', '*', $where);
        if (empty($arr)) {
            var_dump($this->uri->uri_string . ' 未查找到 sys_perm 表中记录');
            var_dump($where);
            return;
        }

        $perm_id = $arr[0]['id']; // 正常只有一条记录
        $this->Base_model->_delete_key('sys_role_perm', ['perm_id' => $perm_id]);
        $this->Base_model->_delete_key('sys_perm', ['id' => $perm_id]);

        // 删除基础表 sys_menu
        if (!$this->Base_model->_delete_key('sys_menu', $parms)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['title'] . ' - 菜单删除错误'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['title'] . ' - 菜单删除成功'
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

        $MenuTreeArr = $this->permission->getPermission($Token, 'menu', true);
        $MenuTree = $this->permission->genVueMenuTree($MenuTreeArr, 'id', 'pid', 0);
        $message = [
            "code" => 20000,
            "data" => $MenuTree,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 根据token拉取 treeselect 下拉选项菜单
    function treeoptions_get()
    {
        // 此 uri 可不做权限/token过期验证，则在菜单里，可以不加入此项路由path /sys/menu/treeoptions。
        //
        //        $uri = $this->uri->uri_string;
        //        $Token = $this->input->get_request_header('X-Token', TRUE);
        //        $retPerm = $this->permission->HasPermit($Token, $uri);
        //        if ($retPerm['code'] != 50000) {
        //            $this->response($retPerm, RestController::HTTP_OK);
        //            return;
        //        }

        $Token = $this->input->get_request_header('X-Token', TRUE);

        $MenuTreeArr = $this->permission->getPermission($Token, 'menu', false);
        array_unshift($MenuTreeArr, ['id' => 0, 'pid' => -1, 'title' => '顶级菜单']);
        $MenuTree = $this->permission->genVueMenuTree($MenuTreeArr, 'id', 'pid', -1);

        $message = [
            "code" => 20000,
            "data" => $MenuTree,
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
