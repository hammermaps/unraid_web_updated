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
$etc     = '/etc/wireguard';

$t1 = '6';  // 6 sec timeout
$t2 = '12'; // 12 sec timeout

if (!function_exists('_')) {function _($text) {return $text;}} // multi-language handling

function ipv4($ip) {
  return strpos($ip,'.')!==false;
}
function ipset($ip) {
  return ipv4($ip) ? $ip : "[$ip]";
}
function ipv6($ip) {
  return ipv4($ip) ? ':' : ']:';
}
function host($ip) {
  return strpos($ip,'/')!==false ? $ip : (ipv4($ip) ? "$ip/32" : "$ip/128");
}
function status($vtun) {
  return strpos(exec("wg show interfaces|tr '\n' ' '"),"$vtun ")===false;
}
function vtun() {
  global $etc;
  $x = 0; while (file_exists("$etc/wg{$x}.conf")) $x++;
  return "wg{$x}";
}
function delPeer($vtun) {
  global $etc,$name;
  $dir = "$etc/peers";
  foreach (glob("$dir/peer-$name-$vtun-*",GLOB_NOSORT) as $peer) unlink($peer);
}
function addPeer(&$x) {
  global $peers,$var;
  $peers[$x] = ['[Interface]'];                                  // [Interface]
  if ($var['client']) $peers[$x][] = $var['client'];             // #name
  if ($var['privateKey']) $peers[$x][] = $var['privateKey'];     // PrivateKey
  $peers[$x][] = $var['address'];                                // Address
  if ($var['listenport']) $peers[$x][] = $var['listenport'];     // ListenPort
  if ($var['dns']) $peers[$x][] = $var['dns'];                   // DNS server
  if ($var['mtu']) $peers[$x][] = $var['mtu'];                   // MTU
  $peers[$x][] = '';
  $peers[$x][] = "[Peer]";                                       // [Peer]
  if ($var['server']) $peers[$x][] = $var['server'];             // #name
  if ($var['handshake']) $peers[$x][] = $var['handshake'];       // PersistentKeepalive
  if ($var['presharedKey']) $peers[$x][] = $var['presharedKey']; // PresharedKey
  $peers[$x][] = $var['publicKey'];                              // PublicKey
  if ($var['tunnel']) $peers[$x][] = $var['tunnel'];             // Tunnel address
  $peers[$x][] = $var['endpoint'] ?: $var['internet'];           // Endpoint
  $peers[$x][] = $var['allowedIPs'];                             // AllowedIPs
  $x++;
}
function autostart($cmd,$vtun) {
  global $etc;
  $autostart = "$etc/autostart";
  $list = @file_get_contents($autostart) ?: '';
  switch ($cmd) {
  case 'off':
    if ($list && strpos($list,"$vtun ")!==false) file_put_contents($autostart,str_replace("$vtun ","",$list));
    break;
  case 'on':
    if (!$list || strpos($list,"$vtun ")===false) file_put_contents($autostart,$list."$vtun ");
    break;
  }
}
function createFiles($vtun) {
  global $etc,$peers,$name;
  $dir = "$etc/peers";
  if (!is_dir($dir)) mkdir($dir);
  delPeer($vtun);
  foreach ($peers as $id => $peer) {
    $cfg = "$dir/peer-$name-$vtun-$id.conf";
    $png = str_replace('.conf','.png',$cfg);
    file_put_contents($cfg,implode("\n",$peer)."\n");
    exec("qrencode -t PNG -r $cfg -o $png");
  }
}
function parseInput(&$input,&$x) {
  global $conf,$user,$var,$next,$default,$default6;
  foreach ($input as $key => $value) {
    list($id,$i) = explode(':',$key);
    if ($i != $next) {
      $conf[] = "\n[Peer]";
      $next = $i;
      if ($i>1) addPeer($x);
    }
    switch ($id) {
    case 'Name':
      if ($value) $conf[] = "#$value";
      if ($i==0) {
        $var['server'] = $value ? "#$value" : false;
      } else {
        $var['client'] = $value ? "#$value" : false;
      }
      break;
    case 'PrivateKey':
      if ($i==0) {
        $conf[] = "$id=$value";
      } else {
        if ($value) $user[] = "$id:$x=\"$value\"";
        $var['privateKey'] = $value ? "$id=$value" : false;
      }
      break;
    case 'PublicKey':
      if ($i==0) {
        $user[] = "$id:0=\"$value\"";
        $var['publicKey'] = "$id=$value";
      } else {
        $conf[] = "$id=$value";
      }
      break;
    case 'DNS':
      if ($i>0 && $value) {
        $user[] = "$id:$x=\"$value\"";
        $var['dns'] = "$id=$value";
      } else $var['dns'] = false;
      break;
    case 'PROT':
      $protocol = $value;
      $user[] = "$id:$i=\"$value\"";
      switch ($protocol) {
        case '46': $var['default']  = "AllowedIPs=$default, $default6"; break;
        case '6' : $var['default']  = "AllowedIPs=$default6"; break;
        default  : $var['default']  = "AllowedIPs=$default"; break;
      }
      break;
    case 'TYPE':
      if ($value==0||$value==4) {
        list($tag,$ips) = explode('=',$var['subnets1']);
        list($ip4,$ip6) = explode(', ',$ips);
        $list = ["$tag=$ip4"];
        if ($protocol=='46') $list[] = $ip6;
      } else {
        $list = array_map('trim',explode(',',$value<4 ? ($value%2 ? $var['subnets2'] : $var['subnets1']) : ($value<6 ? ($value%2 ? $var['shared2'] : $var['shared1']) : $var['default'])));
      }
      $var['allowedIPs'] = implode(', ',array_map('host',array_filter($list)));
      $var['tunnel'] = ($value==2||$value==3) ? $tunnel : false;
    case 'Network':
    case 'Network6':
    case 'UPNP':
    case 'DROP':
    case 'RULE':
    case 'NAT':
      $user[] = "$id:$i=\"$value\"";
      break;
    case 'Address':
      $hosts = implode(', ',array_map('host',array_filter(explode(', ',$value))));
      if ($i==0) {
        $conf[] = "$id=$value";
        $tunnel = "$id=$hosts";
      } else {
        $user[] = "$id:$x=\"$value\"";
        $var['address'] = "$id=$hosts";
      }
      break;
    case 'MTU':
      if ($value) $conf[] = "$id=$value";
      $var['mtu'] = $value ? "$id=$value" : false;
      break;
    case 'Endpoint':
      if ($i==0) {
        $user[] = "$id:0=\"$value\"";
        $var['endpoint'] = $value ? "Endpoint=".ipset($value) : false;
      } else {
        if ($value) $conf[] = "$id=$value";
        $var['listenport'] = $value ? "ListenPort=".explode(ipv6($value),$value)[1] : false;
        if ($var['endpoint'] && strpos($var['endpoint'],ipv6($var['endpoint']))===false) $var['endpoint'] .= ":".explode(ipv6($var['internet']),$var['internet'])[1];
      }
      break;
    case 'PersistentKeepalive':
      if ($value) $conf[] = "$id=$value";
      $var['handshake'] = $value ? "$id=$value" : false;
      break;
    case 'PresharedKey':
      if ($value) $conf[] = "$id=$value";
      $var['presharedKey'] = $value ? "$id=$value" : false;
      break;
    default:
      if ($value && $id[0]!='#') $conf[] = "$id=$value";
      break;
    }
  }
}
$default = '0.0.0.0/0';
$default6 = '::/0';
switch ($_POST['#cmd']) {
case 'keypair':
  $private = exec("wg genkey");
  $public = exec("wg pubkey <<<'$private'");
  echo $private."\0".$public;
  break;
case 'presharedkey':
  echo exec("wg genpsk");
  break;
case 'update':
  if (!exec("iptables -S|grep -om1 WIREGUARD")) {
    exec("iptables -N WIREGUARD;iptables -A FORWARD -j WIREGUARD");
  }
  if (!exec("ip6tables -S|grep -om1 WIREGUARD")) {
    exec("ip6tables -N WIREGUARD;ip6tables -A FORWARD -j WIREGUARD");
  }
  $cfg  = $_POST['#cfg'];
  $wg   = $_POST['#wg'];
  $name = $_POST['#name'];
  $vtun = $_POST['#vtun'];
  $conf = ['[Interface]'];
  $user = $peers = $var = [];
  $var['subnets1'] = "AllowedIPs=".implode(', ',(array_unique(explode(', ',$_POST['#subnets1']))));
  $var['subnets2'] = "AllowedIPs=".implode(', ',(array_unique(explode(', ',$_POST['#subnets2']))));
  $var['shared1']  = "AllowedIPs=".implode(', ',(array_unique(explode(', ',$_POST['#shared1']))));
  $var['shared2']  = "AllowedIPs=".implode(', ',(array_unique(explode(', ',$_POST['#shared2']))));
  $var['internet'] = "Endpoint=".implode(', ',(array_unique(explode(', ',$_POST['#internet']))));
  $next = 0; $x = 1;
  parseInput($_POST,$x);
  addPeer($x);
  exec("wg-quick down $vtun 2>/dev/null");
  file_put_contents($file,implode("\n",$conf)."\n");
  file_put_contents($cfg,implode("\n",$user)."\n");
  createFiles($vtun);
  if ($wg) exec("wg-quick up $vtun 2>/dev/null");
  $save = false;
  break;
case 'toggle':
  $vtun = $_POST['#vtun'];
  switch ($_POST['#wg']) {
  case 'stop':
    exec("timeout $t1 wg-quick down $vtun 2>/dev/null");
    echo status($vtun) ? 1 : 0;
    break;
  case 'start':
    exec("timeout $t1 wg-quick up $vtun 2>/dev/null");
    echo status($vtun) ? 0 : 1;
    break;
  }
  break;
case 'stats':
  require_once "$docroot/webGui/include/Helpers.php";
  $vtun = $_POST['#vtun'];
  $now = time(); $i = 0;
  exec('wg show all latest-handshakes',$shake);
  exec('wg show all transfer',$data);
  $reply = [];
  foreach ($shake as $row) {
    list($wg,$id,$time) = preg_split('/\s+/',$row);
    if ($vtun=='*') $reply[] = "$wg;".($time ? $now - $time : 0);
    elseif ($vtun==$wg) $reply[] = $time ? $now - $time : 0;
  }
  foreach ($data as $row) {
    list($wg,$id,$tx,$rx) = preg_split('/\s+/',$row);
    if ($vtun=='*'||$vtun==$wg) $reply[$i++] .= ';'.my_scale($rx,$unit,null,-1)." $unit;".my_scale($tx,$unit,null,-1)." $unit";
  }
  echo implode("\0",$reply);
  break;
case 'ping':
  $addr = $_POST['#addr'];
  echo exec("ping -qc1 -W4 $addr|grep -Po '1 received'");
  break;
case 'public':
  $context = stream_context_create(['https'=>['timeout'=>12]]);
  echo @file_get_contents('https://api.ipify.org',false,$context) ?: '';
  break;
case 'addtunnel':
  $vtun = vtun();
  $name = $_POST['#name'];
  touch("$etc/$vtun.conf");
  exec("wg-quick down $vtun 2>/dev/null");
  @unlink("$etc/$vtun.cfg");
  delPeer($vtun);
  autostart('off',$vtun);
  break;
case 'deltunnel':
  $vtun = $_POST['#vtun'];
  $name = $_POST['#name'];
  exec("wg-quick down $vtun 2>/dev/null");
  @unlink("$etc/$vtun.conf");
  @unlink("$etc/$vtun.cfg");
  delPeer($vtun);
  autostart('off',$vtun);
  break;
case 'import':
  $name = $_POST['#name'];
  $user = $peers = $var = $import = $sort = [];
  $entries = array_filter(array_map('trim',preg_split('/\[(Interface|Peer)\]/',$_POST['#data'])));
  foreach($entries as $key => $entry) {
    $i = $key-1;
    foreach (explode("\n",$entry) as $row) {
      if (ltrim($row)[0]!='#') {
        list($id,$data) = array_map('trim',explode('=',$row,2));
        $import["$id:$i"] = $data;
      } elseif ($i>=0) {
        $import["Name:$i"] = substr(trim($row),1);
      }
    }
  }
  if ($import['PrivateKey:0'] && !$import['PublicKey:0']) $import['PublicKey:0'] = exec("wg pubkey <<<'{$import['PrivateKey:0']}'");
  $import['UPNP:0'] = 'no';
  $import['NAT:0'] = 'no';
  list($subnet,$mask) = explode('/',$import['Address:0']);
  if (ipv4($subnet)) {
    $mask = ($mask>0 && $mask<32) ? $mask : 24;
    $import['Network:0'] = long2ip(ip2long($subnet) & (0x100000000-2**(32-$mask))).'/'.$mask;
    $import['Address:0'] = $subnet;
    $import['PROT:0'] = '';
  } else {
    $mask = ($mask>0 && $mask<128) ? $mask : 64;
    $import['Network6:0'] = strstr($subnet,'::',true).'::/'.$mask;
    $import['Address:0'] = $subnet;
    $import['PROT:0'] = '6';
  }
  $import['Endpoint:0'] = '';
  for ($n = 1; $n <= $i; $n++) {
    $vpn = strpos($import["AllowedIPs:$n"],$default)!==false || strpos($import["AllowedIPs:$n"],$default6)!==false;
    if ($vpn) $import["Address:$n"] = '';
    $import["TYPE:$n"] = $vpn ? 7 : 0;
    if ($import["TYPE:$n"]==0) $var['subnets1'] = "AllowedIPs=".$import["AllowedIPs:$n"];
  }
  foreach ($import as $key => $val) $sort[] = explode(':',$key)[1];
  array_multisort($sort,$import);
  $next = 0; $x = 1;
  $conf = ['[Interface]'];
  $var['default'] = $import['PROT:0']=='' ? "AllowedIPs=$default" : "AllowedIPs=$default6";
  $var['internet'] = "Endpoint=unknown";
  parseInput($import,$x);
  addPeer($x);
  $vtun = vtun();
  file_put_contents("$etc/$vtun.conf",implode("\n",$conf)."\n");
  file_put_contents("$etc/$vtun.cfg",implode("\n",$user)."\n");
  delPeer($vtun);
  autostart('off',$vtun);
  echo $vtun;
  break;
case 'autostart':
  autostart($_POST['#start'],$_POST['#vtun']);
  break;
case 'upnp':
  $upnp = '/var/tmp/upnp';
  if (is_executable('/usr/bin/upnpc')) {
    $gw = $_POST['#gw'].':';
    $link = $_POST['#link'];
    $xml = @file_get_contents($upnp) ?: '';
    if ($xml) {
      exec("timeout $t1 stdbuf -o0 upnpc -u $xml -m $link -l 2>&1|grep -qm1 'refused'",$output,$code);
      if ($code!=1) $xml = '';
    }
    if (!$xml) {
      exec("timeout $t2 stdbuf -o0 upnpc -m $link -l 2>/dev/null|grep -Po 'desc: \K.+'",$desc);
      foreach ($desc as $url) if ($url && strpos($url,$gw)!==false) {$xml = $url; break;}
    }
  } else $xml = "";
  file_put_contents($upnp,$xml);
  echo $xml;
  break;
case 'upnpc':
  if (!is_executable('/usr/bin/upnpc')) break;
  $xml = $_POST['#xml'];
  $vtun = $_POST['#vtun'];
  $link = $_POST['#link'];
  $ip = $_POST['#ip'];
  if ($_POST['#wg']=='active') {
    exec("timeout $t1 stdbuf -o0 upnpc -u $xml -m $link -l 2>/dev/null|grep -Po \"^(ExternalIPAddress = \K.+|.+\KUDP.+>$ip:[0-9]+ 'WireGuard-$vtun')\"",$upnp);
    list($addr,$upnp) = $upnp;
    list($type,$rule) = explode(' ',$upnp);
    echo $rule ? "UPnP: $addr:$rule/$type" : "UPnP: forwarding not set";
  } else {
    echo "UPnP: tunnel is inactive";
  }
  break;
}
?>
