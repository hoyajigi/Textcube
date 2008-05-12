<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

function dumbCronScheduler($checkOnly=true)
{
	global $service;
	requireModel('common.setting');
	$now = Timestamp::getUNIXtime();

	$dumbCronStamps = getServiceSetting('dumbCronStamps',
			serialize( array( '1m' => 0, '5m' => 0, '30m' => 0, 
					'1h' => 0, '2h' => 0, '6h' => 0, '12h' => 0 )));

	$dumbCronStamps = unserialize( $dumbCronStamps );

	$schedules = array(
					'1m'  => 60,
					'5m'  => 60*5,
					'30m' => 60*30,
					'1h'  => 60*60,
					'2h'  => 60*60*2,
					'6h'  => 60*60*6,
					'12h' => 60*60*12 );
	/* Events: Cron1m, Cron5m, Cron30m, Cron1h, Cron2h, Cron6h, Cron12h */
	foreach( $schedules as $d => $diff ) {
		if( $now > $diff + $dumbCronStamps[$d]    ) { 
			if( $checkOnly && eventExists("Cron$d") ) return true;
			fireEvent( "Cron$d",  null, $now );
			$dumbCronStamps[$d] = $now;
		}
	}
	setServiceSetting( 'dumbCronStamps', serialize( $dumbCronStamps ) );
	return false;
}

function doCronJob()
{
	dumbCronScheduler(false);
}

function checkCronJob()
{
	global $service,$blogURL;
	/* Cron, only in single page request, not in a page dead link */
	if( !empty($_SERVER['HTTP_REFERER']) || !dumbCronScheduler(true) ) return;

	ob_start();
	$s = fsockopen( $_SERVER['SERVER_ADDR'], isset($service['port']) ? $service['port'] : 80 );
	fputs( $s, "GET {$blogURL}/cron HTTP/1.1\r\n" );
	fputs( $s, "Host: {$_SERVER['HTTP_HOST']}\r\n" );
	fputs( $s, "Referer: {$_SERVER['REQUEST_URI']} from {$_SERVER['REMOTE_ADDR']}\r\n" );
	fputs( $s, "\r\n");
	while( ($x = fread($s,102400000) ) ) {
		print $x;
	}
	fclose($s);
	if( !empty($service['debugmode']) ) {
		echo ob_get_clean();
	} else {
		ob_clean();
	}
}

?>