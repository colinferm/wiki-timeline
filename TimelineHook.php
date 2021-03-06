<?php
if (!defined('MEDIAWIKI')) die();

class TimelineHook {
	public static function init(Parser $parser) {
		$parser->setHook('timeline', [self::class, 'timelineRender']);
	}

	public function timelineRender($input, array $args, Parser $parser, PPFrame $frame) {
		$planetParam = (array_key_exists("planets", $args)) ? $args['planets'] : "";
		$catParam = (array_key_exists("categories", $args)) ? $args['categories'] : "";
		$showKey = (array_key_exists("key", $args)) ? $args['key'] : false;
		$showAsList = false;
		$height = "400px";
		if (array_key_exists('list', $args) && strlen($args['list'])) {
			$showAsList = (bool) $args['list'];
		}
		if (array_key_exists('height', $args) && strlen($args['height'])) {
			$height = $args['height'];
		}
		if (strlen($planetParam)) $planetsList = "'".implode("','", explode(',', $planetParam))."'";
		if (strlen($catParam)) $catList = "'".implode("','", explode(',', $catParam))."'";

		if ($showKey || $showAsList) {
			wfDebugLog('extensions', __METHOD__." List?: ".$list);
			$db = wfGetDB(DB_SLAVE);
			$results = TimelineLib::ursFetchTimelineData($db, $catList, $planetsList);
			$html = '<h1><span class="mw-headline" id="Age_of_Colonization">Age of Colonization</span></h1>';
			$curYear = 0;

			global $wgParser, $wgUser, $wgTitle, $wgEnableParserCache;
			$localParser = clone $wgParser;
			$popts = new ParserOptions();
			$popts->setTidy( true );
			$popts->enableLimitReport(false);
			while ($row = $results->fetchRow()) {
				if ($row['start_year'] != $curYear) {
					if ($row['start_year'] == "2210") {
						$html .= '<h1><span class="mw-headline" id="Age_of_War">Age of War</span></h1>';
					} else if ($row['start_year'] == "2361") {
						$html .= '<h1><span class="mw-headline" id="Age_of_Betrayal">Age of Betrayal</span></h1>';
					}
					$html .= '</ul><h3><span class="mw-headline" id="'.$row['start_year'].'">'.$row['start_year'].'</span></h3><ul>';
					$curYear = $row['start_year'];
				}
				$notes = $localParser->parse( $row['notes'], $wgTitle, $popts );
				$hasMatched = preg_match("/<p>(.*)<\/p>/s", trim($notes->mText), $matches);

				$html .= "<li> ".$matches[1]."</li>";

			}
		}

		if ($showAsList) {
			$html .= '</ul>';
			return $html;

		} else {
			$planets = "";
			if (strlen(trim($planetParam))) {
				$planets = implode('|',explode(',', trim($planetParam)));
			}
			$categories = "";
			if (strlen(trim($catParam))) {
				$categories = implode('|',explode(',', trim($catParam)));
			}

			$output = '<div id="dynamic_timeline" class="timeline-default" style="height: '.$height.'; margin-top: 20px; margin-bottom: 50px;" data-planets="'.$planets.'" data-categories="'.$categories.'"></div>';
			wfDebugLog('extensions', __METHOD__." Show Key?: ".$showKey);
			if (strlen($showKey) && $showKey == "true") {
				$output .= '<div id="timeline_view_key"><img src="/wiki/extensions/Timeline/timeline_js/images/icon-ship.png"/>Space Vehicles<br/>'.
					'<img src="/wiki/extensions/Timeline/timeline_js/images/icon-un.png"/>UUHA<br/>'.
					'<img src="/wiki/extensions/Timeline/timeline_js/images/icon-military.png"/>Military<br/>'.
					'<img src="/wiki/extensions/Timeline/timeline_js/images/icon_baseball.png"/>Baseball</div>'.
					'<div id="timeline_view_list">View as list</div>';
			}
			if ($showKey || $showAsList) {
				$output .= '<noscript id="list_timeline">'.$html.'</noscript>';
			}

			return $output;
		}

		return "No Timeline available";
	}
}

?>
