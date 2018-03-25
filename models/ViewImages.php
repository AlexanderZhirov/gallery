<?php

class ViewImages {

    public static function getMyImages()
    {
        $userId = $_SESSION['user']['id'];
        
        $db = db::getConnection();
        
        try
        {
            $sql = 'SELECT id, name, description
                        FROM images
                        INNER JOIN userimages
                        ON images.id = userimages.imageid
                        WHERE userimages.userid = :id order by id desc limit 9';
            $s = $db->prepare($sql);
            $s->bindValue(':id', $userId);
            $s->execute();
        }
        catch (PDOException $e)
        {
            $error = 'Ошибка при извлечении из базы данных: ' . $e->getMessage();
            HelpLibrary::write_log('users.log', $error);
            exit();
        }

        $result = $s->fetchAll();

        return empty($result) ? false : $result;
        
    }

    public static function getImages($id = null)
    {
        $db = db::getConnection();
        
        if(isset($id))
        {
            try
            {
                $sql = 'select id, name, description from images where id = :id';
                $s = $db->prepare($sql);
                $s->bindValue(':id', $id);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при извлечении из базы данных: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $result = $s->fetch();
        }
        else
        {
            try
            {
                $sql = 'select id, name, description from images order by id desc limit 9';
                $s = $db->query($sql);
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при извлечении из базы данных: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $result = $s->fetchAll();
        }

        return empty($result) ? false : $result;
    }
    
    public static function loadImages($count)
    {
        $db = db::getConnection();

        try
        {
            $sql = 'select id, name, description from images order by id desc limit :count, 6';
            $s = $db->prepare($sql);
            $s->bindValue(':count', (int)$count, PDO::PARAM_INT);
            $s->execute();
        }
        catch (PDOException $e)
        {
            $error = 'Ошибка при извлечении из базы данных: ' . $e->getMessage();
            HelpLibrary::write_log('users.log', $error);
            exit();
        }

        return $s->fetchAll();
    }
    
    public static function myloadImages($count)
    {
        $userId = $_SESSION['user']['id'];
        
        $db = db::getConnection();

        try
        {
            $sql = 'SELECT id, name, description
                        FROM images
                        INNER JOIN userimages
                        ON images.id = userimages.imageid
                        WHERE userimages.userid = :id order by id desc limit :count, 6';
            $s = $db->prepare($sql);
            $s->bindValue(':id', $userId);
            $s->bindValue(':count', (int)$count, PDO::PARAM_INT);
            $s->execute();
        }
        catch (PDOException $e)
        {
            $error = 'Ошибка при извлечении из базы данных: ' . $e->getMessage();
            HelpLibrary::write_log('users.log', $error);
            exit();
        }

        return $s->fetchAll();
    }
    
    public static function deleteImage($idImages)
    {
        if(self::itsMyImage($idImages))
        {
            
            $db = db::getConnection();
            
            try
            {
                $sql = 'SELECT name FROM images WHERE id = :id';
                $s = $db->prepare($sql);
                $s->bindValue(':id', $idImages);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при извлечении из базы данных: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $nameImages = $s->fetch();
        
            $path = ROOT . '/upload/' . $nameImages['name'];
            $pathMiniImage = ROOT . '/upload/mini/' . $nameImages['name'];
            
            if(file_exists($path))
            {
                unlink($path);
            }
            
            if(file_exists($pathMiniImage))
            {
                unlink($pathMiniImage);
            }
            
            try
            {
                $sql = 'DELETE FROM images WHERE id = :id';
                $s = $db->prepare($sql);
                $s->bindValue(':id', $idImages);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при удалении изображения из базы данных: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }
            
            try
            {
                $sql = 'DELETE FROM userimages WHERE imageid = :id';
                $s = $db->prepare($sql);
                $s->bindValue(':id', $idImages);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при удалении изображения из связанной таблицы в базе данных: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }
            
            $result = true;            
        }
        else
        {
            $result = false;
        }
        
        return $result;        
    }
    
    public static function itsMyImage($idImages)
    {
        if(User::isAuth())
        {
        
            $userId = $_SESSION['user']['id'];

            $db = db::getConnection();

            try
            {
                $sql = 'SELECT userid
                            FROM userimages
                            WHERE imageid = :id';
                $s = $db->prepare($sql);
                $s->bindValue(':id', $idImages);
                $s->execute();
            }
            catch (PDOException $e)
            {
                $error = 'Ошибка при извлечении из базы данных: ' . $e->getMessage();
                HelpLibrary::write_log('users.log', $error);
                exit();
            }

            $user = $s->fetch();
            
            $result = ($userId == $user['userid'] ? true : false);
        }
        else
        {
            $result = false;
        }
        
        return $result;        
    }
    
}
