/*
MySQL Backup
Source Server Version: 5.5.53
Source Database: vueadmin
Date: 2019/4/22 16:48:50
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
--  Table structure for `keys`
-- ----------------------------
DROP TABLE IF EXISTS `keys`;
CREATE TABLE `keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `key` varchar(40) NOT NULL,
  `level` int(2) NOT NULL,
  `ignore_limits` tinyint(1) NOT NULL DEFAULT '0',
  `is_private_key` tinyint(1) NOT NULL DEFAULT '0',
  `ip_addresses` text,
  `date_created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `logs`
-- ----------------------------
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) NOT NULL,
  `method` varchar(6) NOT NULL,
  `params` text,
  `api_key` varchar(40) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `time` int(11) NOT NULL,
  `rtime` float DEFAULT NULL,
  `authorized` varchar(1) NOT NULL,
  `response_code` smallint(3) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4100 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_dept`
-- ----------------------------
DROP TABLE IF EXISTS `sys_dept`;
CREATE TABLE `sys_dept` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL COMMENT '机构名称',
  `aliasname` varchar(255) DEFAULT NULL,
  `listorder` int(11) DEFAULT '99',
  `status` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_menu`
-- ----------------------------
DROP TABLE IF EXISTS `sys_menu`;
CREATE TABLE `sys_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `component` varchar(255) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL COMMENT '0:目录，1:菜单, 3:功能/按钮/操作',
  `title` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `redirect` varchar(255) DEFAULT '' COMMENT 'redirect: noredirect           if `redirect:noredirect` will no redirect in the breadcrumb',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `condition` varchar(255) DEFAULT '' COMMENT '规则表达式，为空表示存在就验证，不为空表示按照条件验证',
  `listorder` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_perm`
-- ----------------------------
DROP TABLE IF EXISTS `sys_perm`;
CREATE TABLE `sys_perm` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `perm_type` varchar(255) NOT NULL COMMENT '权限类型：menu:菜单路由类,role:角色类,file:文件类',
  `r_id` int(11) NOT NULL COMMENT '实际基础表的关联id，如菜单表ID，角色表ID，文件表ID等',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='系统权限表\r\n\r\n基础表（菜单表，角色表，文件表及其他需要权限控制的表）每新增一个记录，此表同时插入一条对应记录，如\r\nsys_menu表加入一条记录，此处需要对应加入  类型 menu 的 r_id 为menu id的记录';

-- ----------------------------
--  Table structure for `sys_perm_type`
-- ----------------------------
DROP TABLE IF EXISTS `sys_perm_type`;
CREATE TABLE `sys_perm_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL COMMENT '权限类型',
  `r_table` varchar(255) NOT NULL COMMENT '类型对应的基础表，如sys_menu,sys_role,sys_file等',
  `title` varchar(255) NOT NULL COMMENT '类型标题',
  `remark` varchar(255) DEFAULT NULL COMMENT '类型注释说明',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='权限类型对照表';

-- ----------------------------
--  Table structure for `sys_role`
-- ----------------------------
DROP TABLE IF EXISTS `sys_role`;
CREATE TABLE `sys_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `pid` int(11) DEFAULT '0',
  `status` tinyint(4) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `listorder` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_role_perm`
-- ----------------------------
DROP TABLE IF EXISTS `sys_role_perm`;
CREATE TABLE `sys_role_perm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `perm_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  KEY `perm_id` (`perm_id`),
  CONSTRAINT `sys_role_perm_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `sys_role` (`id`),
  CONSTRAINT `sys_role_perm_ibfk_2` FOREIGN KEY (`perm_id`) REFERENCES `sys_perm` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_user`
-- ----------------------------
DROP TABLE IF EXISTS `sys_user`;
CREATE TABLE `sys_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `sex` smallint(1) DEFAULT NULL,
  `last_login_ip` varchar(16) DEFAULT NULL,
  `last_login_time` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `listorder` int(11) DEFAULT '1000',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_user_role`
-- ----------------------------
DROP TABLE IF EXISTS `sys_user_role`;
CREATE TABLE `sys_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `dept_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `role_id` (`role_id`),
  KEY `dept_id` (`dept_id`),
  CONSTRAINT `sys_user_role_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`id`),
  CONSTRAINT `sys_user_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `sys_role` (`id`),
  CONSTRAINT `sys_user_role_ibfk_3` FOREIGN KEY (`dept_id`) REFERENCES `sys_dept` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_user_token`
-- ----------------------------
DROP TABLE IF EXISTS `sys_user_token`;
CREATE TABLE `sys_user_token` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `user_id` bigint(20) NOT NULL,
  `role_id` int(11) NOT NULL COMMENT '用户当前选择角色',
  `token` varchar(100) NOT NULL COMMENT 'token',
  `expire_time` int(11) DEFAULT NULL COMMENT '过期时间',
  `create_by` varchar(50) DEFAULT NULL COMMENT '创建人',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `last_update_by` varchar(50) DEFAULT NULL COMMENT '更新人',
  `last_update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COMMENT='用户Token';

-- ----------------------------
--  Table structure for `upload_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `upload_tbl`;
CREATE TABLE `upload_tbl` (
  `identify` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `idinfo` varchar(255) DEFAULT NULL,
  `bankinfo` varchar(255) DEFAULT NULL,
  `check` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
--  Procedure definition for `getChildLst`
-- ----------------------------
DROP FUNCTION IF EXISTS `getChildLst`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `getChildLst`(`rootId` int) RETURNS varchar(1000) CHARSET utf8
BEGIN 
DECLARE sTemp VARCHAR(1000); 

DECLARE sTempChd VARCHAR(1000); 

 SET sTemp = '$'; 
 SET sTempChd =cast(rootId as CHAR); 
 
 WHILE sTempChd is not null DO 
   SET sTemp = concat(sTemp,',',sTempChd); 
    SELECT group_concat(Id) INTO sTempChd FROM sys_menu where FIND_IN_SET(pid,sTempChd)>0; 
 END WHILE; 
  RETURN sTemp; 
END
;;
DELIMITER ;

-- ----------------------------
--  Records 
-- ----------------------------
INSERT INTO `keys` VALUES ('1','0','oocwo8cs88g4c8w8c08ow00ss844cc4osko0s0ks','10','1','0',NULL,'1551173554'), ('2','0','00kgsog84kooc44kgwkwccow48kggc48s4gcwwcg','0','1','0',NULL,'1551173554');
INSERT INTO `sys_dept` VALUES ('1','0','英雄的黎明',NULL,'99','1'), ('2','1','黄河','黄河','99','1'), ('3','1','长江',NULL,'99','1'), ('4','0','长江的燃烧',NULL,'99','1'), ('5','0','风姿花传',NULL,'99','1');
INSERT INTO `sys_menu` VALUES ('1','0','SysDhdv','/sys','Layout','0','系统管理','sysset2','/sys/menu','0','1','','99',NULL,NULL), ('2','1','SysMenuSnIc','/sys/menu','sys/menu/index','1','菜单管理','menu1','','0','1','','80',NULL,NULL), ('3','1','SysRoleCkoF','/sys/role','sys/role/index','1','角色管理','role','','0','1','','99',NULL,NULL), ('4','1','SysUserPhjc','/sys/user','sys/user/index','1','用户管理','user','','0','1','','99',NULL,NULL), ('5','0','Sysxfgoy','/sysx','Layout','0','测试菜单','plane','/sysx/xiangjun','0','1','','100',NULL,NULL), ('6','2','SysMenuAddTbtv','/sys/menu/add','','2','添加','','','0','1','','90',NULL,NULL), ('7','2','SysMenuEditSJvm','/sys/menu/edit','','2','编辑','','','0','1','','95',NULL,NULL), ('8','2','SysMenuDelTtub','/sys/menu/del','','2','删除','','','0','1','','99',NULL,NULL), ('9','2','SysMenuViewJbtm','/sys/menu/view','','2','查看','','','0','1','','80',NULL,NULL), ('10','5','SysxXiangjunAles','/sysx/xiangjun','xiangjun/index','1','vue课堂测试','form','','0','1','','95',NULL,NULL), ('11','5','SysxUploadimgRafb','/sysx/uploadimg','uploadimg/index','1','上传证件照','form','','0','1','','99',NULL,NULL), ('12','1','SysIconQjyw','/sys/icon','svg-icons/index','1','图标管理','icon','','0','1','','100',NULL,NULL), ('13','3','SysRoleViewTyli','/sys/role/view','','2','查看','','','0','1','','90',NULL,NULL), ('14','3','SysRoleAddAtyq','/sys/role/add','','2','添加','','','0','1','','91',NULL,NULL), ('15','3','SysRoleEditRezj','/sys/role/edit','','2','编辑','','','0','1','','92',NULL,NULL), ('16','3','SysRoleDelEvyv','/sys/role/del','','2','删除','','','0','1','','101',NULL,NULL), ('17','4','SysUserViewAzlk','/sys/user/view','','2','查看','','','0','1','','96',NULL,NULL), ('18','4','SysUserAddIhml','/sys/user/add','','2','添加','','','0','1','','97',NULL,NULL), ('19','4','SysUserEditKzij','/sys/user/edit','','2','编辑','','','0','1','','99',NULL,NULL), ('20','4','SysUserDelZurb','/sys/user/del','','2','删除','','','0','1','','100',NULL,NULL), ('21','1','SysDeptUhff','/sys/dept','sys/dept/index','1','机构管理','dept3','','0','1','','98',NULL,NULL), ('22','21','SysDeptViewPmgf','/sys/dept/view','','2','查看','','','0','1','','98',NULL,NULL), ('23','21','SysDeptAddZznr','/sys/dept/add','','2','添加','','','0','1','','99',NULL,NULL), ('24','21','SysDeptEditIbbe','/sys/dept/edit','','2','编辑','','','0','1','','100',NULL,NULL), ('25','21','SysDeptDelIlgd','/sys/dept/del','','2','删除','','','0','1','','101',NULL,NULL);
INSERT INTO `sys_perm` VALUES ('1','role','1'), ('2','menu','1'), ('3','menu','2'), ('4','menu','3'), ('5','menu','4'), ('6','menu','5'), ('7','menu','6'), ('8','menu','7'), ('9','menu','8'), ('10','menu','9'), ('11','menu','10'), ('12','menu','11'), ('13','menu','12'), ('14','menu','13'), ('15','menu','14'), ('16','menu','15'), ('17','menu','16'), ('18','menu','17'), ('19','menu','18'), ('20','menu','19'), ('21','menu','20'), ('22','menu','21'), ('23','menu','22'), ('24','menu','23'), ('25','menu','24'), ('26','menu','25');
INSERT INTO `sys_perm_type` VALUES ('1','role','sys_role','角色类',NULL), ('2','menu','sys_menu','菜单类',NULL), ('3','file','sys_file','文件类',NULL);
INSERT INTO `sys_role` VALUES ('1','超级管理员','0','1','拥有网站最高管理员权限！','1329633709','1329633709','1');
INSERT INTO `sys_role_perm` VALUES ('1','1','1'), ('2','1','2'), ('3','1','3'), ('4','1','4'), ('5','1','5'), ('6','1','6'), ('7','1','7'), ('8','1','8'), ('9','1','9'), ('10','1','10'), ('11','1','11'), ('12','1','12'), ('13','1','13'), ('14','1','14'), ('15','1','15'), ('16','1','16'), ('17','1','17'), ('18','1','18'), ('19','1','19'), ('20','1','20'), ('21','1','21'), ('22','1','22'), ('23','1','23'), ('24','1','24'), ('25','1','25'), ('26','1','26');
INSERT INTO `sys_user` VALUES ('1','admin','21232f297a57a5a743894a0e4a801fc3','admin','lmxdawn@gmail.com','','0','127.0.0.1','1493103488','1487868050','1','1'), ('2','okay','026a4f42edc4e5016daa1f0a263242ee',NULL,'okay@163.com',NULL,NULL,NULL,NULL,'1555660931','1','1000');
INSERT INTO `sys_user_role` VALUES ('1','1','1','1'), ('2','1','1','2'), ('3','1','1','3'), ('4','1','1','4'), ('5','1','1','5'), ('6','2','1','2');
INSERT INTO `sys_user_token` VALUES ('1','1','1','cogow00kc40gswwswkok0k0swsocgsggwowo080s','1553661738',NULL,'1553654538',NULL,'1553654538'), ('2','1','1','ws04o40wo8kg48gwwc08ssso88skcsocwgc0cgg4','1553661848',NULL,'1553654616',NULL,'1553654648'), ('3','1','1','8gko4g88wocwosscococ4okkso8ggs4og0008s4g','1553661956',NULL,'1553654756',NULL,NULL), ('4','1','1','gk8cocggogggwoc4ws04cg8kw8kwgc8gk088w8go','1553663120',NULL,'1553654834',NULL,'1553655920'), ('5','1','1','kw0scsgw8skook0ww0888os4wgo04ggw0g408ss4','1553683522',NULL,'1553676002',NULL,'1553676322'), ('6','1','1','o4k0ckk00sk8s08k4csgk8oog8go8kws0oc044ow','1553685023',NULL,'1553676330',NULL,'1553677823'), ('7','1','1','44csc4w0cw80skkggo4sso4osc4k88ks0kgswwwc','1553686286',NULL,'1553678557',NULL,'1553679086'), ('8','1','1','044s8ok8ogwk04og8s8gcs08w0gww8k48ckgwsg8','1553687051',NULL,'1553679851',NULL,NULL), ('9','1','1','coswgoc8kgosk8c80s4w4k4ckoo4os44ko40k0g8','1553688454',NULL,'1553681248',NULL,'1553681254'), ('10','1','1','sgwo0sc4ko0kk8wgsgc0ows48s4scww4wkg0g8s0','1553688894',NULL,'1553681686',NULL,'1553681694'), ('11','1','1','044sssosowgc8o44sgo8gg0w0w0ks0kcc0wsg4wg','1553750702',NULL,'1553734652',NULL,'1553743502'), ('12','1','1','0kc88g8gg8g40skgwscocw8k0w80socssgk88gcw','1553765872',NULL,'1553755030',NULL,'1553758672'), ('13','1','1','4c4gww0c88sg0084k8w04ko444wk40w8gwwkwg48','1554181085',NULL,'1554173826',NULL,'1554173885'), ('14','1','1','8w0kcos4ks0okc8gs0o4kc04w0cksgc0wkkkc0k0','1554284518',NULL,'1554277281',NULL,'1554277318'), ('15','1','1','w4sc84c0000woksos8swwcksoocwwc0s0o88ck40','1554284686',NULL,'1554277329',NULL,'1554277486'), ('16','1','1','ccw4cw0g88gss4wo4k8k088wc8oskk4g48488ws4','1554289046',NULL,'1554281701',NULL,'1554281846'), ('17','1','1','40w04wgcc8g8g4skgkocog4w8w8cwks88kg48ws8','1554723221',NULL,'1554716020',NULL,'1554716021'), ('18','1','1','cos8kkk4wowsc04s0go84s4og4okskwo0gc8wwsc','1554807145',NULL,'1554799939',NULL,'1554799945'), ('19','1','1','o0ogw4o4g8sgg8kkww80cwcc4wk8k0s8skcwg44g','1555668131',NULL,'1555660670',NULL,'1555660931'), ('20','1','1','o0kgwgoc88gg0owkco00csww0wc0ws0sogkk48wk','1555908164',NULL,'1555900959',NULL,'1555900964'), ('21','1','1','808os8gggokk0040wo44w8swsw0kw8gwkw4kk40c','1555929617',NULL,'1555922120',NULL,'1555922417');
INSERT INTO `upload_tbl` VALUES ('410000000000000000','136000000','/uploads/image/T410000000000000000/201903/20190327101840_75319.png','/uploads/image/T410000000000000000/201903/20190327101843_79603.jpg','待审核');
