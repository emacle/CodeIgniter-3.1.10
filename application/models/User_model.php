<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model
{
    /**
     * 用户模型部分
     *
     */
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 登陆校验用户名密码
     * @param $username
     * @param $passwd
     */
    function validate($username, $passwd)
    {
        $sql = "SELECT
                    *
                FROM
                    sys_user
                WHERE
                    username = '" . $username . "'
                AND password = '" . $passwd . "'";

        $query = $this->db->query($sql);
        if (($query->row_array()) == null) {
            $result = array(
                'success' => FALSE,
                'userinfo' => null
            );
        } else {
            $result = array(
                'success' => TRUE,
                'userinfo' => $query->row_array()
            );
        }
        return $result;
    }

    /**
     * 根据 $user_id 获取上次登录角色id
     * @param $Id
     */
    function getLastLoginRole($Id)
    {
        $sql = "SELECT
                    role_id
                FROM
                    sys_user_token
                WHERE
                    user_id =" . $Id . "
                ORDER BY
                    create_time DESC
                limit 1";

        $query = $this->db->query($sql);
        $RolesArr = $query->result_array();

        if (empty($RolesArr)) {
            return [
                "code" => "50020",
                "message" => "用户首次登录，没有最后一次登录信息"
            ];
        } else {
            foreach ($RolesArr as $k => $v) {
                $sqlx = "SELECT
                            DISTINCT dept_id
                        FROM
                            `sys_user_role`
                        WHERE
                            user_id =" . $Id . " and role_id=" . $v['role_id'];
                $queryx = $this->db->query($sqlx);
                if (empty($queryx->result_array())) {
                    return [
                        "code" => "50018",
                        "message" => "用户机构信息不完整, 无法登录, 请联系管理员"
                    ];
                }
            }
            return [
                "code" => "20000",
                "role_id" => $RolesArr[0]['role_id']
            ];
        }
    }

    /**
     * 根据 $user_id 获取一个角色作为当前选择默认角色
     * 只在用户初次登录时使用
     * @param $Id
     */
    function getCurrentRole($Id)
    {
        $sql = "SELECT
                   DISTINCT role_id
                FROM
                    sys_user_role
                WHERE
                    user_id =" . $Id;

        $query = $this->db->query($sql);
        if (empty($query->result_array())) {
            return [
                "code" => "50018",
                "message" => "用户未分配角色, 无法登录, 请联系管理员"
            ];
        }
        $RolesArr = $query->result_array();

        foreach ($RolesArr as $k => $v) {
            $sqlx = "SELECT
                            DISTINCT dept_id
                        FROM
                            `sys_user_role`
                        WHERE
                            user_id =" . $Id . " and role_id=" . $v['role_id'];
            $queryx = $this->db->query($sqlx);
            if (empty($queryx->result_array())) {
                return [
                    "code" => "50018",
                    "message" => "用户机构信息不完整, 无法登录, 请联系管理员"
                ];
            }
        }

        return [
            "code" => "20000",
            "role_id" => $RolesArr[0]['role_id'],
            "message" => "成功获取第一个角色"
        ];
    }

    /**
     * @param $RoleId
     * @param $UserId
     * @return bool
     * 判断该用户角色是否已分配机构部门
     */
    function roleHasDept($RoleId, $UserId)
    {
        $sql = "SELECT
                 DISTINCT dept_id
                 FROM
                     `sys_user_role`
                 WHERE
                     user_id =" . $UserId . " and role_id=" . $RoleId;
        $query = $this->db->query($sql);
        if (empty($query->result_array())) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * 根据$token拉取用户信息
     * @param $Token
     */
    function getUserInfo($Token)
    {
        $sql = "SELECT
                    u.id,
                    u.username,
                    u.tel,
                    u.email,
                    u.avatar,
                    u.sex,
                    u.last_login_ip,
                    u.last_login_time,
                    u.status
                FROM
                    sys_user_token ut,
                    sys_user u
                WHERE
                    ut.token = '" . $Token . "'
                    AND u.id = ut.user_id";

        $query = $this->db->query($sql);
        if (($query->row_array()) == null) {
            $result = array(
                'success' => FALSE,
                'userinfo' => null
            );
        } else {
            $result = array(
                'success' => TRUE,
                'userinfo' => $query->row_array()
            );
        }
        return $result;
    }

    /**
     * 根据$token拉取用户角色信息
     * @param $Token
     */
    function getUserRolesByToken($Token)
    {
        $sql = "SELECT
                    DISTINCT r.id,r.name
                FROM
                    sys_user_token ut,
                    sys_user_role ur,
                    sys_role r
                WHERE
                    ut.token = '" . $Token . "'
                    AND ur.user_id = ut.user_id
                    AND r.id = ur.role_id
                    and r.status=1";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 根据$token拉取用户当前选择角色id
     * @param $Token
     */
    function getCurrentRoleByToken($Token)
    {
        $sql = "SELECT
                    ut.role_id
                FROM
                    sys_user_token ut
                WHERE
                    ut.token = '" . $Token . "'";
        $query = $this->db->query($sql);
        return $query->row_array()['role_id'];
    }

    /**
     * 根据$token拉取用户当前选择部门
     * @param $Token
     */
    function getCurrentDeptByToken($Token)
    {
        $sql = "SELECT
                    ut.user_id,
                    ut.role_id
                FROM
                    sys_user_token ut
                WHERE
                    ut.token = '" . $Token . "'";
        $query = $this->db->query($sql);
        $arr = $query->result_array();

        $sqlx = "SELECT DISTINCT
                    d.id,
                    d.pid,
                    d.name label
                FROM
                    sys_user_role ur,
                    sys_dept d
                WHERE
                    ur.user_id =" . $arr[0]['user_id'] . "
                AND ur.role_id =" . $arr[0]['role_id'] . "
                AND ur.dept_id = d.id";

        $queryx = $this->db->query($sqlx);
        return $queryx->result_array();
    }

    /**
     * 获取所有用户列表
     */
    function getUserList($deptIdStr, $filters, $sort, $page, $pageSize)
    {

        // 默认排序
        $orderStr = '';
        if ($sort['prop'] && $sort['order']) {
            $orderStr = " ORDER BY " . $sort['prop'] . " " . ($sort['order'] === 'ascending' ? 'asc' : 'desc');
        }

        $filterStr = '';
        $j = 0;
        foreach ($filters as $k => $v) {
            if ($v['value'] !== '' && !is_null($v['value'])) {
                if ($j) {
                    $filterStr = $filterStr . " and ";
                }

                if ($v['prop'] === 'username') {
                    $filterStr .= $v['prop'] . " like '%" . $v['value'] . "%' ";
                }
                if ($v['prop'] === 'status') {
                    $filterStr .= $v['prop'] . "=" . $v['value'];
                }

                $j++;
            }
        }

        if ($filterStr) {
            $filterStr = " and " . $filterStr;
        }

        //TODO: 将超级管理员隐藏  sys_user u where  id != 1 and id IN (
        $sql = "SELECT
                     *
                FROM
                    sys_user where id IN (
                        SELECT DISTINCT
                            user_id
                        FROM
                            sys_user_role ur
                        WHERE
                            dept_id IN (" . $deptIdStr . ")
                    ) "
            . $filterStr
            . $orderStr . " limit " . ($page - 1) * $pageSize . "," . $pageSize;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    function getUserListCnt($deptIdStr, $filters)
    {
        $filterStr = '';
        $j = 0;
        foreach ($filters as $k => $v) {
            if ($v['value'] !== '' && !is_null($v['value'])) {
                if ($j) {
                    $filterStr = $filterStr . " and ";
                }

                if ($v['prop'] === 'username') {
                    $filterStr .= $v['prop'] . " like '%" . $v['value'] . "%' ";
                }
                if ($v['prop'] === 'status') {
                    $filterStr .= $v['prop'] . "=" . $v['value'];
                }

                $j++;
            }
        }

        if ($filterStr) {
            $filterStr = " and " . $filterStr;
        }
        // TODO: 将超级管理员隐藏  sys_user u where  id != 1 and id IN (
        $sql = "SELECT
                    count(u.id) cnt
                FROM
                   sys_user u where id IN (
                        SELECT DISTINCT
                            user_id
                        FROM
                            sys_user_role ur
                        WHERE
                            dept_id IN (" . $deptIdStr . ")
                    ) "
            . $filterStr;

        $query = $this->db->query($sql);
        if (empty($query->result_array())) {
            return 0;
        } else {
            $result = $query->result_array();
            return $result[0]['cnt'];
        }
    }

    /**
     * 获取所有角色列表 参考 permission.php 权限库，因为需要左关联组合，所以只能重新写模型
     * 并且根据$token 获取对应Token用户角色所拥有的角色类权限选项
     * 当用户含有未拥有的角色类权限时 设置 isDisabled 禁用选择
     * 新增编辑时使用
     *
     */
    function getRoleOptions($Token)
    {
        $sql = "SELECT
                    r.id,
                    r.name,
                    r.remark,
                    r.status,
                    IF (
                        t.name IS NULL,
                        'true',
                        'false'
                    ) isDisabled, 
                    r.listorder
                FROM
                    sys_role r
                LEFT JOIN (
                    SELECT
                        r.id,
                        r.name,
                        r.remark,
                        r.status
                    FROM
                        sys_user_token ut,
                        sys_role_perm rp,
                        sys_perm p,
                        sys_role r
                    WHERE
                        ut.token = '" . $Token . "'
                    AND ut.role_id = rp.role_id
                    AND rp.perm_id = p.id
                    AND p.perm_type = 'role'
                    AND p.r_id = r.id
                    AND r.status = 1
                ) t ON r.id = t.id
                ORDER BY r.listorder";
        $query = $this->db->query($sql);
        $RoleArr = $query->result_array();

        // string to boolean
        foreach ($RoleArr as $k => $v) {
            $v['isDisabled'] === 'true' ? ($RoleArr[$k]['isDisabled'] = true) : ($RoleArr[$k]['isDisabled'] = false);
        }
        return $RoleArr;
    }

    /**
     * 根据 用户ID 获取该用户被分配的角色
     *
     */
    function getUserRoles($Id)
    {
        $sql = "SELECT
                    DISTINCT r.id,
                    r.name,
                    r.remark,
                    r.status
                FROM
                    sys_user_role ur,
                    sys_role r
                WHERE
                    ur.role_id = r.id
                AND ur.user_id =" . $Id;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    function getUserRolesDept($user_id, $role_id)
    {
        $sql = "SELECT
                    *
                FROM
                    sys_user_role ur
                WHERE
                    ur.role_id =" . $role_id . "
                AND ur.user_id =" . $user_id;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 根据 用户ID 获取该用户所拥有的角色
     * [
     * ['user_id'=> 1, 'role_id'=>1]
     * ...
     * ]
     */
    function getRolesByUserId($Id)
    {
        $sql = "SELECT
                    ur.user_id,ur.role_id,ur.dept_id
                FROM
                    sys_user_role ur
                 WHERE ur.user_id=" . $Id;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /*
     * 微信登录认证
     */
    function getUserInfoByTel($Tel)
    {
        $sql = "SELECT
                   u.*
                FROM
                    sys_user u
                 WHERE u.tel='" . $Tel . "'";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /***********************
     * 用户模型部分结束
     ***********************/
}