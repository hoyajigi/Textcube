<?php

// TODO: i18n (Ticket #1133)

function GoogleMap_AddPost($target, $mother) {
	// TODO: Extract address information from the content
}

function GoogleMap_UpdatePost($target, $mother) {
	// TODO: Extract address information from the content
}

function GoogleMap_Header($target) {
	global $configVal, $pluginURL;
	requireComponent('Textcube.Function.Setting');
	$config = Setting::fetchConfigVal($configVal);
	if (!is_null($config) && isset($config['apiKey'])) {
		$api_key = $config['apiKey'];
		$target .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$pluginURL/scripts/common.css\" />\n";
		$target .= "<script type=\"text/javascript\" src=\"http://maps.google.co.kr/maps?file=api&amp;v=2&amp;sensor=false&amp;key=$api_key\"></script>\n";
		$target .= "<script type=\"text/javascript\" src=\"$pluginURL/scripts/gmap_common.js?".time()."\"></script>\n";
		$target .= "<script type=\"text/javascript\">
		//<![CDATA[
		STD.addUnloadEventListener(function(){GUnload();});
		//]]>
		</script>\n";
	}
	return $target;
}

function GoogleMap_AdminHeader($target) {
	global $suri, $pluginURL, $blogURL, $serviceURL, $configVal;
	if ($suri['directive'] == '/owner/entry/post' || $suri['directive'] == '/owner/entry/edit') {
		requireComponent('Textcube.Function.Setting');
		$config = Setting::fetchConfigVal($configVal);
		$api_key = $config['apiKey']; // should exist here
		$target .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$pluginURL/scripts/common.css\" />\n";
		$target .= "<script type=\"text/javascript\" src=\"http://maps.google.co.kr/maps?file=api&amp;v=2&amp;sensor=false&amp;key=$api_key\"></script>\n";
		$target .= "<script type=\"text/javascript\">
		//<![CDATA[
		var pluginURL = '$pluginURL';
		var blogURL = '$blogURL';
		//]]>
		</script>";
		$target .= "<script type=\"text/javascript\" src=\"$pluginURL/scripts/gmap_common.js\"></script>\n";
		$target .= "<script type=\"text/javascript\" src=\"$pluginURL/scripts/gmap_editor.js\"></script>\n";
	}
	return $target;
}

function GoogleMap_AddToolbox($target) {
	global $pluginURL;
	$target .= "<img src=\"$pluginURL/images/gmap_toolbar.gif\" border=\"0\" alt=\"구글맵 추가하기\" onclick=\"GMapTool_Insert();\" style=\"cursor:pointer\" />\n";
	return $target;
}

function GoogleMap_View($target, $mother) {
	global $gmap_msg;
	global $configVal, $pluginURL;
	requireComponent('Textcube.Function.Setting');
	requireComponent('Textcube.Function.Misc');
	$config = Setting::fetchConfigVal($configVal);
	$matches = array();
	$offset = 0;
	while (preg_match('/\[##_GoogleMap\|(([^|]+)\|)?_##\]/', $target, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
		// SUGGUEST: [##_GoogleMap|{JSON_REPRESENTATION_OF_PARAMETERS_WITHOUT_NEWLINES}|_##]
		$id = 'GMapContainer'.$mother.rand();
		ob_start();
?>
		<div id="<?php echo $id;?>" style="border: 1px solid #666;"></div>
		<script type="text/javascript">
		//<![CDATA[
		var c = document.getElementById('<?php echo $id;?>');
		if (GBrowserIsCompatible()) {
			var map = GMap_CreateMap(c, <?php echo $matches[2][0];?>);
		} else {
			c.innerHTML = '<p style="text-align:center; color:#c99;">이 웹브라우저는 구글맵과 호환되지 않습니다.</p>';
		}
		//]]>
		</script>
<?php
		$output = ob_get_contents();
		ob_end_clean();
		$target = substr_replace($target, $output, $matches[0][1], strlen($matches[0][0]));
		$offset += $matches[0][1] + strlen($output);
	}
	return $target;
}

function GoogleMap_LocationLogView($target) {
	global $blogid, $blog, $blogURL, $pluginURL, $configVal, $service, $database;
	requireComponent('Textcube.Function.Misc');
	$config = Setting::fetchConfigVal($configVal);
	$locatives = getLocatives($blogid);
	$width = Misc::getContentWidth();
	$height = intval($width * 1.2);
	$default_type = isset($config['locative_maptype']) ? $config['locative_maptype'] : 'G_HYBRID_MAP';
	$id = 'LocationMap';
	$lat = $config['latitude'];
	$lng = $config['longitude'];
	$zoom = 10;
	ob_start();
?>
	<div style="text-align:center;"><div id="<?php echo $id;?>" style="margin:0 auto;"></div></div>
	<script type="text/javascript">
	//<![CDATA[
	var process_count = 0;
	var polling_interval = 60; // ms
	var boundary = null;
	var locationMap = null;
	function adjustToBoundary() {
		var z = locationMap.getBoundsZoomLevel(boundary);
		if (z > 8)
			z--;
		if (z > 12)
			z = 12;
		locationMap.setZoom(z);
		locationMap.setCenter(boundary.getCenter());
	}
	function locationFetchPoller(target_count) {
		if (process_count != target_count) {
			window.setTimeout('locationFetchPoller('+target_count+');', polling_interval);
			return;
		}
		adjustToBoundary();
	}
	STD.addLoadEventListener(function() {
		var c = document.getElementById('<?php echo $id;?>');
		c.style.width = "<?php echo $width;?>px"
		c.style.height = "<?php echo $height;?>px";
		if (GBrowserIsCompatible()) {
			locationMap = new GMap2(c);
			locationMap.addMapType(G_PHYSICAL_MAP);
			locationMap.setMapType(<?php echo $default_type;?>);
			locationMap.addControl(new GHierarchicalMapTypeControl());
			locationMap.addControl(new GLargeMapControl());
			locationMap.addControl(new GScaleControl());
			locationMap.enableContinuousZoom();
			locationMap.setCenter(new GLatLng(<?php echo $lat;?>, <?php echo $lng;?>), <?php echo $zoom;?>);
			boundary = new GLatLngBounds(locationMap.getCenter());
			var locations = new Array();
<?php
	$count = 0;
	$countRemoteQuery = 0;
	foreach ($locatives as $locative) {
		$locative['link'] = "$blogURL/" . ($blog['useSloganOnPost'] ? 'entry/' . URL::encode($locative['slogan'],$service['useEncodedURL']) : $locative['id']);
		$row = POD::queryRow("SELECT * FROM {$database['prefix']}GMapLocations WHERE blogid = ".getBlogId()." AND address = '".POD::escapeString($locative['location'])."'");
		$result = 9; // 0 = found, 1 = find in client, 9 = not found
		if ($row == null || empty($row)) {
			// Recursively repeat until location is found. (continuously reducing accuracy)
			$addr = explode(' ', _GMap_normalizeAddress($locative['location']));
			while (true) {
				if ($countRemoteQuery == 12) { // not to exceed script-running time limit
					$result = 1;
					break;
				}
				$url = "http://maps.google.co.kr/maps/geo?q=".urlencode(trim(implode(' ', $addr)))."&output=csv&sensor=false&key={$config['apiKey']}";
				$response = requestHttp('get', $url, false, 'text/plain');
				$countRemoteQuery++;
				if ($response === false) {
					$result = 9;
					break;
				} else {
					$response_lines = explode("\n", $response[1]);
					$response_csv = explode(',', $response_lines[1]);
					if ($response_csv[0] == '200') {
						// Insert for later use.
						$lat = $response_csv[2];
						$lng = $response_csv[3];
						POD::execute("INSERT INTO {$database['prefix']}GMapLocations VALUES (".getBlogId().", '".POD::escapeString($locative['location'])."', $lng, $lat, ".time().")");
						$result = 0;
						break;
					} else {
						$lat = null; $lng = null;
						$result = 9;
						if (count($addr) == 1)
							break;
						if ($result == 9)
							array_pop($addr);
						continue;
					}
				}
			}
			if ($result == 9) {
				// Not found. Don't try also later.
				POD::execute("INSERT INTO {$database['prefix']}GMapLocations VALUES (".getBlogId().", '".POD::escapeString($locative['location'])."', NULL, NULL, ".time().")");
			}
		} else {
			$lat = $row['latitude'];
			$lng = $row['longitude'];
			$result = 0;
		}
		switch ($result) {
		case 0: // found
			echo "\t\t\tGMap_addLocationMarkDirect(locationMap, {address:GMap_normalizeAddress('{$locative['location']}'), path:'{$locative['location']}'}, '".str_replace("'", "\\'", $locative['title'])."', encodeURI('".str_replace("'", "\\'", $locative['link'])."'), new GLatLng($lat, $lng), boundary, locations);\n";
			break;
		case 1: // find in client
			echo "\t\t\tGMap_addLocationMark(locationMap, '{$locative['location']}', '".str_replace("'", "\\'", $locative['title'])."', encodeURI('".str_replace("'", "\\'", $locative['link'])."'), boundary, locations);\n";
			break;
		case 9:
			echo "\t\t\tif (process_count != undefined) process_count++;\n";
		}
		$count++;
	}
?>
			window.setTimeout('locationFetchPoller(<?php echo $count;?>);', polling_interval);
			//adjustToBoundary();
		} else {
			c.innerHTML = '<p style="text-align:center; color:#c99;">이 웹브라우저는 구글맵과 호환되지 않습니다.</p>';
		}
	});
	//]]>
	</script>
<?php
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}

function GoogleMap_ConfigHandler($data) {
	global $gmap_msg;
	requireComponent('Textcube.Function.Setting');
	$config = Setting::fetchConfigVal($data);
	if (!is_numeric($config['latitude']) || !is_numeric($config['longitude']) ||
		$config['latitude'] < -90 || $config['latitude'] > 90 || $config['longitude'] < -180 || $config['longitude'] > 180)
		return '위도 또는 경도의 값이 올바르지 않습니다.';
	return true;
}

function GoogleMapUI_Insert($target) {
	global $configVal, $pluginURL;
	requireComponent('Textcube.Function.Misc');
	$config = Setting::fetchConfigVal($configVal);
	$lat = $config['latitude'];
	$lng = $config['longitude'];
	$default_type = 'G_HYBRID_MAP';
	$default_width = min(Misc::getContentWidth(), 500);
	$default_height = 400;
	$zoom = 10;
	_GMap_printHeaderForUI('구글맵 삽입하기', $config['apiKey']);
?>
	<div id="controls">
		<button id="toggleMarkerAddingMode">마커 표시 모드</button>
		<button id="doInsert">본문에 삽입하기</button>
	</div>
	<div style="text-align:center;">
		<div id="GoogleMapPreview" style="width:<?php echo $default_width;?>px; height:<?php echo $default_height;?>px; margin:0 auto;"></div>
	</div>
	<script type="text/javascript">
	//<![CDATA[
	function initializeMap() {
		map = new GMap2($('#GoogleMapPreview')[0]);
		map.addMapType(G_PHYSICAL_MAP);
		map.setMapType(<?php echo $default_type;?>);
		map.addControl(new GHierarchicalMapTypeControl());
		map.addControl(new GLargeMapControl());
		map.addControl(new GScaleControl());
		map.enableScrollWheelZoom();
		map.enableContinuousZoom();
		map.setCenter(new GLatLng(<?php echo $lat;?>, <?php echo $lng;?>), <?php echo $zoom;?>);
	}
	//]]>
	</script>
	<h2>지도 검색</h2>
	<div class="accordion-elem">
		<p><label>위치 검색 : <input type="text" class="editControl" id="inputQuery" value="" /></label><button id="queryLocation">찾기</button></p>
		<div id="queryResult"></div>
	</div>
	<h2>기본 설정</h2>
	<div class="accordion-elem">
		<p><label>가로(px) : <input type="text" class="editControl" id="inputWidth" value="<?php echo $default_width;?>" /></label></p>
		<p><label>세로(px) : <input type="text" class="editControl" id="inputHeight" value="<?php echo $default_height;?>" /></label></p>
		<p><button id="applyBasicSettings">적용</button></p>
	</div>
<?php
	// TODO: 주소 추출 UI
	// - TODO: 포스트 내용 텍스트 얻어오기 및 주소 정보 추출
	_GMap_printFooterForUI();
}

function _GMap_printHeaderForUI($title, $api_key) {
	global $pluginURL, $blogURL, $service, $adminSkinSetting;
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Google Map Plugin: <?php echo $title;?></title>
	<link rel="stylesheet" type="text/css" href="<?php echo $pluginURL;?>/insert.css" />
	<script type="text/javascript" src="<?php echo $pluginURL;?>/scripts/jquery-1.2.6.min.js"></script>
	<script type="text/javascript" src="<?php echo $pluginURL;?>/scripts/jquery-ui-1.6rc2.js"></script>
	<!-- script type="text/javascript" src="<?php echo $pluginURL;?>/.js"></script -->
	<script type="text/javascript" src="http://maps.google.co.kr/maps?file=api&amp;v=2&amp;sensor=false&amp;key=<?php echo $api_key;?>"></script>
	<script type="text/javascript" src="<?php echo $pluginURL;?>/scripts/gmap_common.js?<?php echo time();?>"></script>
	<script type="text/javascript" src="<?php echo $pluginURL;?>/scripts/gmap_ui.js?<?php echo time();?>"></script>
	<script type="text/javascript">
	//<![CDATA[
	var pluginURL = '<?php echo $pluginURL;?>';
	var blogURL = '<?php echo $blogURL;?>';
	$(window).unload(GUnload);
	//]]>
	</script>
</head>
<body>
<div id="all-wrap">
	<h1><?php echo $title;?></h1>
	<div id="layout-body">
<?php
}

function _GMap_printFooterForUI() {
?>
	</div>
</div>
</body>
</html>
<?php
}

function _GMap_normalizeAddress($address) {
	//return trim(implode(' ', array_slice(explode('/', $address), 0, 4)));
	return trim(implode(' ', explode('/', $address)));
}
/* vim: set noet ts=4 sts=4 sw=4: */
?>
