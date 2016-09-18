<?php
Yii::import('application.vendors.*');
require_once 'simple_html_dom.php';

class TestCommand extends BatchBase
{
  private $ch;
  private $cookie_path;
  private $user_agent;
  private $login_url;
  private $logout_url;
  private $transaction_url;

  /**
   * 設定ファイルのパラメータを取得する
   */
  private function paramInit() {
    $this->cookie_path = Yii::app()->params['cookie_path'];
    $this->user_agent = Yii::app()->params['user_agent'];
    $this->login_url = Yii::app()->params['login_url'];
    $this->transaction_url = Yii::app()->params['transaction_url'];
    $this->logout_url = Yii::app()->params['logout_url'];
    $this->log_id = "AIRREGI-INFO";
  }
  
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
   * AIRレジの認証用クッキーを削除する
   */
  private function clearCookies() {
    if (!is_resource($this->ch)) {
      $msg = 'cURLリソースが初期化されていません';
      $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
      return false;
    }
    unlink($this->cookie_path);
    $msg = 'Cookieを削除しました';
    $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);

    return true;
  }

  /**
   * AIRレジログイン画面のCSRFトークンを抽出する
   * @param 
   * @return CSRF token
   */
  private function getCsrfToken() {
    // 初回ログイン画面遷移を再現 
    $this->ch = curl_init();
    curl_setopt_array($this->ch, array(
        CURLOPT_URL => $this->login_url,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEFILE => $this->cookie_path,
        CURLOPT_COOKIEJAR => $this->cookie_path,
        CURLOPT_USERAGENT => $this->user_agent,
        CURLOPT_RETURNTRANSFER => true,
    ));
    $html = str_get_html($this->exec());
      
    // "name=_csrf"の要素を抽出し、値を返却
    foreach ($html->find('input, name') as $element) {
      if (strpos($element,'_csrf') !== false) {
          $msg = 'CSRFトークンが見つかりました。 CSRF=' . $element->{'value'};
          $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
          return $element->{'value'};
      }
    }

    // CSRFトークンが見つからなければエラー判定
    return false;
  }

  /**
   * ログインリクエスト
   * @param type $token
   * @return type
   */
  private function login($token) {
     // POSTパラメータを設定
     $params = array(
       "client_id" => Yii::app()->params['client_id'],
       "redirect_uri" => Yii::app()->params['redirect_uri'],
       "username" => Yii::app()->params['user']['username'],
       "password" => Yii::app()->params['user']['password'],
       "_csrf" => $token,
     );
 
     // ログインリクエストを送信
     $this->ch = curl_init();
     curl_setopt_array($this->ch, array(
         CURLOPT_URL => $this->login_url,
         CURLOPT_HEADER => true,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_COOKIEFILE => $this->cookie_path,
         CURLOPT_COOKIEJAR => $this->cookie_path,
         CURLOPT_USERAGENT => $this->user_agent,
         CURLOPT_POST => true,
         CURLOPT_POSTFIELDS => $params,
         CURLOPT_RETURNTRANSFER => true,
     ));
     
     $msg = 'ログイン処理を行いました。User=' . Yii::app()->params['user']['username'];
     $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
     return $this->exec();
   }
   
  private function getParams() {
      // 前日の日付を抽出する
      $year = date("Y",strtotime("-1 day")); 
      $month = date("m",strtotime("-1 day")); 
      $day = date("d",strtotime("-1 day"));

      // リクエストパラメータを設定する
      $params = 'paramStr=%7B%22categoryId%22%3A%22%22%2C%22targetDateYearFrom%22%3A%22' . $year .
                '%22%2C%22targetDateMonthFrom%22%3A%22' . $month .
                '%22%2C%22targetDateDayFrom%22%3A%22' . $day .
                '%22%2C%22targetDateYearTo%22%3A%22' . $year .
                '%22%2C%22targetDateMonthTo%22%3A%22' . $month .
                '%22%2C%22targetDateDayTo%22%3A%22' . $day .
                '%22%2C%22sortType%22%3A%220%22%7D';
      //$params = 'paramStr=%7B%22categoryId%22%3A%22%22%2C%22targetDateYearFrom%22%3A%222016%22%2C%22targetDateMonthFrom%22%3A%2209%22%2C%22targetDateDayFrom%22%3A%2208%22%2C%22targetDateYearTo%22%3A%222016%22%2C%22targetDateMonthTo%22%3A%2209%22%2C%22targetDateDayTo%22%3A%2213%22%2C%22sortType%22%3A%220%22%7D';
      return $params;
  }
   
  private function getTransaction() {
      // Headerを定義
      $http_header = array(
         'Connection:keep-alive',
         'corClpKeyCd:JF2TOOKSQRSSHS2F8A0D7ND6EO3PV3AI',
      );
      
      // Parameterセット
      $params = $this->getParams();
      
      // ログアウトリクエストを送信
      $this->ch = curl_init();
      curl_setopt_array($this->ch, array(
           CURLOPT_URL => $this->transaction_url,
           CURLOPT_COOKIEFILE => $this->cookie_path,
           CURLOPT_COOKIEJAR => $this->cookie_path,
           CURLOPT_USERAGENT => $this->user_agent,
           CURLOPT_HTTPHEADER => $http_header,
           CURLOPT_POST => true,
           CURLOPT_POSTFIELDS => $params,
           CURLOPT_RETURNTRANSFER => true,
      ));
 
      $response = $this->exec();
      $msg = '取引データを取得しました。Response=' . $response;
      $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
      return $response;
  }

  /**
   * ログアウトリクエスト
   * @return type
   */
  private function logout() {
    // Headerを定義
    $http_header = array(
       'Connection:keep-alive',
       'corClpKeyCd:JF2TOOKSQRSSHS2F8A0D7ND6EO3PV3AI',
    );
      
    // ログアウトリクエストを送信
    $this->ch = curl_init();
    curl_setopt_array($this->ch, array(
         CURLOPT_URL => $this->logout_url,
         CURLOPT_COOKIEFILE => $this->cookie_path,
         CURLOPT_COOKIEJAR => $this->cookie_path,
         CURLOPT_USERAGENT => $this->user_agent,
         CURLOPT_HTTPHEADER => $http_header,
         CURLOPT_RETURNTRANSFER => true,
    ));

    $msg = 'ログアウト処理を行いました';
    $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
    return $this->exec();
  }
   
  /**
   * メイン処理。ここから処理がスタートする
   * @param
   * @return
   */
  public function run($args) {
    // パラメーターの初期化
    $this->paramInit();
      
    // 開始ログを出力
    $msg = 'ARIレジのデータ出力バッチを開始します';
    $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);

    // CSRFトークンを取得
    $token = $this->getCsrfToken();
 
    // CSRFトークンが見つからなければ、エラーログを出力し、終了する
    if (!$token) {
      $msg = 'CSRFトークンが見つかりません。AIRレジのログインページを確認してください';
      $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
    }
    
    // AIRレジへログイン
    if (!$this->login($token)) {
      $msg = 'ログインリクエストに失敗しました';
      $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
    }
    
    // 取引データを抽出
    $this->getTransaction();
    
    // ログアウト処理
    $this->logout();
    
    // Cookie削除
    if (!$this->clearCookies()) {
      $msg = 'クッキーの削除に失敗しました';
      $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
    }
    
    // 終了ログを出力
    $msg = 'ARIレジのデータ出力バッチを終了します';
    $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
  }
}
