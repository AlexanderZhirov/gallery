<?php

class MainController {
    
    public function actionIndex($xtpl) {
        
        $p = $_POST;
        
        if(isset($p['getimage']))
        {
            $count = $p['count'];
            
            echo json_encode(ViewImages::loadImages($count));
        
            exit();
        }
        
        if(User::isAuth())
        {
            $xtpl->parse('page.logout');
        }
        else
        {
            $xtpl->parse('page.login');
            $xtpl->parse('page.reg');
        }
        
        if(($images = ViewImages::getImages()) != false)
        {
            shuffle($images);
            $xtpl->array_loop('page.images.i', 'i', $images);
        }
        
        $xtpl->parse('page.images');
        $xtpl->parse('page');
        $xtpl->out('page');
        
        return true;
    }    
}
