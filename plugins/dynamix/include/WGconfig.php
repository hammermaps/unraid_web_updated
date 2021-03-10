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
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once "$docroot/webGui/include/Helpers.php";

$file = $_GET['file'];
$path = realpath('/etc/wireguard'.$_GET['path']);
$csrf = exec("grep -Pom1 '^csrf_token=\"\K.[^\"]+' /var/local/emhttp/var.ini");
if (!$path || strpos($path,'/boot/config/wireguard')!==0 || !$_GET['csrf_token'] || $_GET['csrf_token']!=$csrf) return;
?>
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/default-fonts.css")?>">
<link type="text/css" rel="stylesheet" href="<?autov("/webGui/styles/default-popup.css")?>">

<script src="<?autov('/webGui/javascript/dynamix.js')?>"></script>
<style>
body{margin:20px 0 0 50px}
pre{margin:0}
div{width:100%;margin-bottom:30px}
img{display:block;margin-bottom:10px}
img:hover{transform:scale(1.1)}
</style>
<script>
function cleanUp(id,file) {
  if (document.hasFocus()) {
    $('#'+id).val('Download').prop('disabled',false);
    $.post('/webGui/include/Download.php',{cmd:'delete',file:file,csrf_token:'<?=$_GET['csrf_token']?>'});
  } else {
    setTimeout(function(){cleanUp(id,file);},1000);
  }
}
function download(id,source,file) {
  $('#'+id).val('Downloading...').prop('disabled',true);
  $.post('/webGui/include/Download.php',{cmd:'save',source:source+'.conf',file:file,opts:'qj',csrf_token:'<?=$_GET['csrf_token']?>'},function(){
    $.post('/webGui/include/Download.php',{cmd:'save',source:source+'.png',file:file,opts:'qj',csrf_token:'<?=$_GET['csrf_token']?>'},function(zip){
      location = zip;
      setTimeout(function(){cleanUp(id,file);},1000);
    });
  });
}
</script>
<body>
<h3><u><?=$_GET['path']?'Remote peer configuration':'Local server configuration'?></u></h3>
<div>
<pre>
<?readfile("$path/$file.conf")?>
</pre>
</div>
<div>
<?if (file_exists("$path/$file.png")):?>
<img src="/webGui/include/WGimage.php?file=<?="$file.png"?>&csrf_token=<?=$_GET['csrf_token']?>&v=<?=filemtime("$path/$file.png")?>">
<?endif;?>
<input type="button" value="Close" onclick="top.Shadowbox.close()">
<input type="button" id="download" value="Download" onclick="download(this.id,'<?="$path/$file"?>','<?=$file?>.zip')">
</div>
</body>
