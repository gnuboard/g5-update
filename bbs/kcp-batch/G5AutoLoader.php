<?php

/**
 * Update AutoLoader Class
 */
class G5AutoLoader
{
    /**
     * migration root path
     *
     * @var string
     */
    private $directory = '';
    /**
     * class filename extension
     *
     * @var string
     */
    private $extension = '';
    
    public function __construct($path = '/bbs/kcp-batch/')
    {
        $this->setDirectory(G5_PATH . $path);
        $this->setExtension('.php');
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
     * load class
     *
     * @param  string $class Class name
     * @return void
     */
    protected function load($class)
    {
        include $this->getDirectory() . $class . $this->getExtension();
    }

    /**
     * Get the value of Directory
     */ 
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set the value of directory
     *
     * @return  self
     */ 
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Get the value of extension
     */ 
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set the value of Extension
     *
     * @return  self
     */ 
    public function setExtension($extension)
    {
        $this->extension = $extension;

        return $this;
    }
}