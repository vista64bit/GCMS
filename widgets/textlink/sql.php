<?php die('File not found !')?>
define("DB_TEXTLINK", PREFIX."_textlink");
DROP TABLE IF EXISTS `{prefix}_textlink`;
CREATE TABLE IF NOT EXISTS `{prefix}_textlink` (`id` int(11) NOT NULL auto_increment,`text` text collate utf8_unicode_ci NOT NULL,`url` text collate utf8_unicode_ci NOT NULL,`publish_start` int(11) NOT NULL,`publish_end` int(11) NOT NULL,`logo` text collate utf8_unicode_ci NOT NULL,`width` int(11) NOT NULL,`height` int(11) NOT NULL,`type` varchar(11) collate utf8_unicode_ci NOT NULL,`name` varchar(11) collate utf8_unicode_ci NOT NULL,`published` smallint(1) NOT NULL DEFAULT '1',`link_order` smallint(2) NOT NULL,`last_preview` int(11) unsigned NOT NULL,`description` varchar(49) collate utf8_unicode_ci NOT NULL,`template` text collate utf8_unicode_ci NOT NULL,`target` varchar(6) collate utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `{prefix}_textlink` (`text`, `url`, `type`, `name`, `published`) VALUES ('Goragod.com','http://www.goragod.com/','list','list','1');
DELETE FROM `{prefix}_language` WHERE `owner`='textlink';
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_DATELESS','text','textlink','0','ไม่มีวันหมดอายุ');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_PUBLISHED_END','text','textlink','0','วันที่สิ้นสุด');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_PUBLISHED_START','text','textlink','0','วันที่เริ่มต้น');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_PUBLISHED_START_END_COMMENT','text','textlink','0','กำหนดวันที่เริ่มต้นและสิ้นสุดการแสดงลิงค์ (ลิงค์จะถูกจัดการแสดงผลภายในเวลาที่กำหนดโดยอัตโนมัติ)');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK','text','textlink','0','Text Links');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_DESCRIPTION_COMMENT','text','textlink','0','หมายเหตุหรือคำอธิบายเกี่ยวกับลิ้งค์ สั้นๆ');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_DETAILS','text','textlink','0','รายละเอียดของลิงค์');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_LOGO_COMMENT','text','textlink','0','อัปโหลดรูปภาพโลโกของลิงค์ (ถ้ามี) ชนิด jpg, gif, png เท่านั้น โลโกที่อัปโหลดควรมีขนาดเท่ากัน');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_NAME_COMMENT','text','textlink','0','กรอกชื่อของ Textlink ภาษาอังกฤษตัวพิมพ์เล็กและตัวเลขเท่านั้น ใช้สำหรับจัดกลุ่มลิงค์ที่ตำแหน่งเดียวกัน');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_PREFIX_TITLE','text','textlink','0','กรอกตัวเลขใช้สำหรับจัดกลุ่มลิงค์เพื่อไปแสดงยังตำแหน่งต่างๆ');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_PUBLISHED','text','textlink','0','การเผยแพร่ลิงค์');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_TEMPLATE_COMMENT','text','textlink','0','กรอกโค้ด HTML สำหรับลิงค์นี้ คุณสามารถเลือกใส่โค้ดที่มาจากแหล่งอื่นได้ เช่นโค้ด Adsense หรือระบุรายละเอียดของลิงค์ด้านล่าง');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_TEXT_COMMENT','text','textlink','0','ข้อความแสดงบนลิงค์ สามารถใช้ &lt;br&gt; เพื่อบังคับให้ข้อความขึ้นบรรทัดใหม่ได้');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_TITLE','text','textlink','0','ส่วนเสริมสำหรับควบคุมและจัดการแสดงผลลิงค์หรือป้ายโฆษณา');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_TYPE','text','textlink','0','ประเภทของลิงค์');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_TEXTLINK_TYPE_COMMENT','text','textlink','0','เลือกชนิดของลิงค์ที่ต้องการ หรือ เลือกรายการแรกสุด หากคุณต้องการกำหนดลิงค์นี้ด้วยตัวเอง เช่น Adsense');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('LNG_WIDGET_URL_COMMENT','text','textlink','0','กรอก URL ของลิงค์ (เช่น http://www.domain.tld)');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('TEXTLINK_TYPE_EMPTY','text','textlink','1','กรุณากรอกชนิดของลิงค์');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('TEXTLINK_TYPE_ERROR','text','textlink','1','ชนิดของลิงค์ต้องเป็นภาษาอังกฤษตัวพิมพ์เล็กและตัวเลขเท่านั้น');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('TEXTLINK_TYPIES','array','textlink','0','a:6:{s:6:"custom";s:44:"กำหนดเองเช่น Adsense";s:4:"text";s:66:"ลิงค์ข้อความอย่างเดียว";s:4:"menu";s:103:"ลิงค์ข้อความอย่างเดียว แสดงเป็นเมนู";s:5:"image";s:76:"แบนเนอร์รูปภาพ แสดงทั้งหมด";s:6:"banner";s:70:"แบนเนอร์รูปภาพ แสดงแบบวน";s:9:"slideshow";s:51:"แบนเนอร์ไสลด์โชว์";}');
INSERT INTO `{prefix}_language` (`key`, `type`, `owner`, `js`, `th`) VALUES ('URL_EMPTY','text','textlink','1','กรุณากรอก URL');