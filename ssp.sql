/*
 Navicat Premium Data Transfer

 Source Server         : bendi
 Source Server Type    : MySQL
 Source Server Version : 50553
 Source Host           : localhost:3306
 Source Schema         : ssp2

 Target Server Type    : MySQL
 Target Server Version : 50553
 File Encoding         : 65001

 Date: 10/10/2018 12:00:39
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ssp_adslot
-- ----------------------------
DROP TABLE IF EXISTS `ssp_adslot`;
CREATE TABLE `ssp_adslot`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '广告位ID',
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '广告位名称',
  `media_id` int(11) NOT NULL COMMENT '所属媒体ID',
  `template_id` int(11) NOT NULL COMMENT '规格ID',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `skey` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '唯一标识',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `skey`(`skey`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 44 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_adslot_template
-- ----------------------------
DROP TABLE IF EXISTS `ssp_adslot_template`;
CREATE TABLE `ssp_adslot_template`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '规格名称',
  `width` int(11) NOT NULL COMMENT '宽度',
  `height` int(11) NOT NULL COMMENT '高度',
  `ad_type` int(11) NOT NULL COMMENT '广告类型',
  `material` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '素材定义',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_advertiser_log
-- ----------------------------
DROP TABLE IF EXISTS `ssp_advertiser_log`;
CREATE TABLE `ssp_advertiser_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opera_id` int(11) NOT NULL COMMENT '操作人ID',
  `uid` int(11) NOT NULL COMMENT '被更改人',
  `uptime` int(11) NOT NULL COMMENT '修改时间',
  `type` tinyint(1) NOT NULL COMMENT '修改类型',
  `val` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '值',
  `old_val` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '原值',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_advertiser_report
-- ----------------------------
DROP TABLE IF EXISTS `ssp_advertiser_report`;
CREATE TABLE `ssp_advertiser_report`  (
  `day` int(11) NOT NULL DEFAULT 0 COMMENT '小时',
  `creative_id` int(11) NOT NULL COMMENT '广告ID',
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '竞价数',
  `im` int(11) NOT NULL COMMENT '展示数',
  `ck` int(11) NOT NULL COMMENT '点击数',
  `cost` decimal(10, 2) NOT NULL COMMENT '消耗',
  PRIMARY KEY (`day`, `creative_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_auditlog
-- ----------------------------
DROP TABLE IF EXISTS `ssp_auditlog`;
CREATE TABLE `ssp_auditlog`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `upstatus` int(11) NOT NULL COMMENT '操作状态',
  `creative_id` int(11) NOT NULL COMMENT '广告ID',
  `opera_id` int(11) NOT NULL COMMENT '操作人ID',
  `create_time` int(11) NOT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 105 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_balance
-- ----------------------------
DROP TABLE IF EXISTS `ssp_balance`;
CREATE TABLE `ssp_balance`  (
  `uid` int(11) NOT NULL COMMENT '用户ID',
  `money` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '账户金额',
  `vir_money` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '虚拟金额',
  `dslimit` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '日限额',
  `update_time` int(11) NOT NULL COMMENT '修改时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `cost_day` decimal(10, 2) NOT NULL DEFAULT 0.00,
  `cost_ts` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_campaign
-- ----------------------------
DROP TABLE IF EXISTS `ssp_campaign`;
CREATE TABLE `ssp_campaign`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '推广计划ID',
  `uid` int(11) NOT NULL COMMENT '归属账号',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '名称',
  `budget` int(11) NOT NULL COMMENT '预算',
  `status` int(255) NOT NULL DEFAULT 1 COMMENT '状态 1开启 0 关闭 2删除',
  `strategy` int(255) NOT NULL COMMENT '投放方式：0标准 1加速',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 45 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_creative
-- ----------------------------
DROP TABLE IF EXISTS `ssp_creative`;
CREATE TABLE `ssp_creative`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '广告ID',
  `campaign_id` int(11) NOT NULL COMMENT '推广计划ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '广告名称',
  `template_id` int(11) NOT NULL COMMENT '规格ID',
  `media_id` int(11) NULL DEFAULT NULL COMMENT '小程序ID(舍弃)',
  `filter_slot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '广告位ID',
  `filter_os` smallint(8) NOT NULL COMMENT '操作系统定向( 0-未知 1-iOS 2-Android)',
  `filter_net` int(11) NOT NULL COMMENT '网络类型定向(位标识 0-未知 1-Wifi 2-2G 3-3G 4-4G)',
  `filter_sex` tinyint(4) NOT NULL COMMENT '性别定向( 0-未知 1-男 2-女)',
  `start_ts` int(11) NOT NULL COMMENT '开始投放时间',
  `end_ts` int(11) NULL DEFAULT NULL COMMENT '结束投放时间',
  `filter_hour` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '时间定向: 星期[日,一,二,三,四,五,六]',
  `bid_type` tinyint(4) NOT NULL COMMENT '出价方式(0-CPC 1-CPM)',
  `bid_price` decimal(10, 2) NOT NULL COMMENT '出价价格',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '状态 0-审核 1审核通过 2审核未通过 3 删除',
  `material` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '素材定义: json',
  `reson` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '原因',
  `material_status` tinyint(1) NULL DEFAULT 0 COMMENT '是否同步CDN',
  `handle_status` tinyint(1) NULL DEFAULT 1 COMMENT '0用户关闭 1 用户开启 2用户删除',
  `type` int(11) NOT NULL COMMENT '1 指定小程序 2 指定分类',
  `type_val` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '指定类型值',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 112 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_day_cost
-- ----------------------------
DROP TABLE IF EXISTS `ssp_day_cost`;
CREATE TABLE `ssp_day_cost`  (
  `uid` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `money` decimal(10, 2) NOT NULL,
  `vmoney` decimal(10, 2) NOT NULL,
  PRIMARY KEY (`uid`, `day`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_dsp_cate
-- ----------------------------
DROP TABLE IF EXISTS `ssp_dsp_cate`;
CREATE TABLE `ssp_dsp_cate`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `permissions` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '权限菜单',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `desc` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id`(`id`) USING BTREE,
  INDEX `name`(`name`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_dsp_menu
-- ----------------------------
DROP TABLE IF EXISTS `ssp_dsp_menu`;
CREATE TABLE `ssp_dsp_menu`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `module` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块',
  `controller` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '控制器',
  `function` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '方法',
  `parameter` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '参数',
  `description` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `is_display` int(1) NOT NULL DEFAULT 1 COMMENT '1显示在左侧菜单2只作为节点',
  `type` int(1) NOT NULL DEFAULT 1 COMMENT '1权限节点2普通节点',
  `pid` int(11) NOT NULL DEFAULT 0 COMMENT '上级菜单0为顶级菜单',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `icon` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '图标',
  `is_open` int(1) NOT NULL DEFAULT 0 COMMENT '0默认闭合1默认展开',
  `orders` int(11) NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id`(`id`) USING BTREE,
  INDEX `module`(`module`) USING BTREE,
  INDEX `controller`(`controller`) USING BTREE,
  INDEX `function`(`function`) USING BTREE,
  INDEX `is_display`(`is_display`) USING BTREE,
  INDEX `type`(`type`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 24 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '系统菜单表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ssp_dspuser
-- ----------------------------
DROP TABLE IF EXISTS `ssp_dspuser`;
CREATE TABLE `ssp_dspuser`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '昵称',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `thumb` int(11) NOT NULL DEFAULT 0 COMMENT '管理员头像',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '修改时间',
  `login_time` int(11) NULL DEFAULT NULL COMMENT '最后登录时间',
  `login_ip` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '最后登录ip',
  `dsp_cate_id` int(2) NOT NULL DEFAULT 1 COMMENT '管理员分组',
  `tf` decimal(4, 2) NOT NULL DEFAULT 0.40 COMMENT '投放类别ID',
  `fc` int(1) NOT NULL DEFAULT 70 COMMENT '分成比例ID',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id`(`id`) USING BTREE,
  INDEX `nickname`(`nickname`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE,
  INDEX `dsp_cate_id`(`dsp_cate_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_fc
-- ----------------------------
DROP TABLE IF EXISTS `ssp_fc`;
CREATE TABLE `ssp_fc`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分成ID',
  `rate` int(11) NOT NULL COMMENT '分成比例',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for ssp_media
-- ----------------------------
DROP TABLE IF EXISTS `ssp_media`;
CREATE TABLE `ssp_media`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '媒体ID',
  `mname` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '媒体名称',
  `uid` int(11) NOT NULL COMMENT '所属账号',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 33 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_media_report
-- ----------------------------
DROP TABLE IF EXISTS `ssp_media_report`;
CREATE TABLE `ssp_media_report`  (
  `day` int(11) NOT NULL COMMENT '日期',
  `slot_id` int(11) NOT NULL COMMENT '广告位ID',
  `wxid` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小程序appid',
  `req` int(11) NOT NULL DEFAULT 0 COMMENT '请求数',
  `bid` int(11) NOT NULL DEFAULT 0,
  `im` int(11) NOT NULL COMMENT '展示数',
  `ck` int(11) NOT NULL COMMENT '点击数',
  `income` decimal(10, 2) NOT NULL COMMENT '预估收入',
  `cost` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '广告主消耗',
  PRIMARY KEY (`day`, `slot_id`, `wxid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_platform
-- ----------------------------
DROP TABLE IF EXISTS `ssp_platform`;
CREATE TABLE `ssp_platform`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '平台ID',
  `skey` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '平台标识',
  `platform_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '平台名称',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `skey`(`skey`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_recharge_log
-- ----------------------------
DROP TABLE IF EXISTS `ssp_recharge_log`;
CREATE TABLE `ssp_recharge_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opera_id` int(11) NOT NULL COMMENT '操作人ID',
  `uid` int(11) NOT NULL COMMENT '充值账户',
  `type` int(11) NULL DEFAULT 0 COMMENT '0现金账户 1虚拟账户',
  `recharge` decimal(10, 2) NOT NULL COMMENT '充值金额',
  `create_time` int(11) NOT NULL COMMENT '充值时间',
  `clent_ip` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_ssp_cate
-- ----------------------------
DROP TABLE IF EXISTS `ssp_ssp_cate`;
CREATE TABLE `ssp_ssp_cate`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `permissions` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '权限菜单',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `desc` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id`(`id`) USING BTREE,
  INDEX `name`(`name`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_ssp_menu
-- ----------------------------
DROP TABLE IF EXISTS `ssp_ssp_menu`;
CREATE TABLE `ssp_ssp_menu`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `module` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '模块',
  `controller` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '控制器',
  `function` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '方法',
  `parameter` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '参数',
  `description` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `is_display` int(1) NOT NULL DEFAULT 1 COMMENT '1显示在左侧菜单2只作为节点',
  `type` int(1) NOT NULL DEFAULT 1 COMMENT '1权限节点2普通节点',
  `pid` int(11) NOT NULL DEFAULT 0 COMMENT '上级菜单0为顶级菜单',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `icon` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '图标',
  `is_open` int(1) NOT NULL DEFAULT 0 COMMENT '0默认闭合1默认展开',
  `orders` int(11) NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id`(`id`) USING BTREE,
  INDEX `module`(`module`) USING BTREE,
  INDEX `controller`(`controller`) USING BTREE,
  INDEX `function`(`function`) USING BTREE,
  INDEX `is_display`(`is_display`) USING BTREE,
  INDEX `type`(`type`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 17 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '系统菜单表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for ssp_sspuser
-- ----------------------------
DROP TABLE IF EXISTS `ssp_sspuser`;
CREATE TABLE `ssp_sspuser`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '昵称',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `thumb` int(11) NOT NULL DEFAULT 0 COMMENT '管理员头像',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '修改时间',
  `login_time` int(11) NULL DEFAULT NULL COMMENT '最后登录时间',
  `login_ip` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '最后登录ip',
  `ssp_cate_id` int(2) NOT NULL DEFAULT 1 COMMENT '管理员分组',
  `tf` decimal(4, 2) NOT NULL DEFAULT 0.00 COMMENT '投放类别ID',
  `fc` int(1) NOT NULL DEFAULT 0 COMMENT '分成比例ID',
  `phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `remarks` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id`(`id`) USING BTREE,
  INDEX `ssp_cate_id`(`ssp_cate_id`) USING BTREE,
  INDEX `nickname`(`nickname`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 29 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_tf
-- ----------------------------
DROP TABLE IF EXISTS `ssp_tf`;
CREATE TABLE `ssp_tf`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `price` decimal(4, 2) NOT NULL DEFAULT 0.00 COMMENT '投放价格',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for ssp_type
-- ----------------------------
DROP TABLE IF EXISTS `ssp_type`;
CREATE TABLE `ssp_type`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `pid` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 268 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for ssp_wxmedia
-- ----------------------------
DROP TABLE IF EXISTS `ssp_wxmedia`;
CREATE TABLE `ssp_wxmedia`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wxid` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '小程序ID',
  `type_id` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '小程序分类',
  `marks` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '小程序说明',
  `status` int(1) NOT NULL DEFAULT 1 COMMENT '小程序状态1开启0关闭',
  `media_id` int(11) NOT NULL COMMENT '媒体ID',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '小程序名称',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `wxid`(`wxid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 34 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '小程序表' ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;
