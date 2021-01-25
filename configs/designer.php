<?php
return [
	'storage'=>'file',
	'configs'=> 'layouts/',
	'lang'=>'eng',
	'development'=> false,
	'components'=>'Ext/Component',
	'field_components'=>'Ext/Component/Field',
	'filter_conponents'=>'Ext/Component/Filter',
	'actionjs_path'=>'',
	'compiled_js'=>'resources/dvelum-module-designer/js/designer/Designer.js',
    'langs_path'=>'www/js/lang/',
    'langs_url'=>'/js/lang/',
	'js_path'=>'www/js/',
	'js_url'=>'/js/',
	'templates' => [
		'wwwroot' => '[%wroot%]',
		'adminpath' => '[%admp%]',
		'urldelimiter' => '[%-%]'
	],
	'vcs_support' => true,
	'theme'=>'gray',
	'application_theme' => 'gray',
    'html_editor' => false
];
