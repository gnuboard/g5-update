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
    
    public function __construct()
    {
        $this->setDirectory(G5_LIB_PATH . '/billing/');
        $this->setExtension('.php');
    }
    
    /**
     * regist autoload
     *
     * @return bool
     */
    public function register()
    {
        if (PHP_VERSION_ID  >= 50300) {
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
        $filePath = $this->getDirectory() . $class . $this->getExtension();
        if (file_exists($filePath)) {
            include_once ($filePath);
        } else {
           // 하위폴더 class load
           if ($handle = opendir($this->getDirectory())) {
               while (($file = readdir($handle)) !== false) {
                   if (($file !== '.') && ($file !== '..')) {
                       if (is_dir($this->getDirectory() . $file)) {
                           $fileName = $this->getDirectory() . $file . '/' . $class . $this->getExtension();
                           if(file_exists($fileName)){
                               include_once $fileName;
                           }
                           break;
                       }
                   }
               }
               closedir($handle);
           }
        }
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