<?PHP
/* Copyright 2005-2020, Lime Technology
 * Copyright 2015-2020, Bergware International
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
$_SERVER['REQUEST_URI'] = '';
require_once "$docroot/webGui/include/Translations.php";

if (isset($_GET['mount'])) {
  exec("ps -C btrfs -o cmd=|awk '/\/mnt\/{$_GET['mount']}$/{print $2}'",$action);
  echo implode(',',$action);
} elseif (empty($_GET['btrfs'])) {
  $var = parse_ini_file("state/var.ini");
  switch ($var['fsState']) {
  case 'Copying':
    echo "<strong>"._('Copying').", {$var['fsCopyPrcnt']}% "._('complete')."...</strong>";
    break;
  case 'Clearing':
    echo "<strong>"._('Clearing').", {$var['fsClearPrcnt']}% "._('complete')."...</strong>";
    break;
  default:
    echo substr($var['fsState'],-3)=='ing' ? 'wait' : 'stop';
    break;
  }
} else {
  echo exec('pgrep -cf /sbin/btrfs')>0 ? 'disable' : 'enable';
}
?>