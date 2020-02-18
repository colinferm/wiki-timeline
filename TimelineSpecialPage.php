<?php
if (!defined('MEDIAWIKI')) die();
$tlIP = dirname( __FILE__ );
class TimelineSpecialPage extends SpecialPage {
	function __construct() {
		parent::__construct( 'Timeline', 'editinterface' );
	}

	function execute($query) {
		global $wgUser;
		if (!$this->userCanExecute($wgUser)) {
			$this->displayRestrictionError('Timeline', 'editinterface', true, 'doTimeline');
			return;
		}

		$this->setHeaders();
		$this->doTimeline();
	}

	private function doTimeline() {
		global $wgOut, $wgRequest, $wgUser, $wgCanonicalNamespaceNames, $wgContLang, $wgParser;
	
		$selectedYear = $wgRequest->getVal('selectedYear');
	
		$wgOut->setPageTitle("Story Timeline");
	
		$form = <<<END
			<style type="text/css">
				#shade, #modal { display: none; }
				#shade { position: fixed; z-index: 100; top: 0; left: 0; width: 100%; height: 100%; }
				#modal { background: #ffffff; border-style: solid; border-color: #666666; border-width: 2px; padding: 10px; position: fixed; z-index: 101; top: 5%; left: 25%; width: 50%; }
				#modal td, #modal input { font-size: 0.7em; line-height: 1em; padding-top: 0.2em; padding-bottom: 0.2em; }
				#modal input { max-height: 3em; }
				#shade { background: silver; opacity: 0.5; filter: alpha(opacity=50); }
				#close { float: right; font-size: 0.5em; padding: 0.7em;  }
			</style>
			<div id="shade"></div>
			<div id="modal">
				<button id="close">Close</button>
				<input type="hidden" id="edit_id"/>
				<table>
				<tr>
				<td>Start Year:</td><td><input type="text" id="edit_start_year" size="12" maxlength="10"/></td>
				</tr>
				<tr>
				<td>End Year:</td><td><input type="text" id="edit_end_year" size="12" maxlength="10"/></td>
				</tr>
				<tr>
				<td>Title:</td><td><input type="text" id="edit_title" size="110" maxlength="255"/></td>
				</tr>
				<tr>
				<td>Caption:</td><td><input type="text" id="edit_caption" size="110" maxlength="255"/></td>
				</tr>
				<tr>
				<td valign="top">Notes:</td><td><textarea id="edit_notes" cols="80" rows="5"></textarea></td>
				</tr>
				<tr>
				<td>Categories:</td><td><input type="text" id="edit_categories" size="110" maxlength="255"/></td>
				</tr>
				<tr>
				<td>Planets:</td><td><input type="text" id="edit_planets" size="110" maxlength="255"/></td>
				</tr>
				<tr>
				<td>Image:</td><td><input type="text" id="edit_image" size="110" maxlength="255"/></td>
				</tr>
				<tr>
				<td>Filtered:</td><td><input type="checkbox" id="edit_private"/></td>
				</tr>
				<tr>
				<td>Icon:</td><td><select id="edit_icon">
							<option value="">None</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/icon_baseball.png">Baseball</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/icon-ship.png">Ship</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/icon-un.png">UUHA</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/icon-military.png">Military</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/dull-blue-circle.png">Light Blue</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/blue-circle.png">Blue</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/dark-blue-circle.png">Dark Blue</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/dull-red-circle.png">Light Red</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/red-circle.png">Red</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/dark-red-circle.png">Dark Red</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/dull-green-circle.png">Light Green</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/green-circle.png">Green</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/dark-green-circle.png">Dark Green</option>
							<option value="/wiki/extensions/Timeline/timeline_js/images/gray-circle.png">Gray</option>
						</select>
	<!-- <input type="text" id="edit_icon" size="110" maxlength="255"/></td> -->
				</tr>
				<tr>
				<td>Color:</td><td><input type="text" id="edit_text_color" size="7" maxlength="7"/></td>
				</tr>
				</table>
				<button id="edit_save">Save Event</button>
			</div>
			<p>
			Enter a year for events you would like to begin editing. Years that do not yet exist will be created after the first event is created in it.<br/>
			<form action="" method="post">
			<input type="text" size="4" maxlength="4" name="selectedYear" value="$selectedYear" tabindex="1"/>
			<input type="submit" value="Go"/>
			</form>			
END;
	
		$wgOut->addHTML($form);
	
		$db = wfGetDB(DB_SLAVE);
		if (strlen($selectedYear)) {
			$query = "SELECT id, start_year, end_year, age, order_index, title, caption, notes, icon_url, image_url, text_color, show_filter_only FROM ".$db->tableName('timeline_events')." WHERE start_year LIKE '".$selectedYear."%' ORDER BY order_index";
			$results = $db->query($query, __METHOD__, true);
	
			$wgOut->addHTML('<form action="" method="post">');
			$editForm = "<table class='basic-table' id='timeline-events'><tr><th colspan='3'>Timeline Events for ".$selectedYear."</th></tr>";
			//$editForm .= "<tr><td colspan='3' id='edit-timeline-0'><b>Add a new timeline event</b></td></tr><tr><td colspan='3'>&nbsp;</td></tr>";
			$editForm .= "<tr><td colspan='3' id='edit-timeline-0'><b>Add a new timeline event</b></td></tr>";
			$editData = array();
			if ($results->numRows()) {
				while ($row = $results->fetchRow()) {
					$editId = $row['id'];
					
					$selectCatsSQL = "SELECT c.id, c.is_planet, c.category FROM ".$db->tableName('timeline_event_categories')." c, ".$db->tableName('timeline_event_map')." m WHERE c.id = m.category_id AND m.event_id = ".$db->addQuotes($editId);
					$catResults = $db->query($selectCatsSQL, __METHOD__, true);
					$cats = array();
					$planets = array();
					if ($catResults->numRows()) {
						wfDebugLog('extensions', __METHOD__." Num rows: ".$catResults->numRows());
						while ($catRow = $catResults->fetchRow()) {
							if ($catRow['is_planet'] == 1) {
								$planets[] = $catRow['category'];
							} else {
								$cats[] = $catRow['category'];
							}
						}
						$db->freeResult($catResults);
					}
					$row['categories'] = $cats;
					$row['planets'] = $planets;
	
					$titleText = $row['title'];
					if (!strlen($row['title']) && strlen($row['caption'])) {
						$titleText = $row['caption'];
					} else if (!strlen($row['title']) && !strlen($row['caption'])) {
						$titleText = $row['notes'];
					}
					$editForm .= '<tr id="timeline-row-'.$row['id'].'">';
					$editForm .= '<td id="timeline-title-'.$row['id'].'">'.$titleText.'</td><td id="edit-timeline-'.$row['id'].'">Edit</td><td id="delete-timeline-'.$row['id'].'">Delete</td>';
					$editForm .= '</tr>';
					//$editForm .= '<tr id="timeline-spacer-'.$row['id'].'"><td colspan="3">&nbsp;</td></tr>';
	
					$editData[] = $row;
				}
			}
			$editForm .= "</table>";
			$db->freeResult($results);
			$wgOut->addWikiText($editForm);
			$wgOut->addHTML('</form>');
			$editJSON = '<script type="text/javascript">var ursSelectedYear = '.$selectedYear.'; var ursTimelineEvents = '.json_encode($editData).'</script>';
			//$editJSON .= '<script type="text/javascript" src="/wiki/extensions/Timeline/Timeline.js"></script>';
			$wgOut->addHTML($editJSON);
	
		} 
		$yearCloud = TimelineLib::ursTimelineBuildCloud($selectedYear);
		$wgOut->addHTML($yearCloud);
	}
}
?>
