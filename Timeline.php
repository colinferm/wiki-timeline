<?php
if (!defined('MEDIAWIKI')) die();

require_once($IP."/extensions/Timeline/TimelineLib.php");
define( 'URSTL_VERSION', '1.1' );

$wgExtensionCredits['parserhook'][] = array(
        'path'           => __FILE__,
        'name'           => 'URS Timeline',
        'version'        => URSTL_VERSION,
        'author'         => 'Colin Andrew Ferm',
        'description' => 'Adds <nowiki><timeline [planet=""] [category=""] [list="true|false"] [height="400px"] key="[true|false]"/></nowiki> tag to parser to show timeline events.'
);

$wgExtensionCredits['specialpage'][] = array(
        'path'           => __FILE__,
        'name'           => 'URS Timeline',
        'version'        => URSTL_VERSION,
        'author'         => 'Colin Andrew Ferm',
        'description'	 =>	'Allows admins to enter and edit timeline events.'
);

$wgExtensionCredits['other'][] = array(
        'name' => 'Timeline API',
        'author' =>'Colin Andrew Ferm',
        'description' => 'Adds an API module for outputting formatted XML and JSON of timeline events.',
        'version' => URSTL_VERSION,
        'path' => __FILE__
);

$tlIP = dirname( __FILE__ );

$wgSpecialPages['Timeline'] = 'SpecialTimeline';
$wgAutoloadClasses['SpecialTimeline'] = $tlIP . '/SpecialTimeline.php';
$wgExtensionMessagesFiles['Timeline'] = $ttIP . '/Messages.php';

$wgHooks['ParserFirstCallInit'][] = 'timelineInt';
$wgHooks['SkinAfterBottomScripts'][] = 'ursTimelineBottomScripts';
$wgHooks['BeforePageDisplay'][] = 'ursTimelineHeaderScripts';

$wgAjaxExportList[] = 'ursUpdateTimelineEvent';
$wgAjaxExportList[] = 'ursDeleteTimelineEvent';

function ursTimelineBottomScripts( $skin, &$text ) {
        //wfDebugLog('extensions', $text);
	$text .= "\n".'<script>'.
		'Timeline_ajax_url="http://'.$_SERVER['SERVER_NAME'].'/wiki/extensions/Timeline/timeline_ajax/simile-ajax-api.js";'.
		'Timeline_urlPrefix="http://'.$_SERVER['SERVER_NAME'].'/wiki/extensions/Timeline/timeline_js/";'.
		'SimileAjax_urlPrefix="http://'.$_SERVER['SERVER_NAME'].'/wiki/extensions/Timeline/timeline_ajax/";'.
		'Timeline_parameters="bundle=true";'.
		'</script>';
        //$text .= "\n<script type='text/javascript' src='http://".$_SERVER['SERVER_NAME']."/wiki/extensions/Timeline/timeline_ajax/simile-ajax-api.js&ver=".URSTL_VERSION."'></script>";
        $text .= "\n<script type='text/javascript' src='http://".$_SERVER['SERVER_NAME']."/wiki/extensions/Timeline/timeline_js/timeline-api.js?ver=".URSTL_VERSION."'></script>";
	//$text .= "\n<script type='text/javascript' src='http://".$_SERVER['SERVER_NAME']."/wiki/extensions/Timeline/Timeline.js?ver=".URSTL_VERSION."'></script>";
	$text .= "\n<script type='text/javascript' src='http://".$_SERVER['SERVER_NAME']."/wiki/extensions/Timeline/Timeline.min.js?ver=".URSTL_VERSION."'></script>";
	$text .= "\n<script type='text/javascript' src='http://".$_SERVER['SERVER_NAME']."/wiki/extensions/Timeline/jquery.tagsinput.js?ver=".URSTL_VERSION."'></script>";
	return true;
}

function timelineInt(&$parser) {
	$parser->setHook( 'timeline', 'timelineRender' );
	return true;

}

function ursTimelineHeaderScripts( OutputPage &$out, Skin &$skin ) {
        //wfDebugLog('extensions', 'Header scripts called.');
	$out->addHeadItem( 'urs_tag_style', '<link rel="stylesheet" type="text/css" href="/wiki/extensions/Timeline/jquery.tagsinput.css" />' );
	return true;
}

require_once($IP."/extensions/Timeline/TimelineAPI.php");
require_once($IP."/extensions/Timeline/TimelineHook.php");
?>
