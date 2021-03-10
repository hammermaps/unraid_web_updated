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
$cmd  = $_POST['cmd'];
$path = $_POST['path'];

switch ($cmd) {
case 'balance':
case 'scrub':
  echo shell_exec("/sbin/btrfs $cmd status $path");
  break;
default:
  [$dev,$id] = explode(' ',$path);
  $file = "/var/lib/$cmd/check.status.$id";
  if (file_exists($file)) {
    switch ($cmd) {
      case 'btrfs': $pgrep = "pgrep -f '/sbin/btrfs check .*$dev'"; break;
      case 'rfs': $pgrep = "pgrep -f '/sbin/reiserfsck $dev'"; break;
      case 'xfs': $pgrep = "pgrep -f '/sbin/xfs_repair.*$dev'"; break;
    }
    echo file_get_contents($file);
    if (!exec($pgrep)) echo "\0";
  } else {
    echo "Not available\0";
  }
  break;
}
?>
