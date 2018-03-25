<?php

class ViewController {
    
    public function actionView($idImages, $myGallery, $xtpl) {
        
        if(User::isAuth())
        {
            $xtpl->parse('page.logout');
        }
        else
        {
            $xtpl->parse('page.reg');
            $xtpl->parse('page.login');
        }
        
        $p = $_POST;
        
        if(isset($p['submit']))
        {
            
            $idImages = $p['image'];
            
            ViewImages::deleteImage($idImages);
            
            header('Location: /mygallery');
            exit();
        }
        
        if($result = ($images = ViewImages::getImages($idImages)) != false)
        {
            if(ViewImages::itsMyImage($idImages))
            {
                $xtpl->insert_loop('page.image.delete', 'i', $images);
                $xtpl->insert_loop('page.image', 'i', $images);
            }
            elseif((bool)$myGallery)
            {
                $xtpl->parse('page.emptyimage.dontmy');
                $xtpl->parse('page.emptyimage');
            }
            else
            {
                $xtpl->insert_loop('page.image', 'i', $images);
            }
        }
        else
        {
            $xtpl->parse('page.emptyimage.none');
            $xtpl->parse('page.emptyimage');
        }
        
        $xtpl->parse('page');
        $xtpl->out('page');
        
        return true;
    }
    
}
