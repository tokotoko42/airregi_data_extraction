<?php

return CMap::mergeArray(
    require(dirname(__FILE__) . '/console.php'),
    array(
        'components'=>array(
            'db'=>array(
                'connectionString' => 'mysql:host=localhost;port=3301;dbname=test',
                'emulatePrepare' => true,
                'username' => 'root',
                'password' => 'password',
                'charset' => 'utf8',
            ),
        ),
        'params' => array(
            'client_id' => 'ARG',
            'redirect_uri' => 'https://connect.airregi.jp/oauth/authorize?client_id=ARG&redirect_uri=https%3A%2F%2Fairregi.jp%2FCLP%2Fview%2FcallbackForPlfLogin%2Fauth&response_type=code',
            'user' => array(
                'username' => '*********',
                'password' => '*********',
            ),
            'login_url' => 'https://connect.airregi.jp/login?client_id=ARG&redirect_uri=https%3A%2F%2Fconnect.airregi.jp%2Foauth%2Fauthorize%3Fclient_id%3DARG%26redirect_uri%3Dhttps%253A%252F%252Fairregi.jp%252FCLP%252Fview%252FcallbackForPlfLogin%252Fauth%26response_type%3Dcode',
            'cookie_path' => '/tmp/cookie.txt',
            'user_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6',
        ),
    )
);

