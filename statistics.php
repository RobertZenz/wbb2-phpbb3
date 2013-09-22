<?php
error_reporting(E_ALL);
ini_set("display_errors", TRUE);

require_once __DIR__ . "/DatabaseFactory.php";
require_once __DIR__ . "/Document.php";

$wbb = DatabaseFactory::getWbbConnection();
$phpbb = DatabaseFactory::getPhpbbConnection();
Document::getInstance()->setTitle("Statistics");

Document::getInstance()->addItem("Repairing forum statistics");
// I'd like to take the opportunity to say that I'm sorry,
// but this is the fastest and easiest way to do this.
// Easiest as in "I don't need to figure out how to do this JOIN".
$phpbb->exec("
	UPDATE
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "forums AS f
	SET
		f.forum_posts = (
			SELECT
				COUNT(*)
			FROM
				" . DatabaseFactory::PHPBB_TABLE_PREFIX . "posts
			WHERE
				forum_id = f.forum_id
		),
		f.forum_topics = (
			SELECT
				COUNT(*)
			FROM
				" . DatabaseFactory::PHPBB_TABLE_PREFIX . "topics
			WHERE
				forum_id = f.forum_id
		),
		f.forum_last_post_id = (
			SELECT
				post_id
			FROM
				" . DatabaseFactory::PHPBB_TABLE_PREFIX . "posts
			WHERE
				forum_id = f.forum_id
			ORDER BY
				post_time DESC
			LIMIT 1
		),
		f.forum_last_poster_id = (
			SELECT
				poster_id
			FROM
				" . DatabaseFactory::PHPBB_TABLE_PREFIX . "posts
			WHERE
				forum_id = f.forum_id
			ORDER BY
				post_time DESC
			LIMIT 1
		),
		f.forum_last_post_subject = (
			SELECT
				post_subject
			FROM
				" . DatabaseFactory::PHPBB_TABLE_PREFIX . "posts
			WHERE
				forum_id = f.forum_id
			ORDER BY
				post_time DESC
			LIMIT 1
		),
		f.forum_last_post_time = (
			SELECT
				post_time
			FROM
				" . DatabaseFactory::PHPBB_TABLE_PREFIX . "posts
			WHERE
				forum_id = f.forum_id
			ORDER BY
				post_time DESC
			LIMIT 1
		),
		f.forum_last_poster_name = (
			SELECT
				post_username
			FROM
				" . DatabaseFactory::PHPBB_TABLE_PREFIX . "posts
			WHERE
				forum_id = f.forum_id
			ORDER BY
				post_time DESC
			LIMIT 1
		);
	
	UPDATE
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "forums AS f
	SET
		f.forum_topics_real = f.forum_topics;
");

Document::getInstance()->write();

?>
