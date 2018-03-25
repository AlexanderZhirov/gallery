<?php

class DownloadController {
    
    public function actionDownloadImages($xtpl)
    {
        
        if(User::isAuth())
        {
            $xtpl->parse('page.logout');
        }
        else
        {
            header('Location: /');
            exit();
        }
        
        $p = $_POST;        
        
        if(isset($p['submit']))
        {
            if(isset($_FILES['image']) && $_FILES['image']['error'] == 0)
            {
                $image = $_FILES['image']['tmp_name'];
                $description = $p['description'];

                if(is_numeric($result = DownloadImages::download($image, $description)) && ($imageInfo = DownloadImages::getImage($result)) != false)
                {
                   $message = array(
                       'image' => $imageInfo,
                       'message' => 'Изображение было успешно загружено!',
                       'result' => 1
                       );                   
//
//                   unset ($_SESSION['errorMessage']);
//                   HelpLibrary::message($message);
//
//                   header('Location: /download');
//                   exit();
                }
                else
                {
                    $message = array(
                       'message' => $result,
                       'result' => 0
                       );
                }
                
//                HelpLibrary::errorMessage($errors);
//            
//                header('Location: /download');
//                exit();
            }
            else
            {
//                header('Location: /download');
//                exit();
                $message = array(
                       'message' => 'Изображение не было выбрано!',
                       'result' => 0
                       );
            }
            
            echo json_encode($message);
            exit();
        }
    
        $message = HelpLibrary::message();
        $errors = HelpLibrary::errorMessage();
        
        $xtpl->array_loop( 'page.download.message', 'message', $message['message']);
        $xtpl->array_loop( 'page.download.errors', 'errors', $errors['errors']);
        
        $xtpl->parse('page.download');
        $xtpl->parse('page');
        $xtpl->out('page');
        
        return true;
    }
}
