<?php

return array(
    'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
    'name'=>'Console Application',
    'preload'=>array('log'),
    'import'=>array(
        'application.models.BaseModel',
        'application.models.*',
        'application.components.*',
    ),
    // application components
    'components'=>array(
        'log'=>array(
            'class'=>'CLogRouter',
            'routes'=>array(
                array(
                    'class'=>'RFileLogRoute',
                    'levels'=>'error, warning, profile, info, trace',
                    'logPath'=>'/usr/local/var/log/airregi/',
                    'logFile'=>'batch',
                ),
            ),
        ),
    ),
    'params'=>array(
    ),
);

