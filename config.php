<?php
return [
    'id' => 'dvelum-module-designer',
    'version' => '1.5.5',
    'author' => 'Kirill Yegorov',
    'name' => 'DVelum Designer',
    'configs' => './configs',
    'locales' => './locales',
    'resources' =>'./resources',
    'templates' => './templates',
    'vendor'=>'Dvelum',
    'autoloader'=> [
        './classes'
    ],
    'objects' =>[
    ],
    'post-install'=>'\\Dvelum\\Designer\\Installer'
];