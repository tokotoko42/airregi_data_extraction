<?php
Yii::import('application.vendors.*');
require_once 'simple_html_dom.php';

class AirregiDataExtractionCommand extends BatchBase
{
  private $ch;
  private $cookie_path;
  private $user_agent;
  private $login_url;
  private $logout_url;
  private $transaction_url;
  private $user;
  private $tax_rate;
  
  /**
   * 設定ファイルのパラメータを取得する
   */
  private function paramInit() {
    $this->cookie_path = Yii::app()->params['cookie_path'];
    $this->user_agent = Yii::app()->params['user_agent'];
    $this->login_url = Yii::app()->params['login_url'];
    $this->transaction_url = Yii::app()->params['transaction_url'];
    $this->logout_url = Yii::app()->params['logout_url'];
    $this->user = Yii::app()->params['user_array'];
    $this->log_id = "AIRREGI-INFO";
    $this->tax_rate = Yii::app()->params['tax_rate'];
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
   * @param type $user
   * @param type $password
   * @return type
   */
  private function login($token, $user, $password) {
     // POSTパラメータを設定
     $params = array(
       "client_id" => Yii::app()->params['client_id'],
       "redirect_uri" => Yii::app()->params['redirect_uri'],
       "username" => $user,
       "password" => $password,
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
     
     $msg = 'ログイン処理を行いました。User=' . $user;
     $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
     return $this->exec();
  }

  /**
   * 取引データ抽出リクエストのパラメータを設定する
   * 取引データは前日分のデータを取得
   * @return string
   */
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
      
      // Debug用 (9/8　取引抽出)
      //$params = 'paramStr=%7B%22categoryId%22%3A%22%22%2C%22targetDateYearFrom%22%3A%222016%22%2C%22targetDateMonthFrom%22%3A%2209%22%2C%22targetDateDayFrom%22%3A%2208%22%2C%22targetDateYearTo%22%3A%222016%22%2C%22targetDateMonthTo%22%3A%2209%22%2C%22targetDateDayTo%22%3A%2213%22%2C%22sortType%22%3A%220%22%7D';
      
      return $params;
  }

  /**
   * 取引データ取得APIのリクエスト要求を行う
   * @param type $user
   * @return type
   */
  private function dataRequest($user) {
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
      $msg = '取引データを取得しました。User=' . $user . ' Response=' . $response;
      $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
      return $response;
  }

  /**
   * 取得した取引データをDBへ挿入する
   * @param type $items
   * @param type $user
   * @return bool
   */
  private function insertData($items, $user) {
      // 取引合計金額用データ
      $total_price = 0;
      
      // tempo_masterのIDを抽出
      $c = new CDbCriteria;
      $c->compare('user_id', $user);
      $tempo_master = TempoMaster::model()->findAll($c);
      
      // tempo_masterチェック
      if (empty($tempo_master[0]->id)) {
          $msg = '対象の店舗が存在しません。tempo_masterテーブルを確認してください。 user_id=' . $user;
          $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
          return false;
      }
          
      // 各取引情報を解析する
      // receipt_master テーブルの挿入
      foreach ($items as $item) {
          $shohin_name = $item["menuName"];
          $shohin_category_text = $item["categoryName"];
          $tax_rate = $this->tax_rate;
          $price = $item["saleMoneyAmount"];
          $total_price += $price;

          try {
              $transaction = Yii::app()->db->beginTransaction();

              $receipt_master = new ReceiptMaster();
              $receipt_master->buydate = date("Y-m-d", strtotime("-1 day"));
              $receipt_master->shoten_id = $tempo_master[0]->id;
              $receipt_master->shohin_name = $shohin_name;
              $receipt_master->shohin_category = 1;
              $receipt_master->shohin_category_text = $shohin_category_text;
              $receipt_master->tax = 0;
              $receipt_master->tax_rate = $tax_rate;
              $receipt_master->receipt_hokko = 0;
              $receipt_master->price = $price;

              $receipt_master->save();
              $transaction->commit();
              
              $msg = 'receip_masterテーブルにレコードを挿入しました';
              $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);

          } catch (CDbException $e) {
              $msg = 'データベースエラーが発生しました。: '. $e->getMessage();
              $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
              $transaction->rollback();
              return false;
          }
      }
        
      // receipt_dbテーブルの挿入
      try {
          $transaction = Yii::app()->db->beginTransaction();

          $receipt_db = new ReceiptDb();
          $receipt_db->buydate = date("Y-m-d", strtotime("-1 day"));
          $receipt_db->shoten_id = $tempo_master[0]->id;
          $receipt_db->price = $total_price;
          $receipt_db->save();

          $transaction->commit();
          $msg = 'receip_dbテーブルにレコードを挿入しました';
          $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
          
      } catch (CDbException $e) {
          $msg = 'データベースエラーが発生しました。: '. $e->getMessage();
          $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
          $transaction->rollback();
          return false;
      }
      return true;
  }

  /**
   * 取得したJsonデータをパースし、
   * データをDBへ挿入する
   * @param type $json
   * @param type $user
   */
  private function insertTransactionData($json, $user) {
    // リスポンスデータをJSONに変換する
    $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $json_array = json_decode($json,true);

    if ($json_array === NULL) {
        $msg = 'リクエストのJSONコードを取得できませんでした。 User=' . $user;
        $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
    }

    // 取引データがあれば、DBへインサート
    if ($json_array["results"]["returnCode"] === "0000") {
        if (empty($json_array["results"]["resultsData"]["statsSalesList"])) {
            $msg = '取引データが見つかりませんでした。 User=' . $user;
            $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
        } else {
            $this->insertData($json_array["results"]["resultsData"]["statsSalesList"], $user);
        }
    } else {
        $error_message = $json_array["results"]["errMsg"];
        $msg = 'ログイン処理でエラーが発生しました。 User=' . $user . ' errMsg=' . $error_message;
        $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
    }
  }

  /**
   * ログアウトリクエスト
   * @param type $user
   * @return type
   */
  private function logout($user) {
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

    $msg = 'ログアウト処理を行いました。 User=' . $user;
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

    // ユーザーを取得し、データ抽出を行う
    foreach ($this->user as $key => $user_info) {
        $user = $user_info['username'];
        $password = $user_info['password'];

        // CSRFトークンを取得
        $token = $this->getCsrfToken();
        // CSRFトークンが見つからなければ、エラーログを出力し、終了する
        if (!$token) {
            $msg = 'CSRFトークンが見つかりません。AIRレジのログインページを確認してください';
            $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
        }

        // AIRレジへログイン
        if (!$this->login($token, $user, $password)) {
            $msg = 'ログインリクエストに失敗しました';
            $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
        }
    
        // 取引データを抽出
        $json = $this->dataRequest($user);


        // データ取得に失敗した場合、ログアウトし終了
        if ($json === "") {
            $msg = 'Jsonの取得に失敗しました。ログインに失敗し立ている可能性があります。';
            $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);

        // 取得したデータをDBへ登録する
        } else {
            $this->insertTransactionData($json, $user);
        }

        // ログアウト処理
        $this->logout($user);
    
        // Cookie削除
        if (!$this->clearCookies()) {
            $msg = 'クッキーの削除に失敗しました';
            $this->setLog($this->log_id, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
        }
    }

    // 終了ログを出力
    $msg = 'ARIレジのデータ出力バッチを終了します';
    $this->setLog($this->log_id, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
  }
}
