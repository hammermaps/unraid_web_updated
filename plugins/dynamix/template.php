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

// Define root path
$docroot = $_SERVER['DOCUMENT_ROOT'];

require_once "$docroot/plugins/dynamix.core/common.php";
require_once Common::getInstance()->getDocRoot()."/webGui/include/Helpers.php";

// Get the webGui configuration preferences
/** @var string $display */
extract(parse_plugin_cfg('dynamix',true));

// Read emhttp status
/** @var array $disks */
/** @var array $var */
foreach (Common::getInstance()->getState()->getIndex() as $varname => $options) {
    $$varname = $options;
    unset($varname,$options);
}

// Merge SMART settings
require_once Common::getInstance()->getDocRoot()."/webGui/include/CustomMerge.php";

// Pool devices
$pool_devices = false;
$pools = pools_filter($disks);
foreach ($pools as $pool) {
    $pool_devices |= $disks[$pool]['devices'];
}

// Read network settings
extract(Common::getInstance()->getState()->getState('network'));

// Language translations
$_SESSION['locale'] = $display['locale'];
$_SESSION['buildDate'] = date('Ymd',$var['regBuildTime']);

require_once Common::getInstance()->getDocRoot()."/webGui/include/Translations.php";

// Build webGui pages first, then plugins pages
require_once Common::getInstance()->getDocRoot()."/webGui/include/PageBuilder.php";
$site = [];
build_pages('webGui/*.page');
foreach (glob('plugins/*', GLOB_ONLYDIR) as $plugin) {
  if ($plugin != 'plugins/dynamix' && $plugin != 'plugins/dynamix.core')
      build_pages("$plugin/*.page");
}

// Get general variables
$name = $_GET['name'];
$dir = $_GET['dir'];
$path = substr(strtok($_SERVER['REQUEST_URI'],'?'),1);

// The current "task" is the first element of the path
$task = strtok($path,'/');

// Here's the page we're rendering
$myPage = $site[basename($path)];
$pageroot = Common::getInstance()->getDocRoot().'/'.dirname($myPage['file']);

// Giddyup
require_once Common::getInstance()->getDocRoot()."/webGui/include/DefaultPageLayout.php";
