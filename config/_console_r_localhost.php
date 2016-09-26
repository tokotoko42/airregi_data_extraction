<?php

return CMap::mergeArray(
    require(dirname(__FILE__) . '/console.php'),
    array(
        'components'=>array(
            'db'=>array(
                'connectionString' => 'mysql:host=127.0.0.1;dbname=airregi',
                'emulatePrepare' => true,
                'username' => 'root',
                'password' => 'test',
                'charset' => 'utf8',
                'class' => 'CDbConnection',
            ),
        ),
        'params' => array(
            'client_id' => 'ARG',
            'redirect_uri' => 'https://connect.airregi.jp/oauth/authorize?client_id=ARG&redirect_uri=https%3A%2F%2Fairregi.jp%2FCLP%2Fview%2FcallbackForPlfLogin%2Fauth&response_type=code',
            'user_array'=> array(
              'user1' => array(
                'username' => 'tokotoko42',
                'password' => 'test-****',
              ),
              'user2' => array(
                'username' => 'deruta1992',
                'password' => 'test-****',
              ),
            ),
            'login_url' => 'https://connect.airregi.jp/login?client_id=ARG&redirect_uri=https%3A%2F%2Fconnect.airregi.jp%2Foauth%2Fauthorize%3Fclient_id%3DARG%26redirect_uri%3Dhttps%253A%252F%252Fairregi.jp%252FCLP%252Fview%252FcallbackForPlfLogin%252Fauth%26response_type%3Dcode',
            'logout_url' => 'https://airregi.jp/CLP//view/logout/',
            'transaction_url' => 'https://airregi.jp/CLP/api/searchSalesListByMenu/execute/',
            'cookie_path' => '/tmp/cookie.txt',
            'tax_rate' => 8,
            'user_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6',
        ),
    )
);

