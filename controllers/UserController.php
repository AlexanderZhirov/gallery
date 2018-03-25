<?php

class UserController {
    
    public function actionMyGallery($xtpl) {
        
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
        
        if(isset($p['getimage']))
        {
            $count = $p['count'];
            
            echo json_encode(ViewImages::myloadImages($count));
        
            exit();
        }
        
        if(($images = ViewImages::getMyImages()) != false)
        {
            shuffle($images);
            $xtpl->array_loop('page.mygallery.i', 'i', $images);
        }
        
        $xtpl->parse('page.mygallery');
        $xtpl->parse('page');
        $xtpl->out('page');
        
        return true;        
    }

    public function actionRegistration($xtpl) {
        
        if(User::isAuth())
        {
            header('Location: /');
            exit();
        }
        else
        {
            $xtpl->parse('page.login');
        }
        
        $p = $_POST;
        
        if(isset($p['submit']))
        {
            $login = $p['login'];
            $passwords = array($p['password_one'], $p['password_two']);
            $email = $p['email'];
            $name = $p['name'];
            
            if(($errors = User::userRegistration($login, $passwords, $email, $name)) === true)
            {
                $message = array('Вы были успешно зарегистрированы!',
                    'Для активации аккаунта на ваш E-mail было выслано письмо.');

                HelpLibrary::message($message, true);
            }
            else
            {
                HelpLibrary::errorMessage($errors, $p);
            }
            
            header('Location: /registration');
            exit();
        }
        
        $errors = HelpLibrary::errorMessage();
        $message = HelpLibrary::message();

        $xtpl->array_loop( 'page.registration.errors', 'errors', $errors['errors']);
        $xtpl->array_loop( 'page.registration.message', 'message', $message['message']);
        $xtpl->assign('p', $errors['post']);
        $xtpl->parse('page.registration');
        $xtpl->parse('page');
        $xtpl->out('page');
        
        return true;
    }
    
    public function actionActivation($login, $linkActivation)
    {
        if(User::isAuth())
        {
            header('Location: /');
            exit();
        }
        
        if(User::userActivated($login, $linkActivation))
        {
            HelpLibrary::message(array(
                "Учётная запись $login была успешно активирована!",
                'Теперь вы можете авторизоваться.'
            ));

            header('Location: /autorization');
            exit();
        }
        
        return false;
    }
    
    public function actionAutorization($xtpl) {
        
        if(User::isAuth())
        {
            header('Location: /');
            exit();
        }
        else
        {
            $xtpl->parse('page.reg');
        }
        
        $p = $_POST;
        
        if(isset($p['submit']))
        {
            $login = $p['login'];
            $password = $p['password'];
            $remember = isset($p['remember']) === true;
            
            if(($errors = User::userAuthorization($login, $password, $remember)) === true)
            {
                header('Location: /');
                exit();
            }
            
            HelpLibrary::errorMessage($errors, $p);
            
            header('Location: /autorization');
            exit();
        }
        
        $errors = HelpLibrary::errorMessage();
        $message = HelpLibrary::message();
        
        $xtpl->array_loop( 'page.autorization.message', 'message', $message['message']);
        $xtpl->array_loop( 'page.autorization.errors', 'errors', $errors['errors']);
        $xtpl->assign('p', $errors['post']);
        $xtpl->parse('page.autorization');
        $xtpl->parse('page');
        $xtpl->out('page');
        
        return true;
    }
    
    public function actionLogout() {
        
        if(!User::isAuth())
        {
            header('Location: /');
            exit();
        }
        
        if(User::userLogout())
        {
            header('Location: /');
            exit();
        }
        
        return true;
    }
    
    public function actionSettings($xtpl) {
        
        if(User::isAuth())
        {
            $xtpl->parse('page.logout');
        }
        else
        {
            header('Location: /');
            exit();
        }
        
        $s = $_SESSION['user'];
        $p = $_POST;
        
        if(isset($p['deletesession']) && $p['deletesession'] == true)
        {            
            $id = $p['id'];
            $userid = $p['userid'];

            echo json_encode(User::deleteSession($id, $userid));
        
            exit();
        }
        
        if(isset($p['submit']))
        {
            $data = array(
                'name' => $p['name'],
                'email' => $p['email'],
                'passwords' => array($p['password_one'], $p['password_two'])
            );
            
            if(isset($p['deleteemail']))
            {
                $data['deleteemail'] = $p['deleteemail'];
            }
            
            if(($errors = User::checkUserNewSettings($data, $s)) === true)
            {
                if(is_array($message = User::userSettingsChange($data, $s)))
                {
                    HelpLibrary::message($message);
                }
            }
            else
            {
                HelpLibrary::errorMessage($errors);
            }
            
            header("Location: /settings");
            exit();
        }
        
        if(($lastInAccount = User::userHistory($s['login'])) != false)
        {
            $s = array_merge($s, $lastInAccount);
        }
        
        if(($checkDevice = User::userCheckedDevice($s['id'], $s['cookie'])) != false)
        {
            $xtpl->array_loop('page.settings.mydevice.i', 'i', $checkDevice);
            $xtpl->parse('page.settings.mydevice');
        }
        
        $errors = HelpLibrary::errorMessage();
        $message = HelpLibrary::message();
        
        $xtpl->array_loop( 'page.settings.errors', 'errors', $errors['errors']);
        $xtpl->array_loop( 'page.settings.message', 'message', $message['message']);
        $xtpl->assign($s);
        if(isset($s['tempemail']))
        {
            $xtpl->parse( 'page.settings.tempemail');
        }        
        $xtpl->parse('page.settings');
        $xtpl->parse('page');
        $xtpl->out('page');
        
        return true;
        
    }
    
    public function actionRecovery($xtpl) {
        
        if(User::isAuth())
        {
            header('Location: /');
            exit();
        }
        else
        {
            $xtpl->parse('page.login');
            $xtpl->parse('page.reg');
        }
        
        $p = $_POST;
        
        if(isset($p['submit']))
        {
            $logmail = $p['logmail'];
            
            if(($errors = User::userRecovery($logmail)) === true)
            {
                HelpLibrary::message('Ссылка на восстановление пароля была выслана вам на E-mail!');
                
                header('Location: /autorization');
                exit();
            }
            
            HelpLibrary::errorMessage($errors, $p);
            header('Location: /autorization/recovery');
            exit();
        }
        
        $errors = HelpLibrary::errorMessage();
        
        $xtpl->array_loop( 'page.recoverypassword.errors', 'errors', $errors['errors']);
        $xtpl->assign($errors['post']);
        $xtpl->parse('page.recoverypassword');
        $xtpl->parse('page');
        $xtpl->out('page');
        
        return true;
        
    }
    
    public function actionChangePassword($login, $linkPassword, $xtpl) {
        
        if($result = User::checkLinkRecoveryPassword($login, $linkPassword))
        {
            if(User::isAuth())
            {
                header('Location: /');
                exit();
            }
            
            $p = $_POST;
        
            if(isset($p['submit']))
            {
                $passwords = array($p['password_one'], $p['password_two']);
                
                if(($errors = User::userRecoveryChangedPassword($login, $passwords)) === true)
                {
                    HelpLibrary::message(array(
                        'Пароль был успешно изменён!',
                        'Теперь вы можете авторизоваться с новым паролем.'
                    ));
                    
                    header('Location: /autorization');
                    exit();
                }
                
                HelpLibrary::errorMessage($errors, $p);
            
                header("Location: /userrecovery/$login/$linkPassword");
                exit();
            }
            
            $errors = HelpLibrary::errorMessage();
        
            $xtpl->array_loop( 'page.userrecovery.errors', 'errors', $errors['errors']);
            $xtpl->assign($errors['post']);
            $xtpl->parse('page.userrecovery');
            $xtpl->parse('page');
            $xtpl->out('page');
        }
        
        
        return $result;
        
    }
    
    public function actionChangeEmail($login, $linkEmail)
    {
        if(User::isAuth() && $_SESSION['user']['login'] == $login)
        {
            if($data = User::checkLinkChangeEmail($login, $linkEmail))
            {
                if(User::userEmailChange($data))
                {
                    HelpLibrary::message('E-mail был успешно изменён!');
                }
                else
                {
                    HelpLibrary::errorMessage('К сожалению, E-mail ' . $data['tempemail'] . ' уже был ранее зарегистрирован!');
                }

                header('Location: /settings');
                exit();
            }
        }
        
        return false;
    }
}
