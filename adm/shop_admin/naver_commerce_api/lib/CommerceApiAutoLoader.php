<?php

/**
 * Update AutoLoader Class
 */
class CommerceApiAutoLoader
{
    /**
     * migration root path
     *
     * @var string
     */
    private $directory;
    /**
     * class filename extension
     *
     * @var string
     */
    private $extension;
    
    public function __construct()
    {
        $this->directory = G5_ADMIN_PATH . '/shop_admin/naver_commerce_api/lib/';
        $this->extension = '.php';
    }
    
    /**
     * regist autoload
     *
     * @return bool
     */
    public function register()
    {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            return spl_autoload_register(array($this, 'load'), true, true);
        } else {
            return spl_autoload_register(array($this, 'load'));
        }
    }

    /**
     * unregist autoload
     *
     * @return bool
     */
    public function unregister()
    {
        return spl_autoload_unregister(array($this, 'load'));
    }

    /**
     * load class
     *
     * @param  string $class Class name
     * @return void
     */
    protected function load($class)
    {
        include_once $this->directory . $class . $this->extension;
    }
}