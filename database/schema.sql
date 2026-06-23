-- =====================================================================
--  网站质量管理工作台 (Website QA Center) · 数据库建表脚本
--  Engine : MySQL 8.0+ / utf8mb4
--  Version: V1.0 (MVP)
--  说明   : 对应原型的 6 大模块——网站档案 / 巡检 / 问题 / 评分 / 优化池 / 报表
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. 人员表
--    督导/质检、产品、运营、SEO、主管、管理员
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(50)  NOT NULL                COMMENT '姓名',
  `email`           VARCHAR(120) NOT NULL                COMMENT '登录邮箱',
  `password`        VARCHAR(255) NOT NULL                COMMENT '密码哈希',
  `role`            ENUM('admin','supervisor','pm','operator','seo','manager')
                                 NOT NULL DEFAULT 'supervisor' COMMENT '角色',
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1      COMMENT '是否在职 1=在职 0=离职',
  `remember_token`  VARCHAR(100) NULL,
  `created_at`      TIMESTAMP    NULL,
  `updated_at`      TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人员表';

-- ---------------------------------------------------------------------
-- 2. 网站档案表
--    40+ 网站的基础底座，缓存当前评分/等级便于排行榜快速读取
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `websites`;
CREATE TABLE `websites` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(80)  NOT NULL                 COMMENT '网站名称',
  `domain`         VARCHAR(120) NOT NULL                 COMMENT '域名',
  `pm_id`          BIGINT UNSIGNED NULL                  COMMENT '产品负责人',
  `operator_id`    BIGINT UNSIGNED NULL                  COMMENT '运营负责人',
  `seo_id`         BIGINT UNSIGNED NULL                  COMMENT 'SEO负责人',
  `manager_id`     BIGINT UNSIGNED NULL                  COMMENT '项目负责人',
  `online_at`      DATE         NULL                     COMMENT '上线时间',
  `status`         ENUM('normal','warning','offline') NOT NULL DEFAULT 'normal' COMMENT '当前状态',
  `current_score`  DECIMAL(5,1) NOT NULL DEFAULT 0       COMMENT '最近一次总分(缓存)',
  `current_grade`  CHAR(1)      NULL                      COMMENT '最近一次等级 A-E(缓存)',
  `last_inspected_at` TIMESTAMP NULL                     COMMENT '最近巡检时间',
  `created_at`     TIMESTAMP    NULL,
  `updated_at`     TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_websites_domain` (`domain`),
  KEY `idx_websites_score` (`current_score`),
  CONSTRAINT `fk_web_pm`  FOREIGN KEY (`pm_id`)       REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_web_op`  FOREIGN KEY (`operator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_web_seo` FOREIGN KEY (`seo_id`)      REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_web_mgr` FOREIGN KEY (`manager_id`)  REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站档案表';

-- ---------------------------------------------------------------------
-- 3. 评分维度配置表 (可后台维护权重)
--    产品质量30 / 内容运营25 / 用户体验20 / 商业化15 / 运营执行10
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `dimensions`;
CREATE TABLE `dimensions` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(40)  NOT NULL                     COMMENT '维度名称',
  `code`       VARCHAR(20)  NOT NULL                     COMMENT '维度代码 product/content/ux/ad/exec',
  `max_score`  TINYINT UNSIGNED NOT NULL                 COMMENT '满分值',
  `sort`       SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dim_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评分维度配置';

-- ---------------------------------------------------------------------
-- 4. 巡检检查项模板表 (巡检表单的勾选项)
--    每项绑定维度、分值、默认问题等级
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `check_items`;
CREATE TABLE `check_items` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dimension_id`  BIGINT UNSIGNED NOT NULL               COMMENT '所属维度',
  `name`          VARCHAR(80)  NOT NULL                   COMMENT '检查项名称',
  `points`        TINYINT UNSIGNED NOT NULL               COMMENT '该项分值',
  `default_level` ENUM('P0','P1','P2','P3') NOT NULL DEFAULT 'P2' COMMENT '异常时默认问题等级',
  `sort`          SMALLINT     NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_ci_dim` (`dimension_id`),
  CONSTRAINT `fk_ci_dim` FOREIGN KEY (`dimension_id`) REFERENCES `dimensions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='巡检检查项模板';

-- ---------------------------------------------------------------------
-- 5. 巡检记录表 (每次巡检一行，各维度得分 + 总分 + 等级)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `inspections`;
CREATE TABLE `inspections` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `website_id`    BIGINT UNSIGNED NOT NULL               COMMENT '网站',
  `inspector_id`  BIGINT UNSIGNED NOT NULL               COMMENT '巡检人(督导)',
  `inspect_date`  DATE         NOT NULL                   COMMENT '巡检日期',
  `score_product` DECIMAL(4,1) NOT NULL DEFAULT 0,
  `score_content` DECIMAL(4,1) NOT NULL DEFAULT 0,
  `score_ux`      DECIMAL(4,1) NOT NULL DEFAULT 0,
  `score_ad`      DECIMAL(4,1) NOT NULL DEFAULT 0,
  `score_exec`    DECIMAL(4,1) NOT NULL DEFAULT 0,
  `score_adjust`  DECIMAL(4,1) NOT NULL DEFAULT 0         COMMENT '问题整改加减分',
  `total_score`   DECIMAL(5,1) NOT NULL DEFAULT 0         COMMENT '总分',
  `grade`         CHAR(1)      NULL                        COMMENT '等级 A-E',
  `status`        ENUM('draft','submitted','reviewed') NOT NULL DEFAULT 'draft' COMMENT '巡检状态',
  `remark`        TEXT         NULL,
  `created_at`    TIMESTAMP    NULL,
  `updated_at`    TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_insp_web`  (`website_id`),
  KEY `idx_insp_date` (`inspect_date`),
  KEY `idx_insp_user` (`inspector_id`),
  CONSTRAINT `fk_insp_web`  FOREIGN KEY (`website_id`)   REFERENCES `websites`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_insp_user` FOREIGN KEY (`inspector_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='巡检记录表';

-- ---------------------------------------------------------------------
-- 6. 巡检明细表 (每次巡检对每个检查项的结果)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `inspection_results`;
CREATE TABLE `inspection_results` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inspection_id` BIGINT UNSIGNED NOT NULL,
  `check_item_id` BIGINT UNSIGNED NOT NULL,
  `is_normal`     TINYINT(1)   NOT NULL DEFAULT 1         COMMENT '1=正常 0=异常(扣分并生成问题)',
  `remark`        VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ir_insp` (`inspection_id`),
  CONSTRAINT `fk_ir_insp` FOREIGN KEY (`inspection_id`) REFERENCES `inspections`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ir_item` FOREIGN KEY (`check_item_id`) REFERENCES `check_items`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='巡检明细结果';

-- ---------------------------------------------------------------------
-- 7. 问题表 (体系核心)
--    巡检异常项可自动生成问题；支持手动建单
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `issues`;
CREATE TABLE `issues` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`          VARCHAR(20)  NOT NULL                   COMMENT '问题编号 如 #001',
  `website_id`    BIGINT UNSIGNED NOT NULL,
  `inspection_id` BIGINT UNSIGNED NULL                    COMMENT '来源巡检(手动建单为空)',
  `level`         ENUM('P0','P1','P2','P3') NOT NULL      COMMENT '问题等级',
  `type`          ENUM('product','operation','ad','content','seo','ux') NOT NULL COMMENT '问题分类',
  `title`         VARCHAR(120) NOT NULL                   COMMENT '问题标题',
  `description`   TEXT         NULL                        COMMENT '问题描述',
  `page_url`      VARCHAR(255) NULL                        COMMENT '问题页面链接',
  `reporter_id`   BIGINT UNSIGNED NOT NULL                COMMENT '提交人(督导)',
  `assignee_id`   BIGINT UNSIGNED NULL                    COMMENT '责任人(null=待指派)',
  `due_at`        DATETIME     NULL                        COMMENT '截止时间(按等级自动算)',
  `status`        ENUM('pending','processing','verifying','closed','evaluating')
                               NOT NULL DEFAULT 'pending'  COMMENT '状态流转',
  `repeat_count`  TINYINT UNSIGNED NOT NULL DEFAULT 0      COMMENT '重复打回次数',
  `closed_at`     TIMESTAMP    NULL,
  `created_at`    TIMESTAMP    NULL,
  `updated_at`    TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_issue_code` (`code`),
  KEY `idx_issue_web`    (`website_id`),
  KEY `idx_issue_status` (`status`),
  KEY `idx_issue_level`  (`level`),
  KEY `idx_issue_assi`   (`assignee_id`),
  KEY `idx_issue_due`    (`due_at`),
  CONSTRAINT `fk_issue_web`  FOREIGN KEY (`website_id`)    REFERENCES `websites`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_issue_insp` FOREIGN KEY (`inspection_id`) REFERENCES `inspections`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_issue_rep`  FOREIGN KEY (`reporter_id`)   REFERENCES `users`(`id`),
  CONSTRAINT `fk_issue_assi` FOREIGN KEY (`assignee_id`)   REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='问题表';

-- ---------------------------------------------------------------------
-- 8. 问题截图附件表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `issue_attachments`;
CREATE TABLE `issue_attachments` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `issue_id`   BIGINT UNSIGNED NOT NULL,
  `file_path`  VARCHAR(255) NOT NULL                      COMMENT '存储路径(本地/OSS)',
  `created_at` TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_att_issue` (`issue_id`),
  CONSTRAINT `fk_att_issue` FOREIGN KEY (`issue_id`) REFERENCES `issues`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='问题截图附件';

-- ---------------------------------------------------------------------
-- 9. 问题状态流转日志 (闭环审计：谁在何时把状态改成什么)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `issue_logs`;
CREATE TABLE `issue_logs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `issue_id`     BIGINT UNSIGNED NOT NULL,
  `from_status`  VARCHAR(20)  NULL,
  `to_status`    VARCHAR(20)  NOT NULL,
  `operator_id`  BIGINT UNSIGNED NOT NULL,
  `note`         VARCHAR(255) NULL,
  `created_at`   TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_log_issue` (`issue_id`),
  CONSTRAINT `fk_log_issue` FOREIGN KEY (`issue_id`)    REFERENCES `issues`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_user`  FOREIGN KEY (`operator_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='问题状态流转日志';

-- ---------------------------------------------------------------------
-- 10. 优化池表 (产品优化 + 运营优化，质检发现转需求/任务)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `suggestions`;
CREATE TABLE `suggestions` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `website_id`  BIGINT UNSIGNED NOT NULL,
  `type`        ENUM('product','operation') NOT NULL      COMMENT '产品池/运营池',
  `module`      VARCHAR(60)  NULL                          COMMENT '所属模块',
  `problem`     VARCHAR(255) NOT NULL                      COMMENT '发现的问题',
  `suggestion`  TEXT         NOT NULL                       COMMENT '优化建议',
  `priority`    ENUM('P0','P1','P2','P3') NOT NULL DEFAULT 'P2',
  `benefit`     ENUM('high','medium','low') NULL           COMMENT '收益预估',
  `status`      ENUM('pending','evaluating','accepted','rejected','done')
                            NOT NULL DEFAULT 'pending'      COMMENT '状态',
  `created_by`  BIGINT UNSIGNED NOT NULL,
  `created_at`  TIMESTAMP    NULL,
  `updated_at`  TIMESTAMP    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sug_web`  (`website_id`),
  KEY `idx_sug_type` (`type`),
  CONSTRAINT `fk_sug_web`  FOREIGN KEY (`website_id`) REFERENCES `websites`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sug_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='优化建议池';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  初始化数据：5 大评分维度 + 检查项模板
-- =====================================================================
INSERT INTO `dimensions` (`name`,`code`,`max_score`,`sort`) VALUES
  ('产品质量','product',30,1),
  ('内容运营','content',25,2),
  ('用户体验','ux',20,3),
  ('商业化运营','ad',15,4),
  ('运营执行','exec',10,5);

INSERT INTO `check_items` (`dimension_id`,`name`,`points`,`default_level`,`sort`) VALUES
  (1,'页面结构与展示',10,'P2',1),
  (1,'功能完整性',10,'P1',2),
  (1,'产品体验',10,'P2',3),
  (2,'内容更新',10,'P1',1),
  (2,'内容质量',10,'P2',2),
  (2,'内容运营策略',5,'P3',3),
  (3,'浏览体验',10,'P2',1),
  (3,'移动端体验',5,'P2',2),
  (3,'用户路径体验',5,'P3',3),
  (4,'广告配置检查',10,'P1',1),
  (4,'广告体验检查',5,'P2',2),
  (5,'页面维护情况',5,'P3',1),
  (5,'需求执行情况',5,'P3',2);
