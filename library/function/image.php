<?php
/// Copyright (c) 2004-2014, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)

// img의 width/height에 맞춰 이미지를 리샘플링하는 함수. 썸네일 함수가 아님! 주의.
// resampleImage는 더 이상 사용되지 않습니다.
function resampleImage($imgString, $filename, $useAbsolutePath = true) {
	$blogid = getBlogId();
	$context = Model_Context::getInstance();

	if (!extension_loaded('gd') || !file_exists(__TEXTCUBE_ATTACH_DIR__."/{$blogid}/{$filename}")) {
		return $imgString;
	}

	if (!is_dir(__TEXTCUBE_CACHE_DIR__."/thumbnail")) {
		@mkdir(__TEXTCUBE_CACHE_DIR__."/thumbnail");
		@chmod(__TEXTCUBE_CACHE_DIR__."/thumbnail", 0777);
	}

	if (!is_dir(__TEXTCUBE_CACHE_DIR__."/thumbnail/".getBlogId())) {
		@mkdir(__TEXTCUBE_CACHE_DIR__."/thumbnail/".getBlogId());
		@chmod(__TEXTCUBE_CACHE_DIR__."/thumbnail/".getBlogId(), 0777);
	}

	$origImageSrc = ($useAbsolutePath ? $context->getProperty('uri.service') : $context->getProperty('uri.path')) . "/attach/{$blogid}/{$filename}";
	$tempWidth = $tempHeight = '';
	if (preg_match('/width="([1-9][0-9]*)"/i', $imgString, $temp))
		$tempWidth = $temp[1];

	if (preg_match('/height="([1-9][0-9]*)"/i', $imgString, $temp))
		$tempHeight = $temp[1];
	
	if (!empty($tempWidth) && is_numeric($tempWidth) && !empty($tempHeight) && is_numeric($tempHeight))
		$resizeImage = getImageResizer($filename, array('width' => $tempWidth, 'height' => $tempHeight, 'absolute' => $useAbsolutePath));
	else if (!empty($tempWidth) && !is_numeric($tempWidth) && empty($tempHeight))
		$resizeImage = getImageResizer($filename, array('width' => $tempWidth, 'absolute' => $useAbsolutePath));
	else if (empty($tempWidth) && !empty($tempHeight) && is_numeric($tempHeight))
		$resizeImage = getImageResizer($filename, array('height' => $tempHeight, 'absolute' => $useAbsolutePath));
	else 
		return $imgString;

	if ($resizeImage === false) return $imgString;
	
	if (basename($resizeImage[0]) == $filename) return $imgString;

	$resizeImageSrc = $resizeImage[0];
	$resizeImageWidth = $resizeImage[1];
	$resizeImageHeight = $resizeImage[2];

	$imgString = preg_replace('/src="([^"]+)"/i', 'src="'.$resizeImageSrc.'"', $imgString);
	$imgString = preg_replace('/width="([^"]+)"/i', 'width="'.$resizeImageWidth.'"', $imgString);
	$imgString = preg_replace('/height="([^"]+)"/i', 'height="'.$resizeImageHeight.'"', $imgString);
	$imgString = preg_replace('/onclick="open_img\(\'([^\']+)\'\)"/', "onclick=\"open_img('{$origImageSrc}')\"", $imgString);

	return $imgString;
}
?>
