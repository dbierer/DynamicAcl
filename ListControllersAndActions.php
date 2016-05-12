<?php
// recommended to run this from the command line
// for best results: start in your ZF2 project root directory
// looks for the "*/module/*/*/module.config.php" files

class ListControllersAndActions
{
    public function getActionsFromController($class, $dir)
    {
        $class    = str_replace(['\\',';'], [DIRECTORY_SEPARATOR, ''], $class);
        $fn       = $dir . '/src/' . $class  . '.php';                            
        $contents = file($fn);
        $actions  = array();
        foreach ($contents as $line) {
            if (preg_match('/public function (\w+?)Action\(/', $line, $matches)) {
                $actions[] = $matches[1];
            }
        }
        return $actions;
    }

    public function main()
    {
        $path = getcwd();
        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path), 
            RecursiveIteratorIterator::SELF_FIRST);
        $controllers = array();

        foreach($objects as $name => $object){
            if (strpos($name, '/module/') && strpos($name, 'module.config.php')) {
                $contents = include $name;
                if (isset($contents['controllers'])) {
                    $dir = dirname(dirname($name));
                    if (isset($contents['controllers']['invokables'])) {
                        foreach ($contents['controllers']['invokables'] as $key => $class) {
                            $controllers[$key] = $this->getActionsFromController($class, $dir);
                        }
                    }
                    if (isset($contents['controllers']['factories'])) {
                        foreach ($contents['controllers']['factories'] as $key => $fn) {
                            $fn = $dir . '/src/' . str_replace('\\', DIRECTORY_SEPARATOR, $fn) . '.php';
                            $factory = file($fn);
                            foreach ($factory as $line) {
                                if (strpos($line, 'use') !== FALSE && strpos($line, 'Controller')) {
                                    $class = trim(substr($line, 4));
                                    $controllers[$key] = $this->getActionsFromController($class, $dir);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $controllers;
    }
}

$list = new ListControllersAndActions();
echo json_encode($list->main());
