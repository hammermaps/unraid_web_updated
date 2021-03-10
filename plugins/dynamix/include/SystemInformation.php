<?PHP
/* Copyright 2005-2020, Lime Technology
 * Copyright 2012-2020, Bergware International.
 * Copyright 2012, Andrew Hamer-Adams, http://www.pixeleyes.co.nz.
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

require_once "$docroot/webGui/include/Helpers.php";
extract(parse_plugin_cfg('dynamix',true));

$var = parse_ini_file('state/var.ini');

function dmidecode($key,$n,$all=true) {
  $entries = array_filter(explode($key,shell_exec("dmidecode -qt$n")));
  $properties = [];
  foreach ($entries as $entry) {
    $property = [];
    foreach (explode("\n",$entry) as $line) if (strpos($line,': ')!==false) {
      list($key,$value) = explode(': ',trim($line));
      $property[$key] = $value;
    }
    $properties[] = $property;
  }
  return $all ? $properties : $properties[0];
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
<style>
span.key{width:104px;display:inline-block;font-weight:bold}
span.key.link{text-decoration:underline;cursor:pointer}
div.box{margin-top:8px;line-height:30px;margin-left:40px}
div.closed{display:none}
</style>
<script src="<?autov('/webGui/javascript/translate.'.($locale?:'en_US').'.js')?>"></script>
<script>
// server uptime & update period
var uptime = <?=strtok(exec("cat /proc/uptime"),' ')?>;

function add(value, label, last) {
  label += (parseInt(value)!=1?'s':'');
  return parseInt(value)+' '+_(label)+(!last?', ':'');
}
function two(value, last) {
  return (parseInt(value)>9?'':'0')+parseInt(value)+(!last?':':'');
}
function updateTime() {
  document.getElementById('uptime').innerHTML = add(uptime/86400,'day')+two(uptime/3600%24)+two(uptime/60%60)+two(uptime%60,true);
  uptime++;
  setTimeout(updateTime, 1000);
}
</script>
</head>
<body onLoad="updateTime()">
<div class="box">
<div><span class="key"><?=_('Model')?>:</span>
<?
echo empty($var['SYS_MODEL']) ? _('N/A') : $var['SYS_MODEL'];
?>
</div>
<div><span class="key">M/B:</span>
<?
$board = dmidecode('Base Board Information','2',0);
echo "{$board['Manufacturer']} {$board['Product Name']} "._('Version')." {$board['Version']} - "._('s/n').": {$board['Serial Number']}";
?>
</div>
<div><span class="key"><?=_('BIOS')?>:</span>
<?
$bios = dmidecode('BIOS Information','0',0);
echo "{$bios['Vendor']} Version {$bios['Version']}. Dated: {$bios['Release Date']}";
?>
</div>
<div><span class="key"><?=_('CPU')?>:</span>
<?
$cpu = dmidecode('Processor Information','4',0);
$cpumodel = str_ireplace(["Processor","(C)","(R)","(TM)"],["","&#169;","&#174;","&#8482;"],$cpu['Version']);
echo $cpumodel.(strpos($cpumodel,'@')!==false ? "" : " @ {$cpu['Current Speed']}");
?>
</div>
<div><span class="key"><?=_('HVM')?>:</span>
<?
// Check for Intel VT-x (vmx) or AMD-V (svm) cpu virtualization support
// If either kvm_intel or kvm_amd are loaded then Intel VT-x (vmx) or AMD-V (svm) cpu virtualization support was found
$strLoadedModules = shell_exec("/etc/rc.d/rc.libvirt test");

// Check for Intel VT-x (vmx) or AMD-V (svm) cpu virtualization support
$strCPUInfo = file_get_contents('/proc/cpuinfo');

if (!empty($strLoadedModules)) {
  // Yah! CPU and motherboard supported and enabled in BIOS
  echo _("Enabled");
} else {
  echo '<a href="http://lime-technology.com/wiki/index.php/UnRAID_Manual_6#Determining_HVM.2FIOMMU_Hardware_Support" target="_blank">';
  if (strpos($strCPUInfo,'vmx')===false && strpos($strCPUInfo, 'svm')===false) {
    // CPU doesn't support virtualization
    echo _("Not Available");
  } else {
    // Motherboard either doesn't support virtualization or BIOS has it disabled
    echo _("Disabled");
  }
  echo '</a>';
}
?>
</div>
<div><span class="key"><?=_('IOMMU')?>:</span>
<?
// Check for any IOMMU Groups
$iommu_groups = shell_exec("find /sys/kernel/iommu_groups/ -type l");

if (!empty($iommu_groups)) {
  // Yah! CPU and motherboard supported and enabled in BIOS
  echo _("Enabled");
} else {
  echo '<a href="http://lime-technology.com/wiki/index.php/UnRAID_Manual_6#Determining_HVM.2FIOMMU_Hardware_Support" target="_blank">';
  if (strpos($strCPUInfo,'vmx')===false && strpos($strCPUInfo, 'svm')===false) {
    // CPU doesn't support virtualization so iommu would be impossible
    echo _("Not Available");
  } else {
    // Motherboard either doesn't support iommu or BIOS has it disabled
    echo _("Disabled");
  }
  echo '</a>';
}
?>
</div>
<div><span class="key"><?=_('Cache')?>:</span>
<?
$cache_installed = [];
$cache_devices = dmidecode('Cache Information','7');
foreach ($cache_devices as $device) $cache_installed[] = str_replace('kB','KiB',$device['Installed Size']);
echo implode(', ',$cache_installed);
?>
</div>
<div><span class="key link" onclick="document.getElementsByClassName('dimm_info')[0].classList.toggle('closed')"><?=_('Memory')?>:</span>
<?
/*
 Memory Device (16) will get us each ram chip. By matching on MB it'll filter out Flash/Bios chips
 Sum up all the Memory Devices to get the amount of system memory installed. Convert MB to GB
 Physical Memory Array (16) usually one of these for a desktop-class motherboard but higher-end xeon motherboards
 might have two or more of these.  The trick is to filter out any Flash/Bios types by matching on GB
 Sum up all the Physical Memory Arrays to get the motherboard's total memory capacity
 Extract error correction type, if none, do not include additional information in the output
 If maximum < installed then roundup maximum to the next power of 2 size of installed. E.g. 6 -> 8 or 12 -> 16
*/
$sizes = ['MB','GB','TB'];
$memory_type = $ecc = '';
$memory_installed = $memory_maximum = 0;
$memory_devices = dmidecode('Memory Device','17');
foreach ($memory_devices as $device) {
  if ($device['Type']=='Unknown') continue;
  list($size, $unit) = explode(' ',$device['Size']);
  $base = array_search($unit,$sizes);
  if ($base!==false) $memory_installed += $size*pow(1024,$base);
  if (!$memory_type) $memory_type = $device['Type'];
}
$memory_array = dmidecode('Physical Memory Array','16');
foreach ($memory_array as $device) {
  [$size, $unit] = explode(' ',$device['Maximum Capacity']);
  $base = array_search($unit,$sizes);
  if ($base>=1) $memory_maximum += $size*pow(1024,$base);
  if (!$ecc && $device['Error Correction Type']!='None') $ecc = "{$device['Error Correction Type']} ";
}
if ($memory_installed >= 1024) {
  $memory_installed = round($memory_installed/1024);
  $memory_maximum = round($memory_maximum/1024);
  $unit = 'GiB';
} else $unit = 'MiB';

// If maximum < installed then roundup maximum to the next power of 2 size of installed. E.g. 6 -> 8 or 12 -> 16
$low = $memory_maximum < $memory_installed;
if ($low) $memory_maximum = pow(2,ceil(log($memory_installed)/log(2)));
echo "$memory_installed $unit $memory_type $ecc("._('max. installable capacity')." $memory_maximum $unit".($low?'*':'').")";
?>
<div class="dimm_info closed">
<?
foreach ($memory_devices as $device) {
  if ($device['Type']=='Unknown') continue;
  $size = preg_replace('/( .)B$/','$1iB',$device['Size']);
  echo "<span class=\"key\"></span> {$device['Manufacturer']} {$device['Part Number']}, {$size} {$device['Type']} @ {$device['Configured Memory Speed']}";
}
?>
</div>
</div>
<div><span class="key"><?=_('Network')?>:</span>
<?
exec("ls /sys/class/net|grep -Po '^(bond|eth)\d+$'",$sPorts);
$i = 0;
foreach ($sPorts as $port) {
  $int = "/sys/class/net/$port";
  $mtu = file_get_contents("$int/mtu");
  $link = file_get_contents("$int/carrier")==1;
  if ($i++) echo "<br><span class='key'></span>&nbsp;";
  if (substr($port,0,4)=='bond') {
    if ($link) {
      $bond_mode = str_replace('Bonding Mode: ','',file("/proc/net/bonding/$port",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)[1]);
      echo "$port: $bond_mode, mtu $mtu";
    } else echo "$port: bond down";
  } else {
    if ($link) {
      $speed = file_get_contents("$int/speed");
      $duplex = file_get_contents("$int/duplex");
      echo "$port: $speed Mbps, $duplex duplex, mtu $mtu";
    } else echo "$port: "._("interface down");
  }
}
?>
</div>
<div><span class="key"><?=_('Kernel')?>:</span>
<?
$kernel = exec("uname -srm");
echo $kernel;
?>
</div>
<div><span class="key"><?=_('OpenSSL')?>:</span>
<?
$openssl_ver = exec("openssl version|cut -d' ' -f2");
echo $openssl_ver;
?>
</div>
<div><span class="key"><?=_('Uptime')?>:</span> <span id="uptime"></span></div>
<div style="margin-top:24px;margin-bottom:12px"><span class="key"></span>
<input type="button" value="<?=_('Close')?>" onclick="top.Shadowbox.close()">
<?if ($_GET['more']):?>
<a href="<?=htmlspecialchars($_GET['more'])?>" class="button" style="display:inline-block;padding:1px" target="_parent"><?=_('More')?></a>
<?endif;?>
</div></div>
</body>
</html>
