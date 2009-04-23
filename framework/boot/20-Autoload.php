<?php
/// Copyright (c) 2004-2009, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

/** Binders */
global $__requireBasics, $__requireLibrary, $__requireComponent, $__requireModel, $__requireView;
if(!isset($__requireBasics)) $__requireBasics = array();
if(!isset($__requireLibrary)) $__requireLibrary = array();
if(!isset($__requireComponent)) $__requireComponent = array();
if(!isset($__requireModel)) $__requireModel = array();
if(!isset($__requireView)) $__requireView = array();
if(!isset($service)) $service = array();

function requireComponent($name) {
	return true;
}
function requireModel($name) {
	global $__requireModel;
	if(!in_array($name,$__requireModel)) {
		include_once (ROOT . "/library/model/$name.php");
		array_push($__requireModel,$name);
	}
}
function requireView($name) {
	global $__requireView;
	if(!in_array($name,$__requireView)) {
		include_once (ROOT . "/library/view/$name.php");
		array_push($__requireView,$name);
	}
}
function requireLibrary($name) {
	global $__requireLibrary;
	if(!in_array($name,$__requireLibrary)) {
		include_once (ROOT . "/library/$name.php");
		array_push($__requireLibrary,$name);
	}
}

/** Autoload components */
class Autoload {
	static function load($className) {
		$pos =strrpos($className,'_');
		if($pos!==false) {
			require_once ROOT.'/framework/'.str_replace('_','/',strtolower(substr($className,0,$pos))).'/'.substr($className,$pos+1).'.php';
		} else {
			require_once ROOT.'/framework/'.$className.'.php';
		}
	}
}
spl_autoload_register(array('Autoload', 'load'));
?>
