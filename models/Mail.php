<?php

class Mail {
    
    // Отправка письма для активации учетной записи
    public static function sendMessageRegistration($name, $login, $email, $activation)
    {        
        $textReplace = array('name' => $name,'login' => $login, 'activation' => $activation);
        
        $xtpl = new XTemplate('activateregistration.html', ROOT . '/views/mail');
        $xtpl->assign($textReplace);
        $xtpl->parse('page');
        $to = $email;
        $subject = "Активация учётной записи";
        $message = $xtpl->out_text('page');
        $headers = "From: zhirov.su <alexander@zhirov.su>\r\n";
        $headers .= "Content-type: text/html; charset='utf-8'\r\n";

        return mail($to, $subject, $message, $headers);
    }
    
    // Отправка письма запроса смены пароля
    public static function sendMessagePasswordChanged($name, $login, $email)
    {        
        $textReplace = array('name' => $name,'login' => $login);
        
        $xtpl = new XTemplate('passwordchanged.html', ROOT . '/views/mail');
        $xtpl->assign($textReplace);
        $xtpl->parse('page');
        $to = $email;
        $subject = "Изменение пароля";
        $message = $xtpl->out_text('page');
        $headers = "From: zhirov.su <alexander@zhirov.su>\r\n";
        $headers .= "Content-type: text/html; charset='utf-8'\r\n";

        return mail($to, $subject, $message, $headers);
    }
    
    // Отправка письма с новым паролем
    public static function sendMessageUserRecovery($name, $login, $email, $linkPassword)
    {        
        $textReplace = array('name' => $name, 'login' => $login, 'linkPassword' => $linkPassword);
        
        $xtpl = new XTemplate('userrecovery.html', ROOT . '/views/mail');
        $xtpl->assign($textReplace);
        $xtpl->parse('page');
        $to = $email;
        $subject = "Восстановление пароля";
        $message = $xtpl->out_text('page');
        $headers = "From: zhirov.su <alexander@zhirov.su>\r\n";
        $headers .= "Content-type: text/html; charset='utf-8'\r\n";

        return mail($to, $subject, $message, $headers);
    }
    
    // Отправка письма запроса смены E-mail
    public static function sendMessageEmailChange($name, $login, $email, $linkEmail)
    {        
        $textReplace = array('name' => $name, 'login' => $login, 'linkEmail' => $linkEmail);
        
        $xtpl = new XTemplate('changeemail.html', ROOT . '/views/mail');
        $xtpl->assign($textReplace);
        $xtpl->parse('page');
        $to = $email;
        $subject = "Смена E-mail";
        $message = $xtpl->out_text('page');
        $headers = "From: zhirov.su <alexander@zhirov.su>\r\n";
        $headers .= "Content-type: text/html; charset='utf-8'\r\n";

        return mail($to, $subject, $message, $headers);
    }
    
    // Отправка письма о подтверждении смены E-mail
    public static function sendMessageEmailChanged($name, $login, $email)
    {        
        $textReplace = array('name' => $name, 'login' => $login);
        
        $xtpl = new XTemplate('changedemail.html', ROOT . '/views/mail');
        $xtpl->assign($textReplace);
        $xtpl->parse('page');
        $to = $email;
        $subject = "Смена E-mail";
        $message = $xtpl->out_text('page');
        $headers = "From: zhirov.su <alexander@zhirov.su>\r\n";
        $headers .= "Content-type: text/html; charset='utf-8'\r\n";

        return mail($to, $subject, $message, $headers);
    }
    
}
