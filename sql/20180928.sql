alter table ssp_sspuser add column `phone` varchar(20) DEFAULT NULL COMMENT '手机号';
alter table ssp_sspuser add column `remarks` varchar(255) DEFAULT NULL;
alter table ssp_creative modify `media_id` int(11) DEFAULT NULL COMMENT '小程序ID(舍弃)';