<?php

class User {
    
    /*
     *  Регистрация пользователя
     *  Возврат true или массив ошибок
     */
    public static function userRegistration($login, $passwords, $email, $name)
    {
        // Проверка логина/пароля/e-mail
        $checkLogin = self::checkLogin($login, true);
        $checkPassword = self::checkPasswords($passwords);
        $checkEmail = self::checkEmail($email, true);
        $checkName = self::checkName($name);
        
        if($checkLogin === true && $checkPassword === true && $checkEmail === true && $checkName === true)
        {
            $userData = self::getUserData($login);
            
            if(!$userData)
            {
                $userData = self::getUserData($email);
                
                if(!$userData)
                {
                    $password = password_hash($passwords[0], PASSWORD_DEFAULT);
                    $linkactivation = self::generateLink($login);

                    $db = db::getConnection();

                    try
                    {
                        $sql = 'INSERT INTO users (login, password, email, date, linkactivation, name) VALUES ('
                                . ':login, :password, :email, now(), :linkactivation, :name)';
                        $s = $db->prepare($sql);
                        $s->bindValue(':login', $login);
                        $s->bindValue(':password', $password);
                        $s->bindValue(':email', $email);
                        $s->bindValue(':name', $name);
                        $s->bindValue(':linkactivation', $linkactivation);
                        $s->execute();
                    }
                    catch (PDOException $e)
                    {
                        $error = 'Ошибка при регистрации нового пользователя: ' . $e->getMessage();
                        HelpLibrary::write_log('users.log', $error);
                        exit();
                    }

                    // Отправка письма для активации учётной записи
                    Mail::sendMessageRegistration($name, $login, $email, $linkactivation);
                    
                    $result = true;
                }
                else
                {
                    $result = "E-mail $email уже зарегистрирован!";
                }
            }
            else
            {
                $result = "Пользователь $login уже зарегистрирован!";
            }
        }
        else
        {
            $result = array();
            
            if(is_array($checkName))
            {
                $result = array_merge($result, $checkName);
            }
            
            if(is_array($checkLogin))
            {
                $result = array_merge($result, $checkLogin);
            }
            
            if(is_array($checkPassword))
            {
                $result = array_merge($result, $checkPassword);
            }
            
            if(is_array($checkEmail))
            {
                $result = array_merge($result, $checkEmail);
            }
        }
        
        return $result;
    }
    
    /*
     *  Активация пользователя
     *  Возврат true или массив ошибок
     */
    public static function userActivated($login, $linkActivation)
    {
        $patternLinkActivation = '/^[a-z0-9]{40}+$/';
        $patternLogin = '/^[a-zA-Z]{1}[a-zA-Z0-9]{4,29}+$/';

        if($result = (preg_match($patternLinkActivation, $linkActivation) && preg_match($patternLogin, $login)))
        {
            $db = db::getConnection();
        
            try
            {
                $sql = 'SELECT id FROM users WHERE login = :login AND linkactivation = :linkactivation';
                $s = $db->prepare($sql);
                $s->bindValue(':login', $login);
                $s->bindValue(':linkactivation', $linkActivation);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при проверке кода активации: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $row = $s->fetch();

            if($result = !empty($row['id']))
            {
                $db = db::getConnection();

                try
                {
                    $sql = 'UPDATE users SET linkactivation = null WHERE id = :id';
                    $s = $db->prepare($sql);
                    $s->bindValue(':id', $row['id']);
                    $s->execute();
                }
                catch (PDOException $e)
                {
                    $error = 'Ошибка при активации учётной записи: ' . $e->getMessage();
                    HelpLibrary::write_log('users.log', $error);
                    exit();
                }
            }
        }
        
        return $result;
    }
    
    /*
     *  Авторизация пользователя
     *  Параметр $remember - запомнить пользователя (создание cookie)
     *  Возврат true или массив ошибок
     */
    public static function userAuthorization($login, $password, $remember)
    {
        if(($result = self::checkLoginPassword($login, $password)) === true)
        {
            if($userData = self::getUserData($login))
            {                
                if(password_verify($password, $userData['password']))
                {
                    if(empty($userData['linkactivation']))
                    {
                        $clientInfo = self::getInfoClient();

                        $db = db::getConnection();

                        try
                        {
                            $sql = 'INSERT INTO users_auth (userid, browser, devicename, ip, date) VALUES (:userid, :browser, :devicename, :ip, now())';
                            $s = $db->prepare($sql);
                            $s->bindValue(':userid', $userData['id']);
                            $s->bindValue(':browser', $clientInfo['browser']);
                            $s->bindValue(':devicename', $clientInfo['devicename']);
                            $s->bindValue(':ip', $clientInfo['ip']);
                            $s->execute();
                        }
                        catch (PDOException $e)
                        {
                            $error = 'Ошибка при добавлении данных авторизации: ' . $e->getMessage();
                            HelpLibrary::write_log('users.log', $error);
                            exit();
                        }
                        
                        if($remember)
                        {
                            $lastInsertId = $db->lastInsertId(); //Текущая сессия
                            
                            $cookie = self::createUserCookie($userData['login']);
                            $userData['cookie'] = $cookie;

                            try
                            {
                                $sql = 'INSERT INTO users_cookie (authid, userid, cookie, date) VALUES (:authid, :userid, :cookie, now())';
                                $s = $db->prepare($sql);
                                $s->bindValue(':authid', $lastInsertId);
                                $s->bindValue(':userid', $userData['id']);
                                $s->bindValue(':cookie', $cookie);
                                $s->execute();
                            }
                            catch (PDOException $e)
                            {
                                $error = 'Ошибка при добавлении cookie при авторизации: ' . $e->getMessage();
                                HelpLibrary::write_log('users.log', $error);
                                exit();
                            }
                            
                            setcookie('user', $cookie, time() + 2678400, '/');
                        }
                        else
                        {
                            $userData['cookie'] = '';
                        }
                        
                        if(!empty($userData['linkpassword']))
                        {
                            $db = db::getConnection();
                            
                            try
                            {
                                $sql = 'UPDATE users SET linkpassword = null WHERE login = :login';
                                $s = $db->prepare($sql);
                                $s->bindValue(':login', $login);
                                $s->execute();
                            }
                            catch (PDOException $e)
                            {
                                $error = 'Ошибка при удалении ссылки на восстановление пароля: ' . $e->getMessage();
                                HelpLibrary::write_log('users.log', $error);
                                exit();
                            }
                        }
                        
                        $_SESSION['loggedIn'] = true;
                        $_SESSION['SESSID'] = session_id();
                        $_SESSION['user'] = $userData;
                    }
                    else
                    {
                        $result = "Пользователь $login ещё не активирован!";
                    }
                }
                else
                {
                    $result = 'Неправильный пароль!';
                }
            }
            else
            {
                $result = "Пользователь $login не зарегистрирован!";
            }
        }
        
        return $result;
    }
    
    /*
     *  Проверка авторизации пользователя
     *  Возврат true или false
     */
    public static function isAuth()
    {
        if($result = (isset($_SESSION['loggedIn']) && $_SESSION['SESSID'] == session_id()))
        {
            $db = db::getConnection();
            
            $user = $_SESSION['user'];
            
            $login = $user['login'];
            $password = $user['password'];
            
            try
            {
                $sql = 'SELECT * FROM users WHERE login = :login AND password = :password';
                $s = $db->prepare($sql);
                $s->bindValue(':login', $login);
                $s->bindValue(':password', $password);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при поиске пользователя по логину и паролю: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $row = $s->fetch();
            
            if(empty($row))
            {
                unset($_SESSION['loggedIn']);
                unset($_SESSION['user']);
                
                if(isset($_COOKIE['user']))
                {
                    setcookie('user', "", time() - 2678400, '/');
                }
                
                $result = false;
            }
            else
            {
                $_SESSION['user'] = $row;
                
                if(isset($_COOKIE['user']))
                {
                    $cookie = $_COOKIE['user'];
                    
                    try
                    {
                        $sql = 'SELECT * FROM users_cookie WHERE userid = :id AND cookie = :cookie';
                        $s = $db->prepare($sql);
                        $s->bindValue(':id', $row['id']);
                        $s->bindValue(':cookie', $cookie);
                        $s->execute();
                    }
                    catch (PDOException $e)
                    {
                        $error = 'Ошибка при поиске cookie пользователя: ' . $e->getMessage();
                        HelpLibrary::write_log('users.log', $error);
                        exit();
                    }
                    
                    $row = $s->fetch();
                    
                    if(empty($row))
                    {
                        $_SESSION['user']['cookie'] = '';
                        setcookie('user', "", time() - 2678400, '/');
                    }
                    else
                    {
                        $_SESSION['user']['cookie'] = $cookie;
                    }
                }
                else
                {
                    $_SESSION['user']['cookie'] = '';
                }
            }
        }
        elseif(isset($_COOKIE['user']))
        {
            $cookie = $_COOKIE['user'];

            $db = db::getConnection();
            
            try
            {
                $sql = 'SELECT u.id, u.login, u.password, u.email, u.name, u.date, u.linkactivation, u.linkpassword, u.linkemail, uc.cookie
                        FROM users as u
                        INNER JOIN users_cookie as uc
                        ON u.id = uc.userid
                        WHERE uc.cookie = :cookie';
                $s = $db->prepare($sql);
                $s->bindValue(':cookie', $cookie);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при поиске пользователя по cookie: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $row = $s->fetch();

            if($result = !empty($row))
            {                
                $_SESSION['loggedIn'] = true;
                $_SESSION['SESSID'] = session_id();
                $_SESSION['user'] = $row;
            }
            else
            {
                setcookie('user', "", time() - 2678400, '/');
                unset($_SESSION['loggedIn']);
                unset($_SESSION['SESSID']);
                unset($_SESSION['user']);
            }
        }
        
        return $result;
    }
    
    /*
     *  Выход из аккаунта и удаление cookie
     *  Возврат true или false
     */
    public static function userLogout()
    {
        
        if($result = self::isAuth())
        {
            if(isset($_COOKIE['user']))
            {
                $cookie = $_COOKIE['user'];
            }
            else
            {
                $cookie = $_SESSION['user']['cookie'];
            }
                
            $db = db::getConnection();

            try
            {
                $sql = 'DELETE FROM users_cookie WHERE userid = :userid AND cookie = :cookie';
                $s = $db->prepare($sql);
                $s->bindValue(':userid', $_SESSION['user']['id']);
                $s->bindValue(':cookie', $cookie);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при удалении пользователя из списка авторизации по логину и Cookie: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }
            
            setcookie('user', "", time() - 2678400, '/');
            unset($_SESSION['loggedIn']);
            unset($_SESSION['SESSID']);
            unset($_SESSION['user']);
        }
        
        return $result;        
    }
    
    /*
     *  Запрос на восстановление пароля по
     *  login || e-mail
     *  Возврат true или массив ошибок
     */
    public static function userRecovery($logmail)
    {
        $patternLogin = '/^[a-zA-Z]{1}[a-zA-Z0-9]{4,29}+$/';
        $patternEmail = '/^[\w\.\-]+@([\w\-]+\.)+[a-z]+$/i';
        
        if($logmail != '')
        {
            // Проверка, что введено - логин или пароль?
            if(($login = preg_match($patternLogin, $logmail)) || ($email = preg_match($patternEmail, $logmail)))
            {
                $db = db::getConnection();

                try
                {
                    if($login)
                    {
                        $sql = 'SELECT name, login, email FROM users WHERE login = :login';
                        $s = $db->prepare($sql);
                        $s->bindValue(':login', $logmail);
                    }
                    else
                    {
                        $sql = 'SELECT name, login, email FROM users WHERE email = :email';
                        $s = $db->prepare($sql);
                        $s->bindValue(':email', $logmail);
                    }

                    $s->execute();
                }
                catch (PDOException $e)
                {
                    $error = 'Ошибка при запросе на восстановление пароля: ' . $e->getMessage();
                    HelpLibrary::write_log('users.log', $error);
                    exit();
                }

                $row = $s->fetch();

                if($result = !empty($row))
                {
                    $login = $row['login'];
                    $email = $row['email'];
                    $name = $row['name'];

                    $linkPassword = self::generateLink($login);

                    try
                    {
                        $sql = 'UPDATE users SET linkpassword = :linkpassword WHERE login = :login';
                        $s = $db->prepare($sql);
                        $s->bindValue(':login', $login);
                        $s->bindValue(':linkpassword', $linkPassword);
                        $s->execute();
                    }
                    catch (PDOException $e)
                    {
                        $error = 'Ошибка при добавлении временной ссылки на смену пароля: ' . $e->getMessage();
                        HelpLibrary::write_log('users.log', $error);
                        exit();
                    }

                    Mail::sendMessageUserRecovery($name, $login, $email, $linkPassword);

                }
                else
                {
                    $result = $login ? "Пользователь $logmail не зарегистрирован!" : "Пользователь с E-mail $logmail не зарегистрирован!";
                }            
            }
            else
            {
                $result = 'Вы ввели неправильные данные!';
            }
        }
        else
        {
            $result = 'Введите логин или E-mail!';
        }
        
        return $result;
    }
    
    /*
     *  Проверка ссылки на восстановление пароля
     *  Возврат true или false
     */
    public static function checkLinkRecoveryPassword($login, $linkPassword)
    {
        $patternLinkPassword = '/^[a-z0-9]{40}+$/';
        $patternLogin = '/^[a-zA-Z]{1}[a-zA-Z0-9]{4,29}+$/';
        
        if($result = preg_match($patternLinkPassword, $linkPassword) && preg_match($patternLogin, $login))
        {
            $db = db::getConnection();
        
            try
            {
                $sql = 'SELECT id FROM users WHERE login = :login AND linkpassword = :linkpassword';
                $s = $db->prepare($sql);
                $s->bindValue(':login', $login);
                $s->bindValue(':linkpassword', $linkPassword);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при проверке ссылки на смену пароля: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }
        
            $row = $s->fetch();
            
            $result = empty($row['id']) ? false : true;
        }
        
        return $result;
    }
    
    /*
     *  Восстановление учётной записи - изменение пароля
     *  Возврат true или массив ошибок
     */
    public static function userRecoveryChangedPassword($login, $passwords)
    {
        if(($result = self::checkPasswords($passwords)) === true)
        {
            $db = db::getConnection();
        
            try
            {
                $sql = 'SELECT id, name, email FROM users WHERE login = :login';
                $s = $db->prepare($sql);
                $s->bindValue(':login', $login);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при получении email: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $row = $s->fetch();

            $email = $row['email'];
            $name = $row['name'];
            $password = password_hash($passwords[0], PASSWORD_DEFAULT);

            try
            {
                $sql = 'UPDATE users
                        SET password = :password, linkpassword = NULL
                        WHERE login = :login';
                $s = $db->prepare($sql);
                $s->bindValue(':login', $login);
                $s->bindValue(':password', $password);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при установке нового пароля: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            Mail::sendMessagePasswordChanged($name, $login, $email);

        }
        
        return $result;
    }
    
    /*
     *  Проверка ссылки на смену E-mail
     *  Возврат false или данных о пользователе
     */
    public static function checkLinkChangeEmail($login, $linkEmail)
    {
        $patternLinkEmail = '/^[a-z0-9]{40}+$/';
        $patternLogin = '/^[a-zA-Z]{1}[a-zA-Z0-9]{4,29}+$/';
        
        if($result = preg_match($patternLinkEmail, $linkEmail) && preg_match($patternLogin, $login))
        {
            $db = db::getConnection();
            
            try
            {
                $sql = 'SELECT * FROM users WHERE login = :login AND linkemail = :linkemail';
                $s = $db->prepare($sql);
                $s->bindValue(':login', $login);
                $s->bindValue(':linkemail', $linkEmail);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при получении данных на смену E-mail: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }
            
            $row = $s->fetch();
            
            $result = empty($row) ? false : $row;
        }
        
        return $result;
    }
    
    /*
     *  Изменение E-mail пользователя
     *  Возврат true или false
     */
    public static function userEmailChange($data)
    {
        $login = $data['login'];
        $tempEmail = $data['tempemail'];
        
        $db = db::getConnection();
        
        // Проверка на текущий момент нового E-mail среди зарегистрированных
        try
        {
            $sql = 'SELECT id, name FROM users WHERE email = :email';
            $s = $db->prepare($sql);
            $s->bindValue(':email', $tempEmail);
            $s->execute();
        }
        catch (PDOException $e)
        {
            $error = 'Ошибка при поиске E-mail на совпадение со сменяющимся: ' . $e->getMessage();
            HelpLibrary::write_log('users.log', $error);
            exit();
        }

        $row = $s->fetch();

        if(empty($row))
        {
            try
            {
                $sql = 'UPDATE users SET tempemail = null, linkemail = null, email = :email WHERE login = :login';
                $s = $db->prepare($sql);
                $s->bindValue(':email', $tempEmail);
                $s->bindValue(':login', $login);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при установки нового E-mail: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            Mail::sendMessageEmailChanged($data['name'], $login, $tempEmail);

            $result = true;
        }
        else
        {
            try
            {
                $sql = 'UPDATE users SET tempemail = null, linkemail = null WHERE login = :login';
                $s = $db->prepare($sql);
                $s->bindValue(':login', $login);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при удалении данных о сменяющемся E-mail: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }
            
            $result = false;
        }
        
        return $result;
    }
    
    /*
     *  Проверка новых настроек пользователя
     *  $newData - массив с данными в виде (username, email, passwords)
     *  $oldData - текущие данные пользователя из сессии
     *  Возврат true или массив ошибок
     */
    public static function checkUserNewSettings($newData, $oldData)
    {        
        $errors = array();
        
        if(!HelpLibrary::array_is_empty($newData['passwords']))
        {
            $checkPassword = self::checkPasswords($newData['passwords']);
            
            if(is_array($checkPassword))
            {
                $errors = array_merge($errors, $checkPassword);
            }
        }
        
        if(empty($newData['deleteemail']))
        {
            if(($checkEmail = self::checkEmail($newData['email'])) === true)
            {
                if($newData['email'] != $oldData['email'])
                {
                    if(self::getUserData($newData['email']))
                    {
                        $errors = array_merge($errors, array('E-mail ' . $newData['email'] . ' уже зарегистрирован!'));
                    }
                }
            }
            else
            {
                $errors = array_merge($errors, $checkEmail);
            }
        }

        return empty($errors) ? true : $errors;
    }
    
    /*
     *  Получение данных о последней авторизации пользователя
     *  Возврат false или строки в виде информации о последней авторизации на сайте
     */
    public static function userHistory($login)
    {
        $db = db::getConnection();
        
        try
        {
            $sql = 'SELECT DATE_FORMAT(users_auth.date, "%T %d.%m.%Y") as date, users_auth.browser, users_auth.devicename, users_auth.ip FROM users_auth
                    INNER JOIN users
                    ON users_auth.userid = users.id
                    WHERE users.login = :login ORDER BY users_auth.id DESC LIMIT 1,1';
            $s = $db->prepare($sql);
            $s->bindValue(':login', $login);
            $s->execute();
        }
        catch (PDOException $e)
        {
            $error = 'Ошибка при извлечении последнего входа в аккаунт: ' . $e->getMessage();
            HelpLibrary::write_log('users.log', $error);
            exit();
        }

        $row = $s->fetch();
        
        if(empty($row))
        {
            $result = false;
        }
        else
        {
            $result = array(
                'lastInAccount' => 'Последний вход в ' . $row['date'] . ' с браузера ' . $row['browser'] . ' на устройстве ' . $row['devicename'] . ' (' . $row['ip'] . ')'
            );
        }
        
        return $result;
    }
    
    /*
     *  Получение информации об авторизации с сохранением кук
     *  Возврат false или массива с записями об авторизованных устройствах
     */
    public static function userCheckedDevice($userid, $cookie)
    {
        $db = db::getConnection();
        
        try
        {
            if(isset($cookie))
            {
                $sql = 'SELECT uc.id, ua.userid, ua.browser, ua.devicename, ua.ip FROM users_cookie as uc
                        INNER JOIN users_auth as ua
                        ON uc.authid = ua.id
                        WHERE uc.userid = :userid AND uc.cookie <> :cookie ORDER BY uc.date';
                $s = $db->prepare($sql);
                $s->bindValue(':userid', $userid);
                $s->bindValue(':cookie', $cookie);
            }
            else
            {
                $sql = 'SELECT uc.id, ua.userid, ua.browser, ua.devicename, ua.ip FROM users_cookie as uc
                        INNER JOIN users_auth as ua
                        ON uc.authid = ua.id
                        WHERE uc.userid = :userid';
                $s = $db->prepare($sql);
                $s->bindValue(':userid', $userid);
            }
            
            $s->execute();
        }
        catch (PDOException $e)
        {
            $error = 'Ошибка при данных об авторизованных устройствах: ' . $e->getMessage();
            HelpLibrary::write_log('users.log', $error);
            exit();
        }

        $row = $s->fetchAll();
        
        if(empty($row))
        {
            $result = false;
        }
        else
        {
            $result = array();
            
            foreach ($row as $value) {
                array_push($result, array(
                    'id' => $value['id'],
                    'userid' => $value['userid'],
                    'browser' => $value['browser'],
                    'devicename' => $value['devicename'],
                    'ip' => $value['ip']
                ));
            }
        }
        
        return $result;
    }
    
    /*
     *  Изменение настроек пользователя
     *  $data - массив с данными в виде (avatar, username, email, passwords)
     *  $oldData - текущие данные пользователя из сессии
     *  Возврат true или массив сообщений об изменениях настроек
     */
    public static function userSettingsChange($data, $session)
    {
        $arrayUpdate = array();
        $message = array();

        if(!HelpLibrary::array_is_empty($data['passwords']))
        {
            $password = password_hash($data['passwords'][0], PASSWORD_DEFAULT);
            $arrayUpdate = array_merge($arrayUpdate, array(
                ':password' => $password
            ));
            
            $_SESSION['user']['password'] = $password;
            
            Mail::sendMessagePasswordChanged($session['name'], $session['login'], $session['email']);
            $message = array_merge($message, array(
                'Пароль был успешно изменён!'
            ));
        }
        else
        {
            $arrayUpdate = array_merge($arrayUpdate, array(
                ':password' => $session['password'],
            ));
        }

        if(isset($data['deleteemail']) && $session['tempemail'])
        {
            $arrayUpdate = array_merge($arrayUpdate, array(
                ':tempemail' => null,
                ':linkemail' => null
            ));
            
            $message = array_merge($message, array(
                'Запрос о смене E-mail отменён!'
            ));
        }
        else
        {
            if($data['email'] != $session['email'])
            {
                $linkEmail = self::generateLink($session['login']);
                $arrayUpdate = array_merge($arrayUpdate, array(
                    ':tempemail' => $data['email'],
                    ':linkemail' => $linkEmail
                ));

                Mail::sendMessageEmailChange($session['name'], $session['login'], $session['email'], $linkEmail);
                $message = array_merge($message, array(
                    'Подтверждение на смену E-mail было отправлено на вашу текущую электронную почту!'
                ));
            }
            else
            {
                $arrayUpdate = array_merge($arrayUpdate, array(
                    ':tempemail' => $session['tempemail'],
                    ':linkemail' => $session['linkemail']
                ));
            }
        }

        if($data['name'] != $session['name'])
        {
            $arrayUpdate = array_merge($arrayUpdate, array(
                ':name' => $data['name']
            ));
            $message = array_merge($message, array(
                'Новое имя установлено!'
            ));
        }
        else
        {
            $arrayUpdate = array_merge($arrayUpdate, array(
                ':name' => $session['name']
            ));
        }

        $db = db::getConnection();

        try
        {
            $sql = 'UPDATE users SET '
                    . 'password = :password, '
                    . 'tempemail = :tempemail, '
                    . 'linkemail = :linkemail, '
                    . 'name = :name '
                    . 'WHERE login = :login';
            $s = $db->prepare($sql);
            foreach ($arrayUpdate as $key => $value) {
                $s->bindValue($key, $value);
            }
            $s->bindValue(':login', $session['login']);
            $s->execute();
        }
        catch (PDOException $e)
        {
            $error = 'Ошибка при изменении настроек пользователя: ' . $e->getMessage();
            HelpLibrary::write_log('users.log', $error);
            exit();
        }
        
        return empty($message) ? true : $message;
    }
    
    /*
     *  Удаление авторизованных устройств (куки)
     *  Возврат true или false
     */
    public static function deleteSession($id, $userid)
    {
        $s = $_SESSION['user'];
        
        if($result = $s['id'] == $userid)
        {
            $db = db::getConnection();
            
            try
            {
                $sql = 'SELECT id FROM users_cookie
                        WHERE userid = :userid AND id = :id';
                $s = $db->prepare($sql);
                $s->bindValue(':userid', $userid);
                $s->bindValue(':id', $id);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при авторизованного устройства: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $row = $s->fetch();

            if(!empty($row))
            {
                try
                {
                    $sql = 'DELETE FROM users_cookie
                            WHERE id = :id';
                    $s = $db->prepare($sql);
                    $s->bindValue(':id', $row['id']);
                    $s->execute();
                }
                catch (PDOException $e)
                {
                    $error = 'Ошибка при удалении авторизованного устройства: ' . $e->getMessage();
                    HelpLibrary::write_log('users.log', $error);
                    exit();
                }
            }
            else
            {
                $result = false;
            }        
        }
        
        return $result;
    }
    
    /*
     * ПРИВАТНЫЕ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
     */
    
    /*
     *  Проверка логина на валидность
     *  Возврат true или массив ошибок
     */
    private static function checkLogin($login)
    {
        $errors = array();
        
        if(empty($login))
        {
            array_push($errors, 'Укажите логин!');
        }
        
        $pattern = '/^[a-zA-Z]{1}[a-zA-Z0-9]+$/';
        
        if(!preg_match($pattern, $login))
        {
            $errors = array_merge($errors, array(
                'Логин должен состоять из букв латинского алфавита и цифр!',
                'Логин должен начинаться с буквы!'));
        }
        
        if(strlen($login) > 30)
        {
            array_push($errors, 'Логин должен быть не более 30 символов!');
        }
        
        if(strlen($login) < 5)
        {
            array_push($errors, 'Логин должен быть не менее 5 символов!');
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /*
     *  Проверка имени на валидность
     *  Возврат true или массив ошибок
     */
    private static function checkName($name)
    {
        $errors = array();
        
        if(empty($name))
        {
            array_push($errors, 'Укажите своё имя!');
        }
        
        $pattern = '/^([а-яА-Я]+)|([a-zA-Z]+)$/u'; //Кириллица не пашет
        
        if(!preg_match($pattern, $name))
        {
            $errors = array_merge($errors, array(
                'Имя должно состоять из букв латинского или русского алфавита!'));
        }
        
        if(strlen($name) > 30)
        {
            array_push($errors, 'Имя должно быть не более 30 символов!');
        }
        
        if(strlen($name) < 3)
        {
            array_push($errors, 'Имя должно быть не менее 3 символов!');
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /*
     *  Проверка E-mail на валидность
     *  Возврат true или массив ошибок
     */
    private static function checkEmail($email)
    {
        $errors = array();
        
        if(empty($email))
        {
            array_push($errors, 'Укажите E-mail!');
        }
        
        $pattern = '/^[\w\.\-]+@([\w\-]+\.)+[a-z]+$/i';
        
        if(!preg_match($pattern, $email))
        {
            array_push($errors, 'E-mail должен соответствовать стандарту!');
        }
        
        return empty($errors) ? true : $errors;
    }

    /*
     *  Проверка пароля на валидность
     *  Возврат true или массив ошибок
     */
    private static function checkPassword($password)
    {
        $errors = array();
        
        if(empty($password))
        {
            array_push($errors, 'Укажите пароль!');
        }
        
        if(strlen($password) < 8)
        {
            array_push($errors, 'Пароль должен быть не менее 8 символов!');
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /*
     *  Проверка пароля из двух полей
     *  Возврат true или массив ошибок
     */
    private static function checkPasswords($passwords)
    {
        $errors = array();
           
        if(empty($passwords[0]))
        {
            array_push($errors, 'Укажите пароль!');
        }

        if(strlen($passwords[0]) < 8)
        {
            array_push($errors, 'Пароль должен быть не менее 8 символов!');
        }

        if($passwords[0] != $passwords[1])
        {
            array_push($errors, 'Введённые пароли не совпадают!');
        }
        
        return empty($errors) ? true : $errors;
    }

    /*
     *  Получение данных о пользователе по
     *  login || id || e-mail
     *  Возврат false или массив данных о пользователе
     */
    private static function getUserData($loginIdEmail)
    {
        $patternEmail = '/^[\w\.\-]+@([\w\-]+\.)+[a-z]+$/i';
        
        $db = db::getConnection();

        try
        {
            if(is_numeric($loginIdEmail))
            {
                $sql = 'SELECT * FROM users WHERE id = :id';
                $s = $db->prepare($sql);
                $s->bindValue(':id', $loginIdEmail);
            }
            elseif(preg_match($patternEmail, $loginIdEmail))
            {
                $sql = 'SELECT * FROM users WHERE email = :email';
                $s = $db->prepare($sql);
                $s->bindValue(':email', $loginIdEmail);
            }
            else
            {
                $sql = 'SELECT * FROM users WHERE login = :login';
                $s = $db->prepare($sql);
                $s->bindValue(':login', $loginIdEmail);
            }

            $s->execute();
        }
        catch (PDOException $e)
        {
            $error = 'Ошибка при запросе данных пользователя: ' . $e->getMessage();
            HelpLibrary::write_log('users.log', $error);
            exit();
        }

        $row = $s->fetch();
        
        return empty($row) ? false : $row;
    }
    
    /*
     *  Проверка логина и пароля(паролей) вместе
     *  Возврат true или массив ошибок
     */
    private static function checkLoginPassword($login, $password)
    {
        $checkLogin = self::checkLogin($login);
        $checkPassword = is_array($password) ? self::checkPasswords($password) : self::checkPassword($password);
        
        $errors = array();
        
        if(is_array($checkLogin))
        {
            $errors = array_merge($errors, $checkLogin);
        }
        
        if(is_array($checkPassword))
        {
            $errors = array_merge($errors, $checkPassword);
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /*
     *  Данный о клиенте пользователя
     *  Возврат массив с данными о клиенте пользователя
     */
    private static function getInfoClient()
    {
        $browser = get_browser(null, true);
        
        $clientInfo = array(
            'ip' => $_SERVER['REMOTE_ADDR'],
            'browser' => $browser['browser'],
            'devicename' => $browser['device_name']
        );
        
        return $clientInfo;
    }
    
    /*
     *  Создание cookie пользователя
     *  Проверка cookie на совпадение в БД
     *  Возврат зашифрованной строки
     */
    private static function createUserCookie($login)
    {
        do
        {
            $cookie = self::generationUserCookie($login);
            
            $db = db::getConnection();
        
            try
            {
                $sql = 'SELECT * FROM users_cookie WHERE cookie = :cookie';
                $s = $db->prepare($sql);
                $s->bindValue(':cookie', $cookie);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при поиске cookie: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $row = $s->fetch();
        
        }
        while(!empty($row));
        
        return $cookie;
    }
    
    /*
     *  Генерирование рандомного cookie
     *  Возврат зашифрованной строки
     */
    private static function generationUserCookie($login)
    {
        $randomString = HelpLibrary::stringGenerate(8);
        $leftRightLogin = self::splitLogin($login);
        $userCookie = hash('ripemd160', $leftRightLogin['left'] . $randomString . $leftRightLogin['right']);
        return $userCookie;
    }
    
    /*
     *  Разбиение логина пополам
     *  Возврат массива left/right с частями логина
     */
    private static function splitLogin($login)
    {
        $count = iconv_strlen($login);
        $halfName = round($count/2) - 1;
        $twoPartsOfTheLogin['left'] = mb_substr($login, 0, $halfName);
        $twoPartsOfTheLogin['right'] = mb_substr($login, $halfName);
        return $twoPartsOfTheLogin;
    }
    
    /*
     *  Генерация рандомной ссылки
     *  Возврат зашифрованной строки
     */
    private static function generateLink($login)
    {
        do
        {
            $randomString = HelpLibrary::stringGenerate(8);
            
            $link = hash('ripemd160', $login . $randomString);
            
            $db = db::getConnection();
        
            try
            {
                $sql = 'SELECT id FROM users WHERE '
                        . 'linkactivation = :link OR '
                        . 'linkpassword = :link OR '
                        . 'linkemail = :link';
                $s = $db->prepare($sql);
                $s->bindValue(':link', $link);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при поиске пути: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $row = $s->fetch();
        
        }
        while (!empty($row));
        
        return $link;
    }
    
}
