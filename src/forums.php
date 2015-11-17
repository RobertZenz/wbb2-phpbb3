<?php
error_reporting(E_ALL);
ini_set("display_errors", TRUE);

require_once __DIR__ . "/DatabaseFactory.php";
require_once __DIR__ . "/Document.php";

function getFlags($type) {
	switch ($type) {
		case 0:
			return 48;
			break;

		case 1:
			return 32;
			break;

		default:
			return 32;
			break;
	}
}

/**
 *
 * @param int $parentId
 * @param PDO $wbb
 * @param PDO $phpbb 
 * @param int $sortId 
 */
function insertForum($parentId, $wbb, $phpbb, $sortId) {
	$get = $wbb->prepare("
		SELECT
			boardid
		FROM
			" . DatabaseFactory::WBB_TABLE_REPFIX . "boards
		WHERE
			parentid = :id
		ORDER BY
			boardorder ASC;
	");

	$forumGet = $wbb->prepare("
		SELECT
			boardid,
			parentid,
			title,
			description,
			isboard
		FROM
			" . DatabaseFactory::WBB_TABLE_REPFIX . "boards
		WHERE
			boardID = :id;
	");

	$insert = $phpbb->prepare("
		INSERT INTO
			" . DatabaseFactory::PHPBB_TABLE_PREFIX . "forums
		SET
			forum_id = :id,
			parent_id = :parentId,
			left_id = :lid,
			forum_name = :name,
			forum_desc = :description,
			forum_type = :type,
			forum_flags = :flags;
	");

	$rightUpdate = $phpbb->prepare("
		UPDATE
			" . DatabaseFactory::PHPBB_TABLE_PREFIX . "forums
		SET
			right_id = :rightId
		WHERE
			forum_id = :id;
	");
	
	$get->bindParam(":id", $parentId);
	$get->execute();
	
	while ($row = $get->fetch(PDO::FETCH_ASSOC)) {
		$forumGet->bindParam(":id", $row["boardid"]);
		$forumGet->execute();
		$forum = $forumGet->fetch(PDO::FETCH_ASSOC);
		$forumGet->closeCursor();

		Document::getInstance()->addItem($row["boardid"] . " - " . $forum["title"]);
		
		$insert->bindParam(":id", $forum["boardid"]);
		$insert->bindParam(":parentId", $forum["parentid"]);
		$insert->bindParam(":lid", $sortId);
		$insert->bindParam(":name", $forum["title"]);
		$insert->bindParam(":description", $forum["description"]);
		$insert->bindParam(":type", typeConvert($forum["isboard"]));
		$insert->bindParam(":flags", getFlags($forum["isboard"]));
		$insert->execute();
		$insert->closeCursor();
		
		$sortId = insertForum($row["boardid"], $wbb, $phpbb, $sortId + 1);
		
		$rightUpdate->bindParam(":rightId", $sortId);
		$rightUpdate->bindParam(":id", $row["boardid"]);
		$rightUpdate->execute();
		$rightUpdate->closeCursor();
		
		$sortId++;
	}

	$get->closeCursor();
	
	return $sortId;
}

function typeConvert($type) {
	switch ($type) {
		case 0:
			return 1;
			break;

		case 1:
			return 0;
			break;

		default:
			return 0;
			break;
	}
}

$wbb = DatabaseFactory::getWbbConnection();
$phpbb = DatabaseFactory::getPhpbbConnection();
Document::getInstance()->setTitle("Forums");

Document::getInstance()->addItem("Truncating table \"forums\"");
$phpbb->query("
	TRUNCATE TABLE
		" . DatabaseFactory::PHPBB_TABLE_PREFIX . "forums;
");

insertForum(0, $wbb, $phpbb, 1);

Document::getInstance()->write();

?>
