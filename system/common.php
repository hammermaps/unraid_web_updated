<?php
session_start();

class Common
{
    /**
     * @var string
     */
    private string $docroot;

    /**
     * common constructor.
     */
    public function __construct()
    {
        $this->docroot = '/usr/local/emhttp';
    }

    /**
     * @return Common
     */
    public static function getInstance(): Common {
        return new Common();
    }

    /**
     * @return string
     */
    public function getDocRoot(): string {
        return $this->docroot;
    }
}

//var_dump(Common::getInstance()->getDocRoot());