<?PHP
/* Copyright 2005-2019, Lime Technology
 * Copyright 2012-2019, Bergware International.
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
$file = realpath('/etc/wireguard/peers/'.$_GET['file']);
$lastmod = filemtime($file);
$csrf = exec("grep -Pom1 '^csrf_token=\"\K.[^\"]+' /var/local/emhttp/var.ini");
if (!$file || strpos($file,'/boot/config/wireguard')!==0 || !$_GET['csrf_token'] || $_GET['csrf_token']!=$csrf) return;

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastmod) {
  header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified');
} else {
  header('Last-Modified:'.gmdate('D, d M Y H:i:s', $lastmod).' GMT');
  header('Content-type:image/png');
  readfile($file);
}
?>