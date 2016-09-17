<?php
Yii::import('application.vendors.*');
require_once 'simple_html_dom.php';

class TestCommand extends BatchBase
{
  private $ch;
  private $userAgent;
  private $cookie;

  /**
   * リクエスト送信用コンポーネント
   * @param 
   * @return Response結果
   */
  private function exec() {
    $ret = curl_exec($this->ch);
    return $ret;
  }


  /**
   * AIRレジログイン画面のCSRFトークンを抽出する
   * @param 
   * @return CSRF token
   */
  private function getCsrfToken() {
    // ログインURLを指定
    $html = file_get_html( 'https://connect.airregi.jp/login?client_id=ARG&redirect_uri=https%3A%2F%2Fconnect.airregi.jp%2Foauth%2Fauthorize%3Fclient_id%3DARG%26redirect_uri%3Dhttps%253A%252F%252Fairregi.jp%252FCLP%252Fview%252FcallbackForPlfLogin%252Fauth%26response_type%3Dcode');

    // name=_csrfの要素を抽出し、値を返却
    $ret = $html->find( 'input, name' );
    foreach ($html->find('input, name') as $element) {
      if (strpos($element,'_csrf') !== false) {
        return $element->{'value'};
      }
    }

    // CSRFトークンが見つからなければエラー判定
    return false;
  }

  /**
   * メイン処理。ここから処理がスタートする
   * @param
   * @return
   */
  public function run($args) {
    // 開始ログを出力
    $this->log_id = "AIRREGI-INFO";
    $msg = 'Start AIR-regi data extraction batch';
    $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);

    // CSRFトークンを取得
    $token = $this->getCsrfToken();
    echo $token . "\n";

    // // CSRFトークンが見つからなければ、エラーログを出力し、終了する
    if ($token) {
      $this->log_id = "AIRREGI-INFO";
      $msg = 'Cannot get CSRF token';
      $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
    }
  }
}
