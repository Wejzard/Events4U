<?php

// Set the reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));

class Config
{
    public static function DB_NAME()
    {
        return 'NULL';
    }
    public static function DB_PORT()
    {
        return  NULL;
    }
    public static function DB_USER()
    {
        return NULL;
    }
    public static function DB_PASSWORD()
    {
        return NULL;
    }
    public static function DB_HOST()
    {
        return NULL;
    }
    // JWT Secret Key Definition 
    public static function JWT_SECRET() {
       return NULL;
   }

}