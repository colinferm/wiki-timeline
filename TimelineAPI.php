<?php

$wgAPIModules['timeline'] = "TimelineAPI";

class TimelineAPI extends ApiBase {

	public function __construct( $main, $action ) {
		parent :: __construct( $main, $action );
	}
	
	public function execute() {
		$action_start = microtime(true);
		$params = $this->extractRequestParams();
		$planets = $params['tlplanets'];
		$cats = $params['tlcategories'];

		if (strlen($planets)) $planetsList = "'".implode("','", explode('|', $planets))."'";
		if (strlen($cats)) $catList = "'".implode("','", explode('|', $cats))."'";

		$allList = '';
		if (strlen($planetsList)) {
			$allList = $planetsList;
		}
		if (strlen($catList)) {
			if (strlen($allList)) {
				$allList .= ',';
			}
			$allList .= $catList;
		}

		$events = $this->checkCache($allList);

		if (!$events) {
			$db = wfGetDB(DB_SLAVE);
			$results = TimelineLib::ursFetchTimelineData($db, $catList, $planetsList);
			$events = array();

			global $wgParser, $wgUser, $wgTitle, $wgEnableParserCache;
        	        $popts = new ParserOptions();
        	        $popts->setTidy( true );
        	        $popts->enableLimitReport(false);
			while ($row = $results->fetchRow()) {
				$notes = $wgParser->parse( $row['notes'], $wgTitle, $popts );

				$title = $row['title'];
				if (!strlen($row['title']) && strlen($row['caption'])) {
					$title = $row['caption'];
				
				} else if (!strlen($row['title']) && !strlen($row['caption'])) {
					$title = $notes->mText;
					//$title = trim(preg_replace('/\"/', '\'', strip_tags($title)));
				}
				$title = trim(preg_replace('/\"/', '\'', strip_tags($title)));

				$e = array();
				$e['start'] = $row['start_year'];
				if (($row['start_year'] != $row['end_year']) && !(strlen($row['start_year']) > 4 && strlen($row['end_year']) == 4)) {
					$e['end'] = $row['end_year'];
					$e['durrationEvent'] = true;
				} else {
					//$e['durrationEvent'] = false;
				}
				$e['title'] = $title;
				if (strlen($row['caption'])) $e['caption'] = $row['caption'];
				$e['description'] = $notes->mText;
				if (strlen($row['icon_url'])) $e['icon'] = $row['icon_url'];
				if (strlen($row['image_url'])) $e['image'] = $row['image_url'];
				if (strlen($row['text_color'])) $e['textColor'] = $row['text_color'];

				$events[] = $e;
			
			}
			$this->saveCache($allList, $events);
		}

		$result = $this->getResult();
		$tags = array('events');

                $result->setIndexedTagName( $tags, 'event' );
		$result->addValue(null, 'events', $events);

		$action_end = microtime(true);
		$action_length = $action_end - $action_start;
		wfDebugLog('extensions', __METHOD__." Performance: action took ".$action_length."ms");
	}

	public function checkCache($params) {
		global $wgUseFileCache;
		if (!$wgUseFileCache) {
			wfDebugLog('extensions', __METHOD__." Cache: Caching turned off, returning...");
			return FALSE;
		}

		$fileName = $this->createFileName($params);
		if ($fileName === false) return FALSE;
		wfDebugLog('extensions', __METHOD__." Cache: Checking cache for ".$fileName);
		

		if (file_exists($fileName)) {
			$expireTime = time() - 900;
			wfDebugLog('extensions', __METHOD__." Cache: File ".$fileName." exists!");
			if (filemtime($fileName) >= (time() - 900)) {
				wfDebugLog('extensions', __METHOD__." Cache: File ".$fileName." has fresh data, returning");
				$stringData = file_get_contents($fileName);
				$data = unserialize($stringData);
				return $data;
			}
		}
		return false;
	}

	public function saveCache($params, $data) {
		global $wgUseFileCache;
		if (!$wgUseFileCache) {
			wfDebugLog('extensions', __METHOD__." Cache: Caching turned off, returning...");
			return FALSE;
		}

		wfDebugLog('extensions', __METHOD__." Cache: Saving cache data to ".$fileName);
		$fileName = $this->createFileName($params);
		if ($fileName === false) return false;

		$serialized = serialize($data);
		file_put_contents($fileName, $serialized);
		return TRUE;
	}

	private function createFileName($params) {
		//$tlIP = dirname( __FILE__ );
		$tlIP = dirname(dirname(dirname(__FILE__)));
		//wfDebugLog('extensions', __METHOD__." Cache: TestIP: ".$testIP);
		wfDebugLog('extensions', __METHOD__." Cache: Generating cache file name.");
		$key;
		if (strlen($params)) {
			$key = md5($params).".tlcache";
		} else if (!strlen($params)) {
			$key = md5("allevents").".tlcache";
		} else {
			return false;
		}
		$cacheDir = $tlIP."/cache/".substr($key, 0, 1)."/".substr($key, 0, 2);
		if (!file_exists($cacheDir)) mkdir($cacheDir);

		$fileName = $cacheDir."/".$key;

		//$fileName = $tlIP."/cache/".$key;
		wfDebugLog('extensions', __METHOD__." Cache: Cache file name ".$fileName);

		return $fileName;
	}
	
	public function getAllowedParams() {
		return array(
			'tlplanets' => null,
			'tlcategories' => null
		);
	}
	
	public function getParamDescription() {
		return array (
			"tlplanets" => "The timeline events associated with the planets passed, inclusive.",
			"tlcategories" => "The timeline events associated with the wiki categories passed, inclusive."
		);
	}
	
	public function getDescription() {
		return "This module retrieves timeline events for display.";
	}
	
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), 
			array( 
				"code" => "params", 
				"info" => "The pages parameter is formatted incorrectly."
			)
		);
	}
	
	protected function getExamples() {
		return array (
			"api.php?action=timeline&tlplanets=Athena|Galileo&tlcategories=Space_Vehicles|Republican_Fleet"
		);
	}
	
	public function getVersion() {
		return __CLASS__ . ": Version 1.0";
	}
}

?>
