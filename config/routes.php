<?php

return [
    'download' => 'download/DownloadImages',
    'autorization/recovery' => 'user/recovery',
    'userrecovery/([a-zA-Z0-9]+)/([a-z0-9]+)' => 'user/changePassword/$1/$2',
    'autorization' => 'user/autorization',
    'registration' => 'user/registration',
    'activation/([a-zA-Z0-9]+)/([a-z0-9]+)' => 'user/activation/$1/$2',
    'logout' => 'user/logout',
    'settings/changeemail/([a-zA-Z0-9]+)/([a-z0-9]+)' => 'user/changeEmail/$1/$2',
    'settings' => 'user/settings',
    'mygallery/([0-9]+)' => 'view/view/$1/1', //единица на конце преобразуется в true, говорящая о том, что мы находимся в своей галереи
    'mygallery' => 'user/myGallery',
    '([0-9]+)' => 'view/view/$1/0', //нуль на конце преобразуется в false, говорящий о том, что мы находимся на главной странице просмотра изображений
    '' => 'main/index'
    ];