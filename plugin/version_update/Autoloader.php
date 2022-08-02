<?php
/**
 * Update AutoLoader Class
 */
class G5UpdateAutoLoader
{
    /**
     * migration root path
     * @var string
     */
    private $directory = G5_PLUGIN_PATH . '/version_update/';
    /**
     * class filename extension
     * @var string
     */
    private $extension = ".lib.php";
    
    /**
     * regist autoload
     * @return bool
     */
    public function register() {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            return spl_autoload_register([$this, 'load'], true, true);
        } else {
            return spl_autoload_register([$this, 'load']);
        }
    }

    /**
     * load class
     * @param string $class Class name
     * @return void
     */
    protected function load($class)
    {
        include_once $this->directory . $class . $this->extension;
    }
}