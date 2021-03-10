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
$_SERVER['REQUEST_URI'] = 'docker';
require_once "$docroot/webGui/include/Translations.php";

$unit = ['B','kB','MB','GB','TB','PB','EB','ZB','YB'];
$list = [];

function autoscale($value) {
  global $unit;
  $size = count($unit);
  $base = $value ? floor(log($value, 1000)) : 0;
  if ($base>=$size) $base = $size-1;
  $value /= pow(1000, $base);
  $decimals = $base ? ($value>=100 ? 0 : ($value>=10 ? 1 : (round($value*100)%100===0 ? 0 : 2))) : 0;
  return number_format($value, $decimals, '.', $value>9999 ? ',' : '').' '.$unit[$base];
}
function align($text, $w=13) {
  return sprintf("%{$w}s",$text);
}
function gap($text) {
  return preg_replace('/([kMGTPEZY]?B)$/'," $1",$text);
}
function byteval($data) {
  global $unit;
  [$value,$base] = explode(' ',gap($data));
  return $value*pow(1000,array_search($base,$unit));
}

exec("docker ps -sa --format='{{.Names}}|{{.Size}}'",$container);
echo align(_('Name'),-30).align(_('Container')).align(_('Writable')).align(_('Log'))."\n";
echo str_repeat('-',69)."\n";
foreach ($container as $ct) {
  [$name,$size] = explode('|',$ct);
  [$writable,$dummy,$total] = explode(' ',str_replace(['(',')'],'',$size));
  $list[] = ['name' => $name, 'total' => byteval($total), 'writable' => byteval($writable), 'log' => (exec("docker inspect --format='{{.LogPath}}' $name|xargs du -b 2>/dev/null |cut -f1")) ?: "0"];
}
$sum = ['total' => 0, 'writable' => 0, 'log' => 0];
array_multisort(array_column($list,'total'),SORT_DESC,$list); // sort on container size
foreach ($list as $ct) {
  echo align($ct['name'],-30).align(autoscale($ct['total'])).align(autoscale($ct['writable'])).align(autoscale($ct['log']))."\n";
  $sum['total'] += $ct['total'];
  $sum['writable'] += $ct['writable'];
  $sum['log'] += $ct['log'];
}
echo str_repeat('-',69)."\n";
echo align(_('Total size'),-30).align(autoscale($sum['total'])).align(autoscale($sum['writable'])).align(autoscale($sum['log']))."\n";
?>
