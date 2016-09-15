<?php
Yii::import('application.vendors.*');
require_once 'simple_html_dom.php';

class TestCommand extends BatchBase
{
  private $ch;
  private $userAgent;
  private $cookie;
    
  private function getCsrfToken() {
    $html = file_get_html( 'https://connect.airregi.jp/login?client_id=ARG&redirect_uri=https%3A    %2F%2Fconnect.airregi.jp%2Foauth%2Fauthorize%3Fclient_id%3DARG%26redirect_uri%3Dhttps%253A%252F%    252Fairregi.jp%252FCLP%252Fview%252FcallbackForPlfLogin%252Fauth%26response_type%3Dcode' );
    $ret = $html->find( 'input, name' );
    foreach ($html->find('input, name') as $element) {
      if (strpos($element,'_csrf') !== false) {
        return $element->{'value'};
      }
    }
  }
  
  private function exec() {
    $ret = curl_exec($this->ch);
    return $ret;
  }
  
  private function login($token) {
    $cookie_path = Yii::app()->params['cookie_path'];
    $user_agent = Yii::app()->params['user_agent'];
    $params = array(
        'client_id' => Yii::app()->params['client_id'],
        'redirect_url' => Yii::app()->params['redirect_url'],
        'username' => Yii::app()->params['user']['username'],
        'password' => Yii::app()->params['user']['password'],
        '_csrf' => $token,
    );
    
    curl_setopt_array($this->ch, array(
        CURLOPT_URL => Yii::app()->params['login_url'],
        CURLOPT_POST => true,
        CURLOPT_COOKIEFILE => $cookie_path,
        CURLOPT_COOKIEJAR => $cookie_path,
        CURLOPT_POSTFIELDS => $params,
        CURLOPT_HEADER => true,
        CURLOPT_USERAGENT => $user_agent,
    ));
    
    return $this->exec();
  }
    
  
  public function run($args) {
    // Start Log
    $this->log_id = "AIRREGI-INFO";
    $msg = 'Start AIR-regi data extraction batch';
    $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
    
    $token = $this->getCsrfToken();
    
    $this->login($token);
    
  }
}
