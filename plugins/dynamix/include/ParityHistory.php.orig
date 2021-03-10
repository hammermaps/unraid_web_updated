<?PHP
/* Copyright 2005-2020, Lime Technology
 * Copyright 2012-2020, Bergware International.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
// add translations
$_SERVER['REQUEST_URI'] = 'main';
require_once "$docroot/webGui/include/Translations.php";

require_once "$docroot/webGui/include/Helpers.php";
extract(parse_plugin_cfg('dynamix',true));

$month = [' Jan '=>'-01-',' Feb '=>'-02-',' Mar '=>'-03-',' Apr '=>'-04-',' May '=>'-05-',' Jun '=>'-06-',' Jul '=>'-07-',' Aug '=>'-08-',' Sep '=>'-09-',' Oct '=>'-10-',' Nov '=>'-11-',' Dec '=>'-12-'];

function his_plus($val, $word, $last) {
  return $val>0 ? (($val||$last)?($val.' '.$word.($last?'':', ')):'') : '';
}
function his_duration($time) {
  if (!$time) return 'Unavailable';
  $days = floor($time/86400);
  $hmss = $time-$days*86400;
  $hour = floor($hmss/3600);
  $mins = $hmss/60%60;
  $secs = $hmss%60;
  return his_plus($days,_('day'),($hour|$mins|$secs)==0).his_plus($hour,_('hr'),($mins|$secs)==0).his_plus($mins,_('min'),$secs==0).his_plus($secs,_('sec'),true);
}
?>
<!DOCTYPE html>
<html <?=$display['rtl']?>lang="<?=strtok($locale,'_')?:'en'?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Content-Security-Policy" content="block-all-mixed-content">
<meta name="format-detection" content="telephone=no">
<meta name="viewport" content="width=1600">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="same-origin">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/default-fonts.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/default-popup.css")?>">
</head>
<body>
<table class='share_status'><thead><tr><td><?=_('Date')?></td><td><?=_('Duration')?></td><td><?=_('Speed')?></td><td><?=_('Status')?></td><td><?=_('Errors')?></td></tr></thead><tbody>
<?
$log = '/boot/config/parity-checks.log'; $list = [];
if (file_exists($log)) {
  $handle = fopen($log, 'r');
  while (($line = fgets($handle)) !== false) {
    [$date,$duration,$speed,$status,$error] = explode('|',$line);
    if ($speed==0) $speed = _('Unavailable');
    $date = str_replace(' ',', ',strtr(str_replace('  ',' 0',$date),$month));
    if ($duration>0||$status<>0) $list[] = "<tr><td>$date</td><td>".his_duration($duration)."</td><td>$speed</td><td>".($status==0?_('OK'):($status==-4?_('Canceled'):$status))."</td><td>$error</td></tr>";
  }
  fclose($handle);
}
if ($list)
  for ($i=count($list); $i>=0; --$i) echo $list[$i];
else
  echo "<tr><td colspan='5' style='text-align:center;padding-top:12px'>"._('No parity check history present')."!</td></tr>";
?>
</tbody></table>
<div style="text-align:center;margin-top:12px"><input type="button" value="<?=_('Done')?>" onclick="top.Shadowbox.close()"></div>
</body>
</html>