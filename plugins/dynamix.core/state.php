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

class State {
    /**
     * @var array
     */
    private array $index = [];

    /**
     * State constructor.
     */
    public function __construct() {
        $state_files = Common::getInstance()->listFiles('state');
        foreach ($state_files as $file) {
            $file_path = Common::getInstance()->getDocRoot().'/'.$file;
            if(is_file($file_path) && is_readable($file_path)) {
                $file = explode('/',$file);
                $filename = str_ireplace('.ini','',$file[1]);
                $this->index[$filename] = parse_ini_file($file_path, $filename != 'var');
            }
        }
    }

    /**
     * @param string $index
     * @return array
     */
    public function getState(string $index='var'):array {
        return (array)$this->index[$index];
    }

    /**
     * @param string $index
     */
    public function updateState(string $index='var') {
        $file_path = Common::getInstance()->getDocRoot().'/'.$index.'.ini';
        if(is_file($file_path) && is_readable($file_path)) {
            $this->index[$index] = parse_ini_file($file_path, $index != 'var');
        }
    }

    /**
     * @return array
     */
    public function getIndex(): array {

        return $this->index;
    }
}