<?php /** @noinspection ALL */

if ( ! defined('ABSPATH')) {
	exit;
}

class ChurchToolsAutoloader
{
    public function register()
    {
        spl_autoload_register([$this, 'load_class']);
    }

    public function unregister()
    {
        spl_autoload_unregister([$this, 'load_class']);
    }

    public function load_class($className)
    {
        $classFile = dirname(__DIR__) . '/includes/classes/' . str_replace('\\', '/', $className) . '.php';
        
        if (file_exists($classFile)) {
            include_once $classFile;
        }
    }
}