<?php

class Router {
    
    private $routes;
    
    public function __construct() {
        $routesPath = ROOT . '/config/routes.php';
        $this->routes = include($routesPath);
    }
    
    private function getURI()
    {
        if(!empty($_SERVER['REQUEST_URI']))
        {
            return trim($_SERVER['REQUEST_URI'], '/');
//            return trim(trim($_SERVER['REQUEST_URI'], 'index.php?XDEBUG_SESSION_START=netbeans-xdebug'), '/');
        }
    }
    
    public function run() {
        
        $uri = $this->getURI();
        
        foreach ($this->routes as $uriPattern => $path) {

            if(preg_match("~^$uriPattern$~", $uri))
            {
                $internalRoute = preg_replace("~$uriPattern~", $path, $uri);
                
                $segments = explode('/', $internalRoute);
                
                $controllerName = ucfirst(array_shift($segments)) . 'Controller';
                $actionName = 'action' . ucfirst(array_shift($segments));
                
                $xtpl = new XTemplate('main.html', ROOT . '/views');
                array_push($segments, $xtpl);
                
                $parameters = $segments;
                
                $controllerFile = ROOT . '/controllers/' . $controllerName . '.php';

                if(file_exists($controllerFile))
                {
                    include_once($controllerFile);
                    
                    $controllerObject = new $controllerName;
                
                    if(method_exists($controllerObject, $actionName))
                    {
                        $result = call_user_func_array(array($controllerObject, $actionName), $parameters);

                        if($result != null)
                        {
                            exit();
                        }
                    }
                }

                break;
            }
        }
        
        $this->error404();
        
        exit();
        
    }
    
    private function error404()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        include_once ROOT . '/errors/404.html';
        exit();
    }
}