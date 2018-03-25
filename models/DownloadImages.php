<?php

class DownloadImages {
    
    /*
     * Обработка загруженного избражения
     * Возврат массива с именем и форматом изображения
     * В случае ошибки возвращает false
     */
    public static function getNameImage($loadImage)
    {        
        if($result = $image = getimagesize($loadImage))
        {
            /*
            *  1 - IMAGETYPE_GIF
            *  2 - IMAGETYPE_JPEG
            *  3 - IMAGETYPE_PNG
            *  6 - IMAGETYPE_BMP
            */
            $imageTypes = array(2, 3, 6);

            // Проверка формата
            if($result = in_array($image[2], $imageTypes))
            {
                $imageTypeArray = array
                (
                    0 => 'unknown',
                    1 => 'gif',
                    2 => 'jpeg',
                    3 => 'png',
                    4 => 'swf',
                    5 => 'psd',
                    6 => 'bmp',
                    7 => 'tiff_ii',
                    8 => 'tiff_mm',
                    9 => 'jpc',
                    10 => 'jpx',
                    11 => 'jpx',
                    12 => 'jb2',
                    13 => 'swc',
                    14 => 'iff',
                    15 => 'wbmp',
                    16 => 'xbm',
                    17 => 'ico',
                    18 => 'count' 
                );

                $nameFile = $_SESSION['user']['login'] . date('_d-m-Y_H-m-s');
                $fileExtension = $imageTypeArray[$image[2]];
                $width = $image[0];
                $heigth = $image[1];        

                $result = array(
                    'fullname' => $nameFile . '.' . $fileExtension,
                    'name' => $nameFile,
                    'extension' => $fileExtension,
                    'width' => $width,
                    'heigth' => $heigth
                    );

                self::createSquareMiniImage($loadImage, $result);

            }
        }

        return $result;

    }
    
    public static function download($image, $description)
    {
        if(($fileName = self::getNameImage($image)) != false)
        {            
            if(move_uploaded_file($image, ROOT . '/upload/' . $fileName['fullname']))
            {                
                $userId = $_SESSION['user']['id'];
                
                $db = db::getConnection();
            
                try
                {
                    $sql = 'INSERT INTO images (name, description, date) VALUES ('
                            . ':fileName, :description, now())';
                    $s = $db->prepare($sql);
                    $s->bindValue(':fileName', $fileName['fullname']);
                    $s->bindValue(':description', $description);
                    $s->execute();
                }
                catch (PDOException $e)
                {
                    $error = 'Ошибка при добавлении в базу данных: ' . $e->getMessage();
                    HelpLibrary::write_log('users.log', $error);
                    exit();
                }
                
                $newIdImage = $db->lastInsertId();
                
                try
                {
                    $sql = 'INSERT INTO userimages (imageid, userid) VALUES ('
                            . ':imageId, :userId)';
                    $s = $db->prepare($sql);
                    $s->bindValue(':imageId', $newIdImage);
                    $s->bindValue(':userId', $userId);
                    $s->execute();
                }
                catch (PDOException $e)
                {
                    $error = 'Ошибка при добавлении в базу данных: ' . $e->getMessage();
                    HelpLibrary::write_log('users.log', $error);
                    exit();
                }

                $result = $newIdImage;
            }
            else
            {
                $result = 'Файл не был загружен на сервере';
            }
        }
        else
        {
            $result = 'Неверный формат загружаемого файла';
        }
        
        return $result;
    }
    
    public static function getImage($id)
    {
        $db = db::getConnection();
            
        try
        {
            $sql = 'SELECT id, name, description FROM images WHERE id = :id';
            $s = $db->prepare($sql);
            $s->bindValue(':id', $id);
            $s->execute();
        }
        catch (PDOException $e)
        {
            $error = 'Ошибка при получении изображения: ' . $e->getMessage();
            HelpLibrary::write_log('users.log', $error);
            exit();
        }
        
        $result = $s->fetch();
        
        return empty($result) ? false : $result;
    }


    /*
     * Создание квадратного изображения с разрешением $widthHeigth
     */
    private static function createSquareMiniImage($image, $fileInfo, $widthHeigth = 800)
    {
        if($widthHeigth < 100)
        {
            $widthHeigth = 500;
        }
        
        $miniImage = new Imagick($image);
        
        if($fileInfo['width'] > $fileInfo['heigth']) //Если маленькая высота
        {
            $ratio = $fileInfo['heigth'] / $widthHeigth;
            $width = round($fileInfo['width'] / $ratio);
            $heigth = $widthHeigth;
            $widthIndent = round($width / 2) - round($heigth / 2);
            $heigthIndent = 0;
        }
        elseif($fileInfo['width'] < $fileInfo['heigth']) //Если маленькая ширина
        {
            $ratio = $fileInfo['width'] / $widthHeigth;
            $width = $widthHeigth;
            $heigth = round($fileInfo['heigth'] / $ratio);
            $widthIndent = 0;
            $heigthIndent = round($heigth / 2) - round($width / 2);
        }
        else
        {
            $width = $widthHeigth;
            $heigth = $widthHeigth;
            $widthIndent = 0;
            $heigthIndent = 0;
        }
        
        $miniImage->thumbnailImage($width, $heigth, false);
        
        if($fileInfo['width'] != $fileInfo['heigth'])
        {
            $miniImage->cropImage($widthHeigth, $widthHeigth, $widthIndent, $heigthIndent);
        }
        
	$miniImage->writeImage(ROOT . '/upload/mini/' . $fileInfo['fullname']);
    }
}
