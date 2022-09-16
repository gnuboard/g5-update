<?php

//Nonce 토큰

class Nonce
{
    private $FT_NONCE_KEY = '_nonce';
    private $FT_NONCE_DURATION = 3600; //60 * 60;
    private $FT_NONCE_UNIQUE_KEY;
    private $FT_NONCE_SESSION_KEY;

    private function __construct()
    {
        $this->FT_NONCE_UNIQUE_KEY = sha1($_SERVER['SERVER_SOFTWARE'] . G5_MYSQL_USER . session_id() . G5_TABLE_PREFIX);
        $this->FT_NONCE_SESSION_KEY = substr(md5($this->FT_NONCE_UNIQUE_KEY), 5);
    }

    public static function get_instance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }

    // This method creates a key / value pair for a url string

    function ft_nonce_create_query_string($action = '', $user = '')
    {
        return $this->FT_NONCE_KEY . "=" . self::ft_nonce_create($action, $user);
    }


    function ft_get_secret_key($secret)
    {
        return md5($this->FT_NONCE_UNIQUE_KEY . $secret);
    }


// This method creates an nonce. It should be called by one of the previous two functions.

    function ft_nonce_create($action = '', $user = '', $timeoutSeconds = null)
    {
        $timeoutSeconds = $timeoutSeconds === null ? $this->FT_NONCE_DURATION : $timeoutSeconds;

        $secret = self::ft_get_secret_key($action . $user);
        $salt = self::ft_nonce_generate_hash();
        $time = time();
        $maxTime = $time + $timeoutSeconds;
        $nonce = $salt . "|" . $maxTime . "|" . sha1($salt . $secret . $maxTime);
        set_session("token_$action" . $this->FT_NONCE_SESSION_KEY, $nonce);
        return $nonce;
    }

// This method validates an nonce

    function ft_nonce_is_valid($action = '', $user = '')
    {
        $secret = self::ft_get_secret_key($action . $user);
        $nonce = get_session("token_$action" . $this->FT_NONCE_SESSION_KEY);

        if (is_string($nonce) == false) {
            return false;
        }
        $a = explode('|', $nonce);
        if (count($a) != 3) {
            return false;
        }
        $salt = $a[0];
        $maxTime = (int)$a[1];
        $hash = $a[2];
        $back = sha1($salt . $secret . $maxTime);
        if ($back != $hash) {
            return false;
        }
        if (time() > $maxTime) {
            return false;
        }
        return true;
    }


// This method generates the nonce timestamp

    function ft_nonce_generate_hash()
    {
        $length = 10;
        $chars = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
        $ll = strlen($chars) - 1;
        $o = '';
        while (strlen($o) < $length) {
            $o .= $chars[mt_rand(0, $ll)];
        }
        return $o;
    }

}