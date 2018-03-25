<?php

class HelpLibrary {
   
    /*
     * Запись логов
     */
    public static function write_log($log_name, $text)
    {
        $log_name = ROOT . '/logs/' . $log_name;
        $file = fopen($log_name, 'a');
        $text = date('m.d.Y, G:i:s') . ' -> ' . $text . "\n";
        fwrite($file, $text);
        fclose($file);
    }
    
    /*
     * Отладка переменных/массива/объекта
     */
    public static function dumper($value)
    {
        echo '<style>div.development{background-color: #8deefc; padding: 3px 0px 3px 20px; line-height: 1.2em; font-size: 1.2em;}</style>'
        . '<div class="development"><h3>ОТЛАДКА</h3><pre>'
        . htmlspecialchars(self::dumperGet($value))
        . '</pre></div><br>';
    }
    
    private static function dumperGet(&$value, $leftSp = "")
    {
        if(is_array($value))
        {
            $type = "Array[" . count($value) . "]";
        }
        elseif(is_object($value))
        {
            $type = "Object";
        }
        elseif(gettype($value) == "boolean")
        {
            return $value ? "true" : "false";
        }
        else
        {
            return "\"$value\"";
        }
        
        $buf = $type;
        
        $leftSp .= "    ";
        
        for(reset($value); list($k, $v) = each($value);)
        {
            if($k === "GLOBALS") continue;
            $buf .= "\n$leftSp$k => " . self::dumperGet($v, $leftSp);
        }
        
        return $buf;
    }
    
    public static function errorMessage($errorsArray = null, $postArray = null)
    {
        if(isset($errorsArray))
        {
            // Блок установки сообщения об ошибке
            
            $_SESSION['errorMessage'] = array(
                'errors' => '',
                'post' => '',
                'path' => $_SERVER['REQUEST_URI']
            );
            
            if(is_array($errorsArray))
            {
                $_SESSION['errorMessage']['errors'] = $errorsArray;
            }
            else
            {
                $_SESSION['errorMessage']['errors'] = array($errorsArray);
            }
            
            if(isset($postArray))
            {
                if(is_array($postArray))
                {
                    $_SESSION['errorMessage']['post'] = $postArray;
                }
                else
                {
                    $_SESSION['errorMessage']['post'] = array($postArray);
                }
            }
            
            $result = true;
        }
        else
        {

            // Блок возврата ошибки
            
            if(!empty($_SESSION['errorMessage']))
            {
                // Шаблон для проверки
                $pattern = '~' . $_SESSION['errorMessage']['path'] . '~';
                
                if(preg_match($pattern, $_SERVER['REQUEST_URI']))
                {
                    if(isset($_SESSION['errorMessage']['errors']))
                    {
                        $session['errors'] = $_SESSION['errorMessage']['errors'];
                        unset ($_SESSION['errorMessage']['errors']);
                    }
                    
                    $session['post'] = $_SESSION['errorMessage']['post'];
                    
                    $result = $session;
                }
                else
                {
                    $result = array('errors' => '', 'post' => '');
                    unset ($_SESSION['errorMessage']);
                }
            }
            else
            {
                $result = array('errors' => '', 'post' => '');
            }
        }
        
        return $result;
        
    }
    
    public static function clearErrorMessage()
    {
        if(!empty($_SESSION['errorMessage']))
        {
            if($_SESSION['errorMessage']['path'] != $_SERVER['REQUEST_URI'])
            {
                unset ($_SESSION['errorMessage']);
            }
        }
    }
    
    public static function message($messageArray = null, $clearErrorMessage = false)
    {
        if(isset($messageArray))
        {
            if(is_array($messageArray))
            {
                $_SESSION['message'] = $messageArray;
            }
            else
            {
                $_SESSION['message'] = array($messageArray);
            }
            
            if($clearErrorMessage)
            {
                unset ($_SESSION['errorMessage']);
            }
            
            $result = true;
        }
        else
        {
            if(!empty($_SESSION['message']))
            {
                $session['message'] = $_SESSION['message'];
                unset ($_SESSION['message']);
                if($clearErrorMessage)
                {
                    unset ($_SESSION['errorMessage']);
                }
                $result = $session;
            }
            else
            {
                $result = array('message' => '');
            }
        }
        
        return $result;
        
    }

    public static function stringGenerate($length)
    {
        $tableChar = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
            'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',
            'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
            'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F',
            'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
            'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
            'W', 'X', 'Y', 'Z', '1', '2', '3', '4',
            '5', '6', '7', '8', '9', '0'
        );
        
        $string = '';
        
        for($i = 0; $i < $length; $i++)
        {
            $index = rand(0, count($tableChar) - 1);
            $string .= $tableChar[$index];
        }
        
        return $string;
    }
    
    /*
     *  Проверка массива и его элементов на пустоту
     *  Возврат true или false
     */
    public static function array_is_empty($array)
    {
        if(is_array($array))
        {
            if(empty($array))
            {
                $result = true;
            }
            else
            {
                foreach($array as $element)
                {
                    $result = self::array_is_empty($element);
                    if($result == false) break;
                }
            }
        }
        else
        {
            $result = empty($array);
        }
        
        return $result;
    }
    
}
