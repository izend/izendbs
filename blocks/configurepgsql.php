<?php

/**
 *
 * @copyright  2014-2016 (2016) izend.org
 * @version    5 (5)
 * @link       http://www.izend.org
 */

function create_db($db_admin_user, $db_admin_password, $db_host, $db_name, $db_user, $db_password) {
	$dsn = "pgsql:host=$db_host";

	try {
		$db_conn = new PDO($dsn, $db_admin_user, $db_admin_password);
		$db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db_conn->exec("SET NAMES 'utf8'");

		$sql="CREATE ROLE \"$db_user\" WITH LOGIN PASSWORD '$db_password'";
		$db_conn->exec($sql);

		$sql="CREATE DATABASE \"$db_name\" WITH OWNER \"$db_user\" TEMPLATE template0 ENCODING 'UTF8'";
		$db_conn->exec($sql);
	}
	catch (PDOException $e) {
		throw($e);
	}

	$db_conn=null;

	return true;
}

function recover_db($db_admin_user, $db_admin_password, $db_host, $db_name, $db_user) {
	$dsn = "pgsql:host=$db_host";

	try {
		$db_conn = new PDO($dsn, $db_admin_user, $db_admin_password);
		$db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db_conn->exec("SET NAMES 'utf8'");

		$sql="DROP DATABASE \"$db_name\"";
		$db_conn->exec($sql);

		$sql="DROP ROLE \"$db_user\"";
		$db_conn->exec($sql);
	}
	catch (PDOException $e) {
	}

	$db_conn=null;

	return true;
}

function init_db($db_host, $db_name, $db_user, $db_password, $db_prefix, $site_admin_user, $site_admin_password, $site_admin_mail, $default_language) {
	$dsn = "pgsql:host=$db_host;dbname=$db_name";

	try {
		$db_conn = new PDO($dsn, $db_user, $db_password);
		$db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db_conn->exec("SET NAMES 'utf8'");

		$db_conn->exec("CREATE OR REPLACE FUNCTION FROM_UNIXTIME(integer) RETURNS timestamp AS 'SELECT TO_TIMESTAMP($1)::timestamp AS result;' LANGUAGE 'SQL';");
		$db_conn->exec("CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP() RETURNS bigint AS 'SELECT EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))::bigint AS result;' LANGUAGE 'SQL';");
		$db_conn->exec("CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP(timestamp with time zone) RETURNS bigint AS 'SELECT EXTRACT(EPOCH FROM $1)::bigint AS result;' LANGUAGE 'SQL';");
		$db_conn->exec("CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP(timestamp without time zone) RETURNS bigint AS 'SELECT EXTRACT(EPOCH FROM $1)::bigint AS result;' LANGUAGE 'SQL';");
		$db_conn->exec("CREATE OR REPLACE FUNCTION INET_ATON(inet) RETURNS bigint AS 'SELECT INETMI($1,''0.0.0.0'');' LANGUAGE 'SQL';");
		$db_conn->exec("CREATE OR REPLACE FUNCTION INET_NTOA(bigint) RETURNS inet AS 'SELECT ''0.0.0.0''::inet+$1;' LANGUAGE 'SQL';");
		$db_conn->exec("CREATE OR REPLACE FUNCTION STRFLAT(text) RETURNS text AS 'SELECT TRANSLATE($1, ''ŠšŽžÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝŸÞàáâãäåæçèéêëìíîïñòóôõöøùúûüýÿþƒ'', ''SsZzAAAAAAACEEEEIIIINOOOOOOUUUUYYBaaaaaaaceeeeiiiinoooooouuuuyybf'');' LANGUAGE 'SQL';");

		$sql= <<<_SEP_
CREATE TYPE "${db_prefix}type_locale" AS ENUM('en','fr');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE FUNCTION ${db_prefix}type_locale(unknown) RETURNS ${db_prefix}type_locale AS 'SELECT $1::text::${db_prefix}type_locale;' LANGUAGE 'SQL';
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE CAST (unknown as ${db_prefix}type_locale) WITH FUNCTION ${db_prefix}type_locale(unknown) AS ASSIGNMENT;
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}comment" (
  "comment_id" SERIAL,
  "node_id" integer NOT NULL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "created" timestamp NOT NULL,
  "edited" timestamp NOT NULL,
  "user_id" integer NOT NULL DEFAULT '0',
  "user_mail" varchar(100) DEFAULT NULL,
  "ip_address" bigint NOT NULL,
  "text" text NOT NULL,
  PRIMARY KEY ("comment_id")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE INDEX "${db_prefix}comment_index_node" ON "${db_prefix}comment" ("node_id", "locale");
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}content_download" (
  "content_id" SERIAL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "name" varchar(50) DEFAULT NULL,
  "path" varchar(200) DEFAULT NULL,
  PRIMARY KEY ("content_id","locale")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}content_file" (
  "content_id" SERIAL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "path" varchar(200) DEFAULT NULL,
  "start" integer NOT NULL DEFAULT '0',
  "end" integer NOT NULL DEFAULT '0',
  "format" varchar(20) DEFAULT NULL,
  "lineno" boolean NOT NULL DEFAULT '1',
  PRIMARY KEY ("content_id","locale")
);
_SEP_;
		$db_conn->exec($sql);

			$sql= <<<_SEP_
CREATE TABLE "${db_prefix}content_infile" (
  "content_id" SERIAL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "path" varchar(200) DEFAULT NULL,
  PRIMARY KEY ("content_id","locale")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TYPE "${db_prefix}type_content_longtail_controlbar" AS ENUM('none','bottom','top','over');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE FUNCTION ${db_prefix}type_content_longtail_controlbar(unknown) RETURNS ${db_prefix}type_content_longtail_controlbar AS 'SELECT $1::text::${db_prefix}type_content_longtail_controlbar;' LANGUAGE 'SQL';
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE CAST (unknown as ${db_prefix}type_content_longtail_controlbar) WITH FUNCTION ${db_prefix}type_content_longtail_controlbar(unknown) AS ASSIGNMENT;
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}content_longtail" (
  "content_id" SERIAL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "file" varchar(200) DEFAULT NULL,
  "image" varchar(200) DEFAULT NULL,
  "width" integer NOT NULL DEFAULT '0',
  "height" integer NOT NULL DEFAULT '0',
  "icons" boolean NOT NULL DEFAULT '0',
  "skin" varchar(200) DEFAULT NULL,
  "controlbar" ${db_prefix}type_content_longtail_controlbar NOT NULL DEFAULT 'none',
  "duration" integer NOT NULL DEFAULT '0',
  "autostart" boolean NOT NULL DEFAULT '0',
  "repeat" boolean NOT NULL DEFAULT '0',
  PRIMARY KEY ("content_id","locale")
);
_SEP_;
		$db_conn->exec($sql);

			$sql= <<<_SEP_
CREATE TABLE "${db_prefix}content_text" (
  "content_id" SERIAL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "text" text,
  "eval" boolean NOT NULL DEFAULT '0',
  PRIMARY KEY ("content_id","locale")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TYPE "${db_prefix}type_content_youtube_theme" AS ENUM('light','dark');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE FUNCTION ${db_prefix}type_content_youtube_theme(unknown) RETURNS ${db_prefix}type_content_youtube_theme AS 'SELECT $1::text::${db_prefix}type_content_youtube_theme;' LANGUAGE 'SQL';
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE CAST (unknown as ${db_prefix}type_content_youtube_theme) WITH FUNCTION ${db_prefix}type_content_youtube_theme(unknown) AS ASSIGNMENT;
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}content_youtube" (
  "content_id" SERIAL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "id" varchar(20) DEFAULT NULL,
  "width" integer NOT NULL DEFAULT '0',
  "height" integer NOT NULL DEFAULT '0',
  "miniature" varchar(200) DEFAULT NULL,
  "title" varchar(200) DEFAULT NULL,
  "autoplay" boolean NOT NULL DEFAULT '0',
  "controls" boolean NOT NULL DEFAULT '0',
  "fs" boolean NOT NULL DEFAULT '0',
  "theme" ${db_prefix}type_content_youtube_theme NOT NULL DEFAULT 'dark',
  "rel" boolean NOT NULL DEFAULT '0',
  PRIMARY KEY ("content_id","locale")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}newsletter_post" (
  "thread_id" integer NOT NULL,
  "node_id" integer NOT NULL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "scheduled" timestamp NOT NULL,
  "mailed" timestamp DEFAULT NULL,
  PRIMARY KEY ("thread_id","node_id","locale")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}newsletter_user" (
  "mail" varchar(100) NOT NULL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "created" timestamp NOT NULL,
  PRIMARY KEY ("mail")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE INDEX "${db_prefix}newsletter_user_locale" ON "${db_prefix}newsletter_user" ("locale");
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}node" (
  "node_id" SERIAL,
  "user_id" integer NOT NULL,
  "created" timestamp NOT NULL,
  "modified" timestamp NOT NULL,
  "visits" boolean NOT NULL DEFAULT '1',
  "nocomment" boolean NOT NULL DEFAULT '0',
  "nomorecomment" boolean NOT NULL DEFAULT '0',
  "novote" boolean NOT NULL DEFAULT '0',
  "nomorevote" boolean NOT NULL DEFAULT '0',
  "ilike" boolean NOT NULL DEFAULT '1',
  "tweet" boolean NOT NULL DEFAULT '1',
  "plusone" boolean NOT NULL DEFAULT '1',
  "linkedin" boolean NOT NULL DEFAULT '1',
  "pinit" boolean NOT NULL DEFAULT '0',
  PRIMARY KEY ("node_id")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}node_locale" (
  "node_id" integer NOT NULL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "name" varchar(100) NOT NULL,
  "title" varchar(100) NULL default NULL,
  "abstract" text,
  "cloud" text,
  "image" varchar(200) DEFAULT NULL,
  "visited" integer NOT NULL DEFAULT '0',
  PRIMARY KEY ("node_id","locale")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TYPE "${db_prefix}type_content_type" AS ENUM('text','file','download','infile','youtube','longtail');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE FUNCTION ${db_prefix}type_content_type(unknown) RETURNS ${db_prefix}type_content_type AS 'SELECT $1::text::${db_prefix}type_content_type;' LANGUAGE 'SQL';
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE CAST (unknown as ${db_prefix}type_content_type) WITH FUNCTION ${db_prefix}type_content_type(unknown) AS ASSIGNMENT;
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}node_content" (
  "node_id" integer NOT NULL,
  "content_id" integer NOT NULL,
  "content_type" ${db_prefix}type_content_type NOT NULL DEFAULT 'text',
  "number" integer NOT NULL,
  "ignored" boolean NOT NULL DEFAULT '0',
  PRIMARY KEY ("node_id","content_id","content_type")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TYPE "${db_prefix}type_thread_thread_type" AS ENUM('thread','folder','story','book','rss','newsletter');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE FUNCTION ${db_prefix}type_thread_thread_type(unknown) RETURNS ${db_prefix}type_thread_thread_type AS 'SELECT $1::text::${db_prefix}type_thread_thread_type;' LANGUAGE 'SQL';
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE CAST (unknown as ${db_prefix}type_thread_thread_type) WITH FUNCTION ${db_prefix}type_thread_thread_type(unknown) AS ASSIGNMENT;
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}thread" (
  "thread_id" SERIAL,
  "user_id" integer NOT NULL DEFAULT '1',
  "thread_type" ${db_prefix}type_thread_thread_type NOT NULL DEFAULT 'thread',
  "created" timestamp NOT NULL,
  "modified" timestamp NOT NULL,
  "number" integer NOT NULL,
  "visits" boolean NOT NULL DEFAULT '1',
  "nosearch" boolean NOT NULL DEFAULT '0',
  "nocloud" boolean NOT NULL DEFAULT '0',
  "nocomment" boolean NOT NULL DEFAULT '0',
  "nomorecomment" boolean NOT NULL DEFAULT '0',
  "novote" boolean NOT NULL DEFAULT '0',
  "nomorevote" boolean NOT NULL DEFAULT '0',
  "ilike" boolean NOT NULL DEFAULT '1',
  "tweet" boolean NOT NULL DEFAULT '1',
  "plusone" boolean NOT NULL DEFAULT '1',
  "linkedin" boolean NOT NULL DEFAULT '1',
  "pinit" boolean NOT NULL DEFAULT '1',
  PRIMARY KEY ("thread_id")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}thread_locale" (
  "thread_id" integer NOT NULL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "name" varchar(100) NOT NULL,
  "title" varchar(100) DEFAULT NULL,
  "abstract" text,
  "cloud" text,
  "image" varchar(200) DEFAULT NULL,
  PRIMARY KEY ("thread_id","locale")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}thread_node" (
  "thread_id" integer NOT NULL,
  "node_id" integer NOT NULL,
  "number" integer NOT NULL,
  "ignored" boolean NOT NULL DEFAULT '0',
  PRIMARY KEY ("thread_id","node_id")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}tag" (
  "tag_id" SERIAL,
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "name" varchar(100) NOT NULL,
  PRIMARY KEY ("tag_id","locale"),
  UNIQUE ("locale","name")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}tag_index" (
  "tag_id" integer NOT NULL,
  "node_id" integer NOT NULL,
  PRIMARY KEY ("tag_id","node_id")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}user" (
  "user_id" SERIAL,
  "name" varchar(40) DEFAULT NULL,
  "password" char(32) NOT NULL,
  "newpassword" char(32) DEFAULT NULL,
  "seed" char(8) NOT NULL,
  "mail" varchar(100) DEFAULT NULL,
  "website" varchar(100) DEFAULT NULL,
  "timezone" varchar(100) DEFAULT NULL,
  "created" timestamp NOT NULL,
  "modified" timestamp DEFAULT NULL,
  "accessed" timestamp DEFAULT NULL,
  "logged" integer NOT NULL DEFAULT '0',
  "locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "active" boolean NOT NULL DEFAULT '1',
  "banned" boolean NOT NULL DEFAULT '0',
  PRIMARY KEY ("user_id"),
  UNIQUE ("name"),
  UNIQUE ("mail")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE IF NOT EXISTS "${db_prefix}user_info" (
  "user_id" integer NOT NULL,
  "lastname" varchar(100) DEFAULT NULL,
  "firstname" varchar(100) DEFAULT NULL,
  PRIMARY KEY ("user_id")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}role" (
  "role_id" SERIAL,
  "name" varchar(40) NOT NULL,
  PRIMARY KEY ("role_id"),
  UNIQUE ("name")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}user_role" (
  "user_id" integer NOT NULL,
  "role_id" integer NOT NULL,
  PRIMARY KEY ("user_id","role_id")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE INDEX "${db_prefix}user_role_role" ON "${db_prefix}user_role" ("role_id");
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}registry" (
  "name" varchar(100) NOT NULL,
  "value" text NOT NULL,
  PRIMARY KEY ("name")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}track" (
  "track_id" SERIAL,
  "time_stamp" timestamp NOT NULL DEFAULT NOW(),
  "ip_address" bigint NOT NULL,
  "request_uri" varchar(255) NOT NULL,
  "user_agent" varchar(255) DEFAULT NULL,
  PRIMARY KEY ("track_id")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TYPE "${db_prefix}type_vote_content_type" AS ENUM('node','thread','comment');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE FUNCTION ${db_prefix}type_vote_content_type(unknown) RETURNS ${db_prefix}type_vote_content_type AS 'SELECT $1::text::${db_prefix}type_vote_content_type;' LANGUAGE 'SQL';
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE CAST (unknown as ${db_prefix}type_vote_content_type) WITH FUNCTION ${db_prefix}type_vote_content_type(unknown) AS ASSIGNMENT;
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
CREATE TABLE "${db_prefix}vote" (
  "vote_id" SERIAL,
  "content_id" integer NOT NULL,
  "content_type" ${db_prefix}type_vote_content_type NOT NULL DEFAULT 'node',
  "content_locale" ${db_prefix}type_locale NOT NULL DEFAULT '$default_language',
  "created" timestamp NOT NULL,
  "user_id" integer NOT NULL DEFAULT '0',
  "ip_address" bigint NOT NULL,
  "value" integer NOT NULL DEFAULT '1',
  PRIMARY KEY ("vote_id"),
  UNIQUE ("content_id","content_type","content_locale","ip_address","user_id")
);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}role" ("role_id", "name") VALUES
(1, 'administrator'),
(2, 'writer'),
(3, 'reader'),
(4, 'moderator'),
(5, 'member');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}role_role_id_seq', (SELECT MAX("role_id") FROM "${db_prefix}role"));
_SEP_;
		$db_conn->exec($sql);

		$seed=substr(md5(uniqid()), 1, 8);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}user" ("user_id", "name", "password", "seed", "mail", "created", "locale", "active", "banned") VALUES
(1, '$site_admin_user', MD5(CONCAT('$seed', '$site_admin_password')), '$seed', '$site_admin_mail', NOW(), '$default_language', '1', '0');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}user_user_id_seq', (SELECT MAX("user_id") FROM "${db_prefix}user"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}user_role" ("user_id", "role_id") VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}comment" ("comment_id", "node_id", "locale", "created", "edited", "user_id", "user_mail", "ip_address", "text") VALUES
(1, 2, 'en', NOW(), NOW(), 1, NULL, INET_ATON('127.0.0.1'), '[p]Let me try a comment with a url: [url=http://www.izend.org]iZend[/url]![/p]'),
(2, 2, 'en', NOW(), NOW(), 1, NULL, INET_ATON('127.0.0.1'), '[p][u]Quote[/u]:[/p][quote]Let me try a comment with a url: [url=http://www.izend.org]iZend[/url]![/quote]\r\n[p]No! One can put a [b]url[/b] in a comment?\r\n[br]Don\'t tell me it\'s not true![/p]'),
(3, 2, 'fr', NOW(), NOW(), 1, NULL, INET_ATON('127.0.0.1'), '[p]J\'essaye un commentaire avec une url : [url=http://www.izend.org]iZend[/url] ![/p]'),
(4, 2, 'fr', NOW(), NOW(), 1, NULL, INET_ATON('127.0.0.1'), '[p][u]Citation[/u] :[/p][quote]J\'essaye un commentaire avec une url : [url=http://www.izend.org]iZend[/url] ![/quote]\r\n[p]Non ! On peut mettre une [b]url[/b] dans un commentaire ?\r\n[br]Dis-moi pas que c\'est pas vrai ![/p]');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}comment_comment_id_seq', (SELECT MAX("comment_id") FROM "${db_prefix}comment"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}node" ("node_id", "user_id", "created", "modified", "visits", "nocomment", "nomorecomment", "novote", "nomorevote", "ilike", "tweet", "plusone", "linkedin", "pinit") VALUES
(1, 1, NOW(), NOW(), 0, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(2, 1, NOW(), NOW(), 1, 0, 1, 0, 0, 1, 1, 1, 1, 0),
(3, 1, NOW(), NOW(), 1, 1, 1, 0, 0, 1, 1, 1, 1, 0),
(4, 1, NOW(), NOW(), 0, 1, 1, 1, 1, 0, 0, 0, 0, 0);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}node_node_id_seq', (SELECT MAX("node_id") FROM "${db_prefix}node"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}node_locale" ("node_id", "locale", "name", "title", "abstract", "cloud") VALUES
(1, 'en', 'welcome', 'Welcome', NULL, NULL),
(1, 'fr', 'bienvenue', 'Bienvenue', NULL, NULL),
(2, 'en', 'contents', 'Contents', NULL, 'content text PHP insertion file download audio video LongTail YouTube HTML5 jQuery UI calendar', NULL),
(2, 'fr', 'contenus', 'Contenus', NULL, 'contenu texte PHP insertion fichier téléchargement audio vidéo LongTail YouTube HTML5 jQuery UI calendrier', NULL),
(3, 'en', 'slideshow', 'Slideshow', 'Show an animated gallery of images which displays a full page slide show and a gallery of images which starts playing YouTube videos.', 'gallery image video jQuery Cycle Colorbox YouTube QRmii QR cat dog', NULL),
(3, 'fr', 'diaporama', 'Diaporama', 'Montrez une galerie d\'images animée qui affiche un diaporama pleine page et une galerie d\'images qui démarre la lecture de vidéos YouTube.', 'galerie image vidéo jQuery Cycle Colorbox YouTube QRmii QR Imagin Raytracer chat chien', NULL),
(4, 'en', 'qrmii', 'What is a QRmii?', 'A QRmii is QR code which contains a short URL which is automatically redirected to a complete URL.', 'QRmii QR URL redirection', NULL),
(4, 'fr', 'qrmii', 'Qu\'est-ce qu\'un QRmii ?', 'Un QRmii est un code QR qui contient une URL courte qui est automatiquement redirigée vers une URL complète.', 'QRmii QR URL redirection', NULL);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}node_content" ("node_id", "content_id", "content_type", "number") VALUES
(1, 1, 'infile', 1),
(1, 1, 'text', 2),
(1, 2, 'text', 3),
(1, 3, 'text', 4),
(1, 1, 'youtube', 5),
(1, 2, 'infile', 6),
(2, 4, 'text', 1),
(2, 5, 'text', 2),
(2, 6, 'text', 3),
(2, 7, 'text', 4),
(2, 8, 'text', 5),
(2, 2, 'youtube', 6),
(2, 9, 'text', 7),
(2, 1, 'longtail', 8),
(2, 10, 'text', 9),
(2, 2, 'longtail', 10),
(2, 11, 'text', 11),
(2, 1, 'download', 12),
(2, 12, 'text', 13),
(2, 1, 'file', 14),
(2, 13, 'text', 15),
(2, 3, 'infile', 16),
(2, 14, 'text', 17),
(3, 4, 'infile', 1),
(3, 15, 'text', 2),
(3, 16, 'text', 3),
(3, 5, 'infile', 4),
(3, 17, 'text', 5),
(3, 18, 'text', 6),
(4, 19, 'text', 1),
(4, 20, 'text', 2);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}content_text" ("content_id", "locale", "text", "eval") VALUES
(1, 'en', '<p>Your <b>iZend</b> site is now operational.\r\nConsult <a href="/en/article/test">the example pages</a>.</p>', 0),
(1, 'fr', '<p>Votre site <b>iZend</b> est maintenant opérationnel.\r\nConsultez <a href="/fr/article/test">les pages d\'exemples</a>.</p>', 0),
(2, 'en', '<p style="width:320px;max-width:100%">\r\n<audio controls>\r\n<source src="/files/sounds/smoke.ogg" type="audio/ogg" />\r\n<source src="/files/sounds/smoke.m4a" type="audio/m4a" />\r\n<source src="/files/sounds/smoke.mp3" type="audio/mpeg" />\r\n</audio>\r\n</p>\r\n<?php head(\'javascript\', \'audioplayer\'); ?>\r\n<?php head(\'stylesheet\', \'audioplayer\', \'screen\'); ?>\r\n<script type="text/javascript">\r\n$(document).ready(function() { $(\'audio\').audioPlayer(); });\r\n</script>', 1),
(2, 'fr', '<p style="width:320px;max-width:100%">\r\n<audio controls>\r\n<source src="/files/sounds/smoke.ogg" type="audio/ogg" />\r\n<source src="/files/sounds/smoke.m4a" type="audio/m4a" />\r\n<source src="/files/sounds/smoke.mp3" type="audio/mpeg" />\r\n</audio>\r\n</p>\r\n<?php head(\'javascript\', \'audioplayer\'); ?>\r\n<?php head(\'stylesheet\', \'audioplayer\', \'screen\'); ?>\r\n<script type="text/javascript">\r\n$(document).ready(function() { $(\'audio\').audioPlayer(); });\r\n</script>', 1),
(3, 'en', '<p>Validated with\r\n<span class="browser-firefox" title="Firefox">Firefox</span>,\r\n<span class="browser-chrome" title="Chrome">Chrome</span>,\r\n<span class="browser-safari" title="Safari">Safari</span>,\r\n<span class="browser-opera" title="Opera">Opera</span>\r\nand\r\n<span class="nowrap"><span class="browser-ie" title="Internet Explorer">Internet Explorer</span>.</span>\r\n</p>\r\n<p class="hidden-print">Read <a href="http://www.izend.org/en/documentation">the documentation</a> on-line.</p>', 0),
(3, 'fr', '<p>Validé avec\r\n<span class="browser-firefox" title="Firefox">Firefox</span>,\r\n<span class="browser-chrome" title="Chrome">Chrome</span>,\r\n<span class="browser-safari" title="Safari">Safari</span>,\r\n<span class="browser-opera" title="Opera">Opera</span>\r\net\r\n<span class="nowrap"><span class="browser-ie" title="Internet Explorer">Internet Explorer</span>.</span>\r\n</p>\r\n<p class="hidden-print">Lisez <a href="http://www.izend.org/fr/documentation">la documentation</a> en ligne.</p>', 0),
(4, 'en', '<div class="media">\r\n<div class="media-left media-top">\r\n<p><a href="http://www.izend.org"><img class="media-object" src="/logos/izend.png" alt="" title="" /></a></p>\r\n</div>\r\n<div class="media-body">\r\n<p>Lorem ipsum dolor sit amet, alterum antiopam maluisset vis eu, et brute expetenda iracundia has. Eos animal nusquam delicata ad. Cetero legendos in pri, no usu quidam utamur. Vel quodsi voluptua cu, eam ex reque audire vidisse.</p>\r\n</div>\r\n</div>\r\n<div class="panel panel-warning pull-right">\r\n<div class="panel-body bg-warning">\r\n<ol class="nav nav-pills nav-stacked text-right">\r\n<li><a href="#">Duo ridens</a></li>\r\n<li><a href="#">Tale posidonium</a></li>\r\n<li><a href="#">Cu mea ferri</a></li>\r\n</ol>\r\n</div>\r\n</div>\r\n<p>Text <b>bold</b>, <i>italics</i>, <u>underlined</u> and <s>striked</s>.</p>\r\n<h4>H4</h4>\r\n<p>Paragraph with some <code>code embedded</code> in the text.</p>\r\n<h5>H5</h5>\r\n<blockquote>\r\n<p>Et scaevola principes elaboraret mea. At usu docendi epicurei, et ferri sensibus deterruisset nec, mei solet persius dignissim te. Vix velit rationibus at. Ei eum simul suscipit, assum munere recusabo vix no.</p>\r\n<footer><cite title="iZend">iZend</cite></footer>\r\n</blockquote>\r\n<h6>H6</h6>\r\n<p>A series of commands:</p>\r\n<pre><code>$ ls -l\r\n$ pwd</code></pre>\r\n<h6>Image</h6>\r\n<p><img class="img-responsive" src="/files/images/pinit.jpg" alt="" title="www.izend.org" /></p>\r\n<h6>Table</h6>\r\n<table class="table table-striped table-auto">\r\n<thead>\r\n<tr><th>French</th><th>English</th></tr>\r\n</thead>\r\n<tbody>\r\n<tr><td>Un</td><td>One</td></tr>\r\n<tr><td>Deux</td><td>Two</td></tr>\r\n<tr><td>Trois</td><td>Three</td></tr>\r\n</tbody>\r\n</table>\r\n<h6>Tree</h6>\r\n<div class="tree-group">\r\n<b><em>/folder</em></b>\r\n<ol>\r\n  <li>\r\n    <em>folder</em>\r\n    <ol>\r\n      <li>\r\n        <em>folder</em>\r\n        <ol>\r\n          <li>file</li>\r\n          <li>file</li>\r\n        </ol>\r\n      </li>\r\n      <li>\r\n        <em>folder</em>\r\n        <ol>\r\n          <li>file</li>\r\n        </ol>\r\n      </li>\r\n    </ol>\r\n  </li>\r\n  <li>\r\n    <em>folder</em>\r\n    <ol>\r\n      <li>file</li>\r\n      <li>file</li>\r\n    </ol>\r\n  </li>\r\n  <li>file</li>\r\n</ol>\r\n</div>\r\n<h6>Columns</h6>\r\n<div class="row">\r\n<div class="col-md-4">\r\n<p>No dolor invenire adversarium nam, erat suscipit per no. Id duo summo mollis.</p>\r\n</div>\r\n<div class="col-md-4">\r\n<p>Per ut illud tempor. Ut vis laboramus voluptatibus. Vel oporteat ullamcorper id, modus decore luptatum vim ea. Nec ex brute placerat, feugiat percipitur eos ea, fabulas principes ea sit.</p>\r\n</div>\r\n<div class="col-md-4">\r\n<p><img class="img-right" src="/logos/izend.png" alt="" title="www.izend.org" />Ad eam odio evertitur neglegentur, verterem disputationi eam ex. Sed no solet homero voluptua. Mei et legendos dissentias, ea dolorem oportere vix.</p>\r\n</div>\r\n</div>', 0),
(4, 'fr', '<div class="media">\r\n<div class="media-left media-top">\r\n<p><a href="http://www.izend.org"><img class="media-object" src="/logos/izend.png" alt="" title="" /></a></p>\r\n</div>\r\n<div class="media-body">\r\n<p>Lorem ipsum dolor sit amet, alterum antiopam maluisset vis eu, et brute expetenda iracundia has. Eos animal nusquam delicata ad. Cetero legendos in pri, no usu quidam utamur. Vel quodsi voluptua cu, eam ex reque audire vidisse.</p>\r\n</div>\r\n</div>\r\n<div class="panel panel-warning pull-right">\r\n<div class="panel-body bg-warning">\r\n<ol class="nav nav-pills nav-stacked text-right">\r\n<li><a href="#">Duo ridens</a></li>\r\n<li><a href="#">Tale posidonium</a></li>\r\n<li><a href="#">Cu mea ferri</a></li>\r\n</ol>\r\n</div>\r\n</div>\r\n<p>Texte en <b>gras</b>, en <i>italique</i>, <u>souligné</u> et <s>barré</s>.</p>\r\n<h4>H4</h4>\r\n<p>Paragraphe avec du <code>code inséré</code> dans le texte.</p>\r\n<h5>H5</h5>\r\n<blockquote>\r\n<p>Et scaevola principes elaboraret mea. At usu docendi epicurei, et ferri sensibus deterruisset nec, mei solet persius dignissim te. Vix velit rationibus at. Ei eum simul suscipit, assum munere recusabo vix no.</p>\r\n<footer><cite title="iZend">iZend</cite></footer>\r\n</blockquote>\r\n<h6>H6</h6>\r\n<p>Une série de commandes&nbsp;:</p>\r\n<pre><code>$ ls -l\r\n$ pwd</code></pre>\r\n<h6>Image</h6>\r\n<p><img class="img-responsive" src="/files/images/pinit.jpg" alt="" title="www.izend.org" /></p>\r\n<h6>Tableau</h6>\r\n<table class="table table-striped table-auto">\r\n<thead>\r\n<tr><th>Français</th><th>Anglais</th></tr>\r\n</thead>\r\n<tbody>\r\n<tr><td>Un</td><td>One</td></tr>\r\n<tr><td>Deux</td><td>Two</td></tr>\r\n<tr><td>Trois</td><td>Three</td></tr>\r\n</tbody>\r\n</table>\r\n<h6>Arbre</h6>\r\n<div class="tree-group">\r\n<b><em>/dossier</em></b>\r\n<ol>\r\n  <li>\r\n    <em>dossier</em>\r\n    <ol>\r\n      <li>\r\n        <em>dossier</em>\r\n        <ol>\r\n          <li>fichier</li>\r\n          <li>fichier</li>\r\n        </ol>\r\n      </li>\r\n      <li>\r\n        <em>dossier</em>\r\n        <ol>\r\n          <li>fichier</li>\r\n        </ol>\r\n      </li>\r\n    </ol>\r\n  </li>\r\n  <li>\r\n    <em>dossier</em>\r\n    <ol>\r\n      <li>fichier</li>\r\n      <li>fichier</li>\r\n    </ol>\r\n  </li>\r\n  <li>fichier</li>\r\n</ol>\r\n</div>\r\n<h6>Colonnes</h6>\r\n<div class="row">\r\n<div class="col-md-4">\r\n<p>No dolor invenire adversarium nam, erat suscipit per no. Id duo summo mollis.</p>\r\n</div>\r\n<div class="col-md-4">\r\n<p>Per ut illud tempor. Ut vis laboramus voluptatibus. Vel oporteat ullamcorper id, modus decore luptatum vim ea. Nec ex brute placerat, feugiat percipitur eos ea, fabulas principes ea sit.</p>\r\n</div>\r\n<div class="col-md-4">\r\n<p><img class="img-right" src="/logos/izend.png" alt="" title="www.izend.org" />Ad eam odio evertitur neglegentur, verterem disputationi eam ex. Sed no solet homero voluptua. Mei et legendos dissentias, ea dolorem oportere vix.</p>\r\n</div>\r\n</div>', 0),
(5, 'en', '<h5>jQuery UI</h5>\r\n<h6>Calendar</h6>\r\n<div class="form-group form-inline-auto"><input type="text" class="form-control" id="calendar" title="aaaa-mm-jj" /></div>', 0),
(5, 'fr', '<h5>jQuery UI</h5>\r\n<h6>Calendrier</h6>\r\n<div class="form-group form-inline-auto"><input type="text" class="form-control" id="calendar" title="aaaa-mm-jj" /></div>', 0),
(6, 'en', '<?php head(\'javascript\', \'jquery-ui\'); ?>\r\n<?php head(\'stylesheet\', \'jquery-ui\', \'screen\'); ?>\r\n<script type="text/javascript">\r\n$(document).ready(function() {\r\n    $(\'#calendar\').datepicker({dateFormat: \'yy-mm-dd\', autoSize: true, showAnim: \'drop\', showOn: \'both\', buttonText: \'Calendar\', buttonImage: \'/images/theme/edit/calendar.png\', buttonImageOnly: true, minDate: \'today\', maxDate: \'+2m\', showOtherMonths: true, navigationAsDateFormat: true, prevText: \'MM\', nextText: \'MM\', beforeShowDay: function(date) {return [date.getDay() != 0];}});\r\n});\r\n</script>', 1),
(6, 'fr', '<?php head(\'javascript\', \'jquery-ui\'); ?>\r\n<?php head(\'javascript\', \'jquery.ui.datepicker-fr\'); ?>\r\n<?php head(\'stylesheet\', \'jquery-ui\', \'screen\'); ?>\r\n<script type="text/javascript">\r\n$(document).ready(function() {\r\n    $(\'#calendar\').datepicker({dateFormat: \'yy-mm-dd\', autoSize: true, showAnim: \'drop\', showOn: \'both\', buttonText: \'Calendrier\', buttonImage: \'/images/theme/edit/calendar.png\', buttonImageOnly: true, minDate: \'today\', maxDate: \'+2m\', showOtherMonths: true, navigationAsDateFormat: true, prevText: \'MM\', nextText: \'MM\', beforeShowDay: function(date) {return [date.getDay() != 0];}});\r\n});\r\n</script>', 1),
(7, 'en', '<h5 class="hidden-print">HTML5</h5>\r\n<h6 class="hidden-print">Audio</h6>\r\n<audio controls loop>\r\n<source src="/files/sounds/smoke.ogg" type="audio/ogg" />\r\n<source src="/files/sounds/smoke.m4a" type="audio/m4a" />\r\n<source src="/files/sounds/smoke.mp3" type="audio/mpeg" />\r\n</audio>\r\n<h6 class="hidden-print">Video</h6>\r\n<video controls width="640" height="272" poster="http://www.jplayer.org/video/poster/Incredibles_Teaser_640x272.png">\r\n<source src="http://www.jplayer.org/video/ogv/Incredibles_Teaser.ogv" type="video/ogg" />\r\n<source src="http://www.jplayer.org/video/m4v/Incredibles_Teaser.m4v" type="video/mp4" />\r\n<source src="http://www.jplayer.org/video/webm/Incredibles_Teaser.webm" type="video/webm" />\r\n</video>', 0),
(7, 'fr', '<h5 class="hidden-print">HTML5</h5>\r\n<h6 class="hidden-print">Audio</h6>\r\n<audio controls loop>\r\n<source src="/files/sounds/smoke.ogg" type="audio/ogg" />\r\n<source src="/files/sounds/smoke.m4a" type="audio/m4a" />\r\n<source src="/files/sounds/smoke.mp3" type="audio/mpeg" />\r\n</audio>\r\n<h6 class="hidden-print">Vidéo</h6>\r\n<video controls width="640" height="272" poster="http://www.jplayer.org/video/poster/Incredibles_Teaser_640x272.png">\r\n<source src="http://www.jplayer.org/video/ogv/Incredibles_Teaser.ogv" type="video/ogg" />\r\n<source src="http://www.jplayer.org/video/m4v/Incredibles_Teaser.m4v" type="video/mp4" />\r\n<source src="http://www.jplayer.org/video/webm/Incredibles_Teaser.webm" type="video/webm" />\r\n</video>', 0),
(8, 'en', '<h6 class="hidden-print"><img src="/images/youtube.png" alt="" title="YouTube"/></h6>', 0),
(8, 'fr', '<h6 class="hidden-print"><img src="/images/youtube.png" alt="" title="YouTube"/></h6>', 0),
(9, 'en', '<h5 class="hidden-print">LongTail</h5>\r\n<h6 class="hidden-print">Audio</h6>', 0),
(9, 'fr', '<h5 class="hidden-print">LongTail</h5>\r\n<h6 class="hidden-print">Audio</h6>', 0),
(10, 'en', '<h6 class="hidden-print">Video</h6>', 0),
(10, 'fr', '<h6 class="hidden-print">Vidéo</h6>', 0),
(11, 'en', '<h6 class="hidden-print">Download</h6>', 0),
(11, 'fr', '<h6 class="hidden-print">Téléchargement</h6>', 0),
(12, 'en', '<h6>File</h6>', 0),
(12, 'fr', '<h6>Fichier</h6>', 0),
(13, 'en', '<h6>Insertion</h6>', 0),
(13, 'fr', '<h6>Insertion</h6>', 0),
(14, 'en', '<h6>PHP</h6>\r\n<code>&lt;p&gt;&lt;i&gt;&lt;?php setlocale(LC_TIME, \'en_US.UTF-8\'); \$fmt=strtoupper(substr(PHP_OS, 0, 3)) == \'WIN\' ? \'%B %#d, %Y\' : \'%B %e, %Y\'; echo strftime(\$fmt); ?&gt;&lt;/i&gt;&lt;/p&gt;</code>\r\n<p><i><?php setlocale(LC_TIME, \'en_US.UTF-8\'); \$fmt=strtoupper(substr(PHP_OS, 0, 3)) == \'WIN\' ? \'%B %#d, %Y\' : \'%B %e, %Y\'; echo strftime(\$fmt); ?></i></p>', 1),
(14, 'fr', '<h6>PHP</h6>\r\n<code>&lt;p&gt;&lt;i&gt;&lt;?php setlocale(LC_TIME, \'fr_FR.UTF-8\'); \$fmt=strtoupper(substr(PHP_OS, 0, 3)) == \'WIN\' ? \'%#d %B %Y\' : \'%e %B %Y\'; echo strftime(\$fmt); ?&gt;&lt;/i&gt;&lt;/p&gt;</code>\r\n<p><i><?php setlocale(LC_TIME, \'fr_FR.UTF-8\'); \$fmt=strtoupper(substr(PHP_OS, 0, 3)) == \'WIN\' ? \'%#d %B %Y\' : \'%e %B %Y\'; echo strftime(\$fmt); ?></i></p>', 1),
(15, 'en', '<p class="hidden-print"><i>Click on an image to display a full page slide show</i>&nbsp;<span class="glyphicon glyphicon-camera" aria-hidden="true"></span></p>', 0),
(15, 'fr', '<p class="hidden-print"><i>Cliquez sur une image pour afficher un diaporama pleine page</i>&nbsp;<span class="glyphicon glyphicon-camera" aria-hidden="true"></span></p>', 0),
(16, 'en', '<h4>What is a QRmii?</h4>\r\n<p><a href="http://www.qrmii.com/"><img src="/files/images/qrmii.png" alt="" title="QRmii - 1 URL 1 QR" /></a></p>\r\n<p>A <b>QRmii</b> is QR code which contains a short URL which is automatically redirected to a complete URL.\r\nScanning a QRmii with a smartphone directly displays the page of the original URL.</p>', 0),
(16, 'fr', '<h4>Qu\'est-ce qu\'un QRmii&nbsp;?</h4>\r\n<p><a href="http://www.qrmii.com/"><img src="/files/images/qrmii.png" alt="" title="QRmii - 1 URL 1 QR" /></a></p>\r\n<p>Un <b>QRmii</b> est un code QR qui contient une URL courte qui est automatiquement redirigée vers une URL complète.\r\nFlasher un QRmii avec un smartphone affiche directement la page de l\'URL d\'origine.</p>', 0),
(17, 'en', '<p class="hidden-print"><i>Click on an image to start playing the video on</i>&nbsp;<img src="/images/youtube.png" alt="" title="YouTube" /></p>', 0),
(17, 'fr', '<p class="text-small"><i>Cliquez sur une image pour démarrer la lecture de la vidéo sur</i>&nbsp;<img src="/images/youtube.png" alt="" title="YouTube" /></p>', 0),
(18, 'en', '<p>Download a QRmii by program in just a few lines of code.\r\nCreate a dynamic link between your services or your products and your public.\r\nA QRmii is simple, fast, reliable and fun.\r\nThe applications are infinite!</p>\r\n<p class="text-center"><a href="http://qrmii.com/a944d525"><img src="/files/images/qr50.png" width="50" height="50" alt="" title="http://qrmii.com/a944d525" /></a> Scan me!</p>', 0),
(18, 'fr', '<p>Téléchargez un QRmii par programme en quelques lignes de code.\r\nCréez un lien dynamique entre vos services ou vos produits et votre public.\r\nUn QRmii est simple, rapide, fiable et fun.\r\nLes applications sont infinies&nbsp;!</p>\r\n<p class="text-center"><a href="http://qrmii.com/a944d525"><img src="/files/images/qr50.png" width="50" height="50" alt="" title="http://qrmii.com/a944d525" /></a> Flashez-moi&nbsp;!</p>', 0),
(19, 'en', '<h1><a href="http://www.izend.org"><img src="/logos/izend.png" alt="" title="izend.org" /></a></h1>\r\n<h4>What is a QRmii?</h4>\r\n<p><a href="http://www.qrmii.com/"><img src="/files/images/qrmii.png" alt="" title="qrmii - 1 URL 1 QR" /></a></p>\r\n<p>A QRmii is QR code which contains a short URL which is automatically redirected to a complete URL.\r\nScanning a QRmii with a smartphone directly displays the page of the original URL.</p>\r\n<p><a href="http://www.qrmii.com">Visit the website!</a></p>\r\n<p class="text-center"><a href="http://qrmii.com/a944d525"><img src="/files/images/qr50.png" width="50" height="50" alt="" title="http://qrmii.com/a944d525" /></a> Scan me!</p>', 0),
(19, 'fr', '<h1><a href="http://www.izend.org"><img src="/logos/izend.png" alt="" title="izend.org" /></a></h1>\r\n<h4>Qu\'est-ce qu\'un QRmii ?</h4>\r\n<p><a href="http://www.qrmii.com/"><img src="/files/images/qrmii.png" alt="" title="qrmii - 1 URL 1 QR" /></a></p>\r\n<p>Un QRmii est un code QR qui contient une URL courte qui est automatiquement redirigée vers une URL complète.\r\nFlasher un QRmii avec un smartphone affiche directement la page de l\'URL d\'origine.</p>\r\n<p><a href="http://www.qrmii.com">Visitez le site web !</a></p>\r\n<p class="text-center"><a href="http://qrmii.com/a944d525"><img src="/files/images/qr50.png" width="50" height="50" alt="" title="http://qrmii.com/a944d525" /></a> Flashez-moi&nbsp;!</p>', 0),
(20, 'en', '<p>A QRmii is QR code which contains a short URL which is automatically redirected to a complete URL.\r\nScanning a QRmii with a smartphone directly displays the page of the original URL.</p>\r\n<p>Visit the website: <a href="http://www.qrmii.com">http://www.qrmii.com</a></p>', 0),
(20, 'fr', '<p>Un QRmii est un code QR qui contient une URL courte qui est automatiquement redirigée vers une URL complète.\r\nFlasher un QRmii avec un smartphone affiche directement la page de l\'URL d\'origine.</p>\r\n<p>Visitez le site web : <a href="">http://www.qrmii.com</a></p>', 0);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}content_text_content_id_seq', (SELECT MAX("content_id") FROM "${db_prefix}content_text"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}content_infile" ("content_id", "locale", "path") VALUES
(1, 'en', 'views/en/social.phtml'),
(1, 'fr', 'views/fr/social.phtml'),
(2, 'en', 'views/en/link.phtml'),
(2, 'fr', 'views/fr/link.phtml'),
(3, 'en', 'files/sysinfo.php'),
(3, 'fr', 'files/sysinfo.php'),
(4, 'en', 'files/slideshow.phtml'),
(4, 'fr', 'files/slideshow.phtml'),
(5, 'en', 'files/en/tubelist.phtml'),
(5, 'fr', 'files/fr/tubelist.phtml');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}content_infile_content_id_seq', (SELECT MAX("content_id") FROM "${db_prefix}content_infile"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}content_download" ("content_id", "locale", "name", "path") VALUES
(1, 'en', 'sysinfo.php', 'files/sysinfo.php'),
(1, 'fr', 'sysinfo.php', 'files/sysinfo.php');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}content_download_content_id_seq', (SELECT MAX("content_id") FROM "${db_prefix}content_download"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}content_file" ("content_id", "locale", "path", "start", "end", "format", "lineno") VALUES
(1, 'en', 'files/sysinfo.php', 0, 0, 'html5', 1),
(1, 'fr', 'files/sysinfo.php', 0, 0, 'html5', 1);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}content_file_content_id_seq', (SELECT MAX("content_id") FROM "${db_prefix}content_file"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}content_youtube" ("content_id", "locale", "id", "width", "height", "miniature", "title", "autoplay", "controls", "fs", "theme", "rel") VALUES
(1, 'en', 'aqz-KE-bpKQ', 640, 360, NULL, NULL, 0, 1, 0, 'light', 0),
(1, 'fr', 'aqz-KE-bpKQ', 640, 360, NULL, NULL, 0, 1, 0, 'light', 0);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}content_youtube_content_id_seq', (SELECT MAX("content_id") FROM "${db_prefix}content_youtube"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}content_longtail" ("content_id", "locale", "file", "image", "width", "height", "icons", "skin", "controlbar", "duration", "autostart", "repeat") VALUES
(1, 'en', '/files/sounds/smoke.mp3 /files/sounds/smoke.ogg /files/sounds/smoke.m4a', NULL, 200, 24, 0, '/longtail/simple.zip', 'bottom', 0, 0, 0),
(1, 'fr', '/files/sounds/smoke.mp3 /files/sounds/smoke.ogg /files/sounds/smoke.m4a', NULL, 200, 24, 0, '/longtail/simple.zip', 'bottom', 0, 0, 0),
(2, 'en', '/files/videos/imagin-raytracer.flv', '/files/videos/imagin-raytracer.jpg', 320, 240, 0, '/longtail/modieus.zip', 'over', 0, 0, 0),
(2, 'fr', '/files/videos/imagin-raytracer.flv', '/files/videos/imagin-raytracer.jpg', 320, 240, 0, '/longtail/modieus.zip', 'over', 0, 0, 0);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}content_longtail_content_id_seq', (SELECT MAX("content_id") FROM "${db_prefix}content_longtail"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}tag" ("tag_id", "locale", "name") VALUES
(32, 'en', 'Colorbox'),
(31, 'en', 'Cycle'),
(11, 'en', 'HTML5'),
(9, 'en', 'LongTail'),
(3, 'en', 'PHP'),
(34, 'en', 'QR'),
(33, 'en', 'QRmii'),
(13, 'en', 'UI'),
(49, 'en', 'URL'),
(10, 'en', 'YouTube'),
(7, 'en', 'audio'),
(14, 'en', 'calendar'),
(45, 'en', 'cat'),
(1, 'en', 'content'),
(46, 'en', 'dog'),
(6, 'en', 'download'),
(5, 'en', 'file'),
(29, 'en', 'gallery'),
(30, 'en', 'image'),
(4, 'en', 'insertion'),
(12, 'en', 'jQuery'),
(50, 'en', 'redirection'),
(2, 'en', 'text'),
(8, 'en', 'video'),
(40, 'fr', 'Colorbox'),
(39, 'fr', 'Cycle'),
(25, 'fr', 'HTML5'),
(43, 'fr', 'Imagin'),
(23, 'fr', 'LongTail'),
(17, 'fr', 'PHP'),
(42, 'fr', 'QR'),
(41, 'fr', 'QRmii'),
(44, 'fr', 'Raytracer'),
(27, 'fr', 'UI'),
(51, 'fr', 'URL'),
(24, 'fr', 'YouTube'),
(21, 'fr', 'audio'),
(28, 'fr', 'calendrier'),
(47, 'fr', 'chat'),
(48, 'fr', 'chien'),
(15, 'fr', 'contenu'),
(19, 'fr', 'fichier'),
(37, 'fr', 'galerie'),
(38, 'fr', 'image'),
(18, 'fr', 'insertion'),
(26, 'fr', 'jQuery'),
(52, 'fr', 'redirection'),
(16, 'fr', 'texte'),
(20, 'fr', 'téléchargement'),
(22, 'fr', 'vidéo');
_SEP_;
		$db_conn->exec($sql);
		$sql= <<<_SEP_
SELECT setval('${db_prefix}tag_tag_id_seq', (SELECT MAX("tag_id") FROM "${db_prefix}tag"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}tag_index" ("tag_id", "node_id") VALUES
(1, 2),
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(8, 2),
(8, 3),
(9, 2),
(10, 2),
(10, 3),
(11, 2),
(12, 2),
(12, 3),
(13, 2),
(14, 2),
(15, 2),
(16, 2),
(17, 2),
(18, 2),
(19, 2),
(20, 2),
(21, 2),
(22, 2),
(22, 3),
(23, 2),
(24, 2),
(24, 3),
(25, 2),
(26, 2),
(26, 3),
(27, 2),
(28, 2),
(29, 3),
(30, 3),
(31, 3),
(32, 3),
(33, 3),
(33, 4),
(34, 3),
(34, 4),
(37, 3),
(38, 3),
(39, 3),
(40, 3),
(41, 3),
(41, 4),
(42, 3),
(42, 4),
(43, 3),
(44, 3),
(45, 3),
(46, 3),
(47, 3),
(48, 3),
(49, 4),
(50, 4),
(51, 4),
(52, 4);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}thread" ("thread_id", "user_id", "thread_type", "created", "modified", "number", "visits", "nosearch", "nocloud", "nocomment", "nomorecomment", "novote", "nomorevote", "ilike", "tweet", "plusone", "linkedin", "pinit") VALUES
(1, 1, 'folder', NOW(), NOW(), 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 0),
(2, 1, 'story', NOW(), NOW(), 2, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1),
(3, 1, 'newsletter', NOW(), NOW(), 3, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 0);
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
SELECT setval('${db_prefix}thread_thread_id_seq', (SELECT MAX("thread_id") FROM "${db_prefix}thread"));
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}thread_locale" ("thread_id", "locale", "name", "title") VALUES
(1, 'en', 'blog', 'Blog'),
(1, 'fr', 'blog', 'Blog'),
(2, 'en', 'test', 'Test'),
(2, 'fr', 'test', 'Test'),
(3, 'en', 'newsletter', 'Newsletter'),
(3, 'fr', 'infolettre', 'Infolettre');
_SEP_;
		$db_conn->exec($sql);

		$sql= <<<_SEP_
INSERT INTO "${db_prefix}thread_node" ("thread_id", "node_id", "number") VALUES
(1, 1, 1),
(2, 2, 1),
(2, 3, 2),
(3, 4, 1);
_SEP_;
		$db_conn->exec($sql);
	}
	catch (PDOException $e) {
		throw($e);
	}

	$db_conn=null;

	return true;
}
