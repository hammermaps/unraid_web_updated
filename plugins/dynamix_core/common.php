<?php
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
class Common
{
    /**
     * @var string
     */
    private string $docroot;

    /**
     * @var state|null
     */
    private ?state $state = null;

    /**
     * @var Common|null
     */
    private static ?Common $common = null;

    /**
     * common constructor.
     */
    public function __construct() {
        session_start();

        $this->docroot = '/usr/local/emhttp';
    }

    /**
     * @return Common
     */
    public static function getInstance(): Common {
        if(self::$common != null)
            return self::$common;

        self::$common = new Common();
        return self::$common;
    }

    /**
     * @return string
     */
    public function getDocRoot(): string {
        return $this->docroot;
    }

    /**
     * @return State
     */
    public function getState(): State {
        if($this->state != null)
            return $this->state;

        $this->state = new State();
        return $this->state;
    }

    /**
     * @param string $from
     * @return array|false
     */
    public function listFiles(string $from = '.')
    {
        if(!is_dir($from))
            return false;

        $files = array();
        $dirs = array( $from);
        while( NULL !== ($dir = array_pop( $dirs)))
        {
            if( $dh = opendir($dir))
            {
                while( false !== ($file = readdir($dh)))
                {
                    if( $file == '.' || $file == '..')
                        continue;

                    $path = $dir . '/' . $file;
                    if( is_dir($path))
                        continue;

                    $files[] = $path;
                }
                closedir($dh);
            }
        }
        return $files;
    }
}

Common::getInstance(); //init

require_once Common::getInstance()->getDocRoot()."/plugins/dynamix_core/state.php";
