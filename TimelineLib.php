<?php
define( 'URSTL_VERSION', '1.1' );
class TimelineLib {
	public static function ursTimelineBuildCloud($selectedYear) {
		$db = wfGetDB(DB_SLAVE);
		$yearQuery = "SELECT SUBSTRING(start_year, 1, 4) AS start_year, count(*) AS events FROM ".$db->tableName('timeline_events')." GROUP BY start_year ORDER BY start_year ASC";
			$results = $db->query($yearQuery, __METHOD__, true);
			$yearCloud = "<div id=\"timeline-cloud\">Existing Years ";
			while ($row = $results->fetchRow()) {
					$year = $row['start_year'];
					$events = $row['events'];
			/*
					if ($events >= 10) {
							$sizePercent = "160%";
					} else if ($events >= 5) {
							$sizePercent = "135%";
					}
			*/
			$sizePercent = ($events * .35);
			if ($sizePercent < 1) $sizePercent = 1;

					if ($selectedYear == $year) {
							$yearCloud .= '| &nbsp;<b style="font-size: '.$sizePercent.'em;">'.$year.'</b>&nbsp;';
					} else {
							$yearCloud .= '| &nbsp;<a href="?selectedYear='.$year.'" style="font-size: '.$sizePercent.'em;">'.$year.'</a>&nbsp;';
					}

			}
		$yearCloud .= "</div>";
			$db->freeResult($results);
		return $yearCloud;
	}

	public static function ursUpdateTimelineEvent($args) {
		$id = $args['id'];
		$startYear = $args['start_year'];
		$endYear = $args['end_year'];
		$title = $args['title'];
		$caption = $args['caption'];
		$notes = $args['notes'];
		$imageURL = $args['image_url'];
		$iconURL = $args['icon_url'];
		$textColor = $args['text_color'];
		$showFilterOnly = $args['show_filter_only'];
		$cats = trim($args['categories']);
		$catList = split(",", $cats);
		$plnts = trim($args['planets']);
		$planetList = split(",", $plnts);

		$age = 1;
		if ($startYear >= 2210 && $startYear < 2361) {
			$age = 2;
		} else if ($startYear >= 2361) {
			$age = 3;
		}


		wfDebugLog('extensions', __METHOD__." ID Passed: ".$id);
		wfDebugLog('extensions', __METHOD__." Cats Passed: ".$cats);

		$db = wfGetDB(DB_MASTER);
		$result = false;
		if ($id != 0) {
			$update = "UPDATE ".$db->tableName('timeline_events')." SET start_year = ".$db->addQuotes($startYear).", end_year = ".$db->addQuotes($endYear).
			", title = ".$db->addQuotes($title).", caption = ".$db->addQuotes($caption).", notes = ".$db->addQuotes($notes).
			", image_url = ".$db->addQuotes($imageURL).", icon_url = ".$db->addQuotes($iconURL).", text_color = ".$db->addQuotes($textColor).
			", show_filter_only = ".$db->addQuotes($showFilterOnly).
			" WHERE id = ".$db->addQuotes($id);
			wfDebugLog('extensions', __METHOD__." Update Query: ".$update);
			$result = $db->query($update, __METHOD__, true);
			wfDebugLog('extensions', __METHOD__." Result: ".$result);

		} else {

			$selectOrder = "SELECT count(*) AS order_count FROM ".$db->tableName('timeline_events')." WHERE start_year = ".$db->addQuotes($startYear);
			$resultCount = $db->query($selectOrder);
			$countRow = $resultCount->fetchRow();
			$order = $countRow['order_count'] + 1;

			$insert = "INSERT INTO ".$db->tableName('timeline_events')." VALUES(0, ".$db->addQuotes($startYear).",".$db->addQuotes($endYear).
			",".$age.",".$order.",".$db->addQuotes($title).",".$db->addQuotes($caption).",".$db->addQuotes($notes).",".$db->addQuotes($iconURL).
			",".$db->addQuotes($imageURL).",".$db->addQuotes($textColor).",".$db->addQuotes($showFilterOnly).")";
			wfDebugLog('extensions', __METHOD__." Insert Query: ".$insert);

			$result = $db->query($insert, __METHOD__, true);
			wfDebugLog('extensions', __METHOD__." Result: ".$result);

			if ($result) {
				$selectId = "SELECT id FROM ".$db->tableName('timeline_events')." WHERE start_year = ".$db->addQuotes($startYear)." AND end_year = ".$db->addQuotes($endYear).
				" AND title = ".$db->addQuotes($title)." AND caption = ".$db->addQuotes($caption)." AND notes = ".$db->addQuotes($notes);
				$resultID = $db->query($selectId);
				$idRow = $resultID->fetchRow();
				$id = $idRow['id'];
				$args['id']  = $id;

				global $wgParser, $wgUser, $wgTitle, $wgEnableParserCache;
				$popts = new ParserOptions();
				$popts->setTidy( true );
				$popts->enableLimitReport(false);
				$notesParse = $wgParser->parse( $notes, $wgTitle, $popts );
				$args['notes'] = $notesParse->mText;
			}
		}
		$deleteSQL = "DELETE FROM ".$db->tableName('timeline_event_map')." WHERE event_id = ".$db->addQuotes($id);
		$db->query($deleteSQL);

		if (strlen($plnts) && count($planetList)) {
			$args['planets'] = $planetList;
			for($i = 0; $i < count($planetList); $i++) {
				$planetId = 0;
				$planet = $planetList[$i];
				$selectPlanetSQL = "SELECT id FROM ".$db->tableName('timeline_event_categories')." WHERE category = ".$db->addQuotes($planet)." AND is_planet = 1";
				$results = $db->query($selectPlanetSQL);
				if ($results->numRows() > 0) {
					$row = $results->fetchRow();
					$planetId = $row['id'];
					$db->freeResult($results);
				} else {
					$insertPlanetSQL = "INSERT INTO ".$db->tableName('timeline_event_categories')." VALUES(0, 1, ".$db->addQuotes($planet).")";
					$db->query($insertPlanetSQL);

					$results = $db->query($selectPlanetSQL);
								$row = $results->fetchRow();
					$planetId = $row['id'];
					$db->freeResult($results);
				}
				$insertMapSQL = "INSERT INTO ".$db->tableName('timeline_event_map')." VALUES(".$db->addQuotes($id).",".$db->addQuotes($planetId).")";
				$db->query($insertMapSQL);
			}
		} else {
			$args['planets'] = array();
		}

		if (strlen($cats) && count($catList)) {
			$args['categories'] = $catList;
			for($i = 0; $i < count($catList); $i++) {
				$catId = 0;
				$category = $catList[$i];
				wfDebugLog('extensions', __METHOD__." Saving Category: ".$category);
				$selectCatSQL = "SELECT id FROM ".$db->tableName('timeline_event_categories')." WHERE category = ".$db->addQuotes($category)." AND is_planet = 0";
				$results = $db->query($selectCatSQL);
				if ($results->numRows() > 0) {
					$row = $results->fetchRow();
					$catId = $row['id'];
					$db->freeResult($results);
				} else {
					$insertCatSQL = "INSERT INTO ".$db->tableName('timeline_event_categories')." VALUES(0, 0, ".$db->addQuotes($category).")";
					$db->query($insertCatSQL);

					$results = $db->query($selectCatSQL);
								$row = $results->fetchRow();
					$catId = $row['id'];
					$db->freeResult($results);
				}
				$insertMapSQL = "INSERT INTO ".$db->tableName('timeline_event_map')." VALUES(".$db->addQuotes($id).",".$db->addQuotes($catId).")";
				$db->query($insertMapSQL);
			}
		} else {
			$args['categories'] = array();
		}

		header('Content-type: application/json');
		return json_encode(array('status' => $result, 'event' => $args));
	}

	public static function ursDeleteTimelineEvent($args) {
		$id = $args['id'];

		$db = wfGetDB(DB_MASTER);
		wfDebugLog('extensions', __METHOD__." Deleting Event ID: ".$id);
		$deleteSQL = "DELETE FROM ".$db->tableName('timeline_events')." WHERE id = ".$db->addQuotes($id);
		$result= $db->query($deleteSQL);
		wfDebugLog('extensions', __METHOD__." Deleting Result: ".$result);

		header('Content-type: application/json');
		return json_encode(array('status' => $result));
	}

	public static function ursFetchTimelineData(&$db, $catList, $planetsList) {
		wfDebugLog('extensions', __METHOD__." Planets Passed: ".$planets);
		wfDebugLog('extensions', __METHOD__." Categories Passed: ".$cats);

		$selectSQL = "SELECT DISTINCT e.start_year, e.end_year, e.title, e.caption, e.notes, e.icon_url, e.image_url, e.text_color, e.show_filter_only ".
			"FROM ".$db->tableName('timeline_events')." e ";

		if (strlen($catList)) {
			$selectSQL .= "WHERE e.id IN (SELECT m.event_id ".
					"FROM ".$db->tableName('timeline_event_map')." m, ".$db->tableName('timeline_event_categories')." c ".
					"WHERE m.category_id = c.id ".
					"AND c.category IN (".$catList.") ".
					"AND c.is_planet = 0)";
		}
		if (strlen($planetsList)) {
			if (strlen($catList)) {
				$selectSQL .= " AND ";
			} else {
				$selectSQL .= "WHERE ";
			}
				$selectSQL .= "e.id IN (SELECT m.event_id ".
						"FROM ".$db->tableName('timeline_event_map')." m, ".$db->tableName('timeline_event_categories')." c ".
						"WHERE m.category_id = c.id ".
						"AND c.category IN (".$planetsList.") ".
						"AND c.is_planet = 1)";
		}
		if (strlen($planetsList) || strlen($catList)) {
			$selectSQL .= " AND (e.show_filter_only = 0 OR e.show_filter_only = 1)";
		} else {
			$selectSQL .= " WHERE e.show_filter_only = 0";

		}

		$selectSQL .= " ORDER BY e.start_year ASC";

		wfDebugLog('extensions', __METHOD__." Query Running: ".$selectSQL);
		$results = $db->query($selectSQL, __METHOD__, true);

		return $results;
	}

	public static function ursTimelineHeaderScripts( OutputPage &$out, Skin &$skin ) {
        //wfDebugLog('extensions', 'Header scripts called.');
		$out->addHeadItem( 'urs_tag_style', '<link rel="stylesheet" type="text/css" href="/wiki/extensions/Timeline/jquery.tagsinput.css" />' );
		return true;
	}

	public static function ursTimelineBottomScripts( $skin, &$text ) {
        //wfDebugLog('extensions', $text);
		$text .= "\n".'<script>'.
			'Timeline_ajax_url="//'.$_SERVER['SERVER_NAME'].'/wiki/extensions/Timeline/timeline_ajax/simile-ajax-api.js";'.
			'Timeline_urlPrefix="//'.$_SERVER['SERVER_NAME'].'/wiki/extensions/Timeline/timeline_js/";'.
			'SimileAjax_urlPrefix="//'.$_SERVER['SERVER_NAME'].'/wiki/extensions/Timeline/timeline_ajax/";'.
			'Timeline_parameters="bundle=true";'.
			'</script>';
		$text .= "\n<script type='text/javascript' src='//".$_SERVER['SERVER_NAME']."/wiki/extensions/Timeline/timeline_js/timeline-api.js?ver=".URSTL_VERSION."'></script>";
		$text .= "\n<script type='text/javascript' src='//".$_SERVER['SERVER_NAME']."/wiki/extensions/Timeline/Timeline.min.js?ver=".URSTL_VERSION."'></script>";
		$text .= "\n<script type='text/javascript' src='//".$_SERVER['SERVER_NAME']."/wiki/extensions/Timeline/jquery.tagsinput.js?ver=".URSTL_VERSION."'></script>";
		return true;
	}
}
?>
