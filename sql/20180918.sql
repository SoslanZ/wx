use ssp;

CREATE TABLE `ssp_wxmedia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wxid` varchar(50) NOT NULL COMMENT '小程序ID',
  `type_id` varchar(50) NOT NULL COMMENT '小程序分类',
  `marks` varchar(255) DEFAULT NULL COMMENT '小程序说明',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '小程序状态1开启0关闭',
  `media_id` int(11) NOT NULL COMMENT '媒体ID',
  `name` varchar(255) DEFAULT NULL COMMENT '小程序名称',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `wxid` (`wxid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='小程序表';

delete from ssp_media where id=3;

INSERT INTO ssp_wxmedia ( wxid, `name`, type_id, marks, `status`, media_id, create_time ) SELECT wxid,`name`,type_id,marks,`status`,id AS media_id,create_time FROM ssp_media;


alter table ssp_creative add type int(11) NOT NULL COMMENT '1 指定小程序 2 指定分类';
alter table ssp_creative add type_val text NOT NULL COMMENT '指定类型值';

alter table ssp_media change name mname varchar(50) NOT NULL DEFAULT '' COMMENT '媒体名称';



drop INDEX `mskey` ON ssp_media;

ALTER TABLE ssp_media DROP `wxid`;

ALTER TABLE ssp_media DROP `type_id`;

ALTER TABLE ssp_media DROP `marks`;

ALTER TABLE ssp_media DROP `mskey`;

ALTER TABLE ssp_media DROP `status`;


