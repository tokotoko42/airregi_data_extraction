<?php
/**
 * Batch base class.
 *
 * @package
 * @subpackage
 * @version $Revision$
 */
class BatchBase extends CConsoleCommand
{
    public  $run_multiple = true;
    public  $exit_code = 0;
    public  $log_id = '';

    private $lock_file;
    public  $request_id = '';

    /**
     * Initialization.
     *
     * @return void
     */
    public function init()
    {
        // 定数ファイル
        Yii::import('application.const.*');

        register_shutdown_function(function() {
            Yii::app()->end();
        });
        if (!$this->run_multiple) {
            $this->checkIsRunning();
        }
        // ログ出力用 リクエスト固有ID生成
        $this->request_id = md5(time(). rand(0, 10000));

        Yii::log('Batch ' . $this->name . ' start', 'info');
    }

    /**
     * Check is running.
     *
     * @return void
     */
    private function checkIsRunning()
    {
        if (!isset(Yii::app()->params['batch_lock_dir'])) {
            echo "Lock Directory not found in configulation\n";
            Yii::app()->end();
        }
        $this->lock_file = Yii::app()->params['batch_lock_dir'] . '/batch_' . $this->name . '.pid';
        $pid = null;
        if (file_exists($this->lock_file)) {
            $pid = file_get_contents($this->lock_file);
        }
        if ($pid) {
            if (file_exists('/proc/' . $pid)) {
                Yii::log('batch '. $this->name. ' is still running.(PID:'. $pid. ')', 'error', 'components.batchbase');
                $this->exit_code = 1;
                Yii::app()->end();
            } else {
                Yii::log('previous batch '. $this->name. ' could stop by unknown reason', 'error', 'components.batchbase');
                unlink($this->lock_file);
            }
        }
        file_put_contents($this->lock_file, getmypid());
    }

    /**
     * 更新対象データ(トランザクションテーブル)のステータスチェック処理
     * 各APIから共通的に呼ばれる。
     * Transactionテーブルのprocess_flag（処理中）フラグをON・OFFにする
     * flagスタータスが処理中のものをONにしようとした場合はエラーとするが、
     * flagスタータスが未処理の時にoffに使用としたした時はエラーとしない。
     * エラー発生時は、ExceptionはThrowせず、
     * 戻り値によって呼び出し側にてエラー制御する（ここでthrowしてもcatchできないケースがある為)
     * 戻り値(0：false >>400, 1：true , -1：システムエラー >> 500)
     *
     * @param order_key 必須
     * @param process_division 必須 (0：API実行前, 1：API実行後)
     * @return integer(0：false, 1：true , -1：システムエラー)
     */
    protected function switchProcessFlag($order_key, $process_division)
    {
        $log_id = ConstApi::API_COMMON;
        // process_division に許容されるのは、0もしくは1のみである。
        if (!in_array($process_division, array(ConstApi::EXECUTED, ConstApi::UNEXECUTED))) {
            $msg = 'リクエストパラメータが不正です。:process_division='. $process_division;
            $this->setLog($log_id. '-'. ConstApi::LID_REQ_PRM, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
            return ConstApi::PROCESS_CHECK_FALSE;
        }

        // トランザクションスタート
        $transaction = Yii::app()->db->beginTransaction();

        // 行ロック
        $trans =  EmvTransaction::model()->getByOrderKeyLock($order_key);
        if (!$trans) {
            $transaction->rollback();
            $msg = '決済取引情報が存在しませんでした:order_key=%s'. $order_key;
            $this->setLog($log_id. '-'. ConstApi::LID_DAT_TRAN_NON, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
            return ConstApi::PROCESS_CHECK_FALSE;
        }

        // process_flgの値とdivisionの値の組み合わせにて処理を決定
        // 処理中＋API実行後 process_flagをOFFへ
        if ((integer)$trans['process_flag'] === ConstApi::EXECUTED && $process_division === ConstApi::EXECUTED) {
            $process_flag = ConstApi::UNEXECUTED;

        } else if ((integer)$trans['process_flag'] === ConstApi::UNEXECUTED && $process_division === ConstApi::UNEXECUTED) {
            // 未処理＋API実行前 process_flagをONへ
            $process_flag = ConstApi::EXECUTED;

        } else if ((integer)$trans['process_flag'] === ConstApi::EXECUTED && $process_division === ConstApi::UNEXECUTED) {
            // 既に処理中なのにONしようとした
            $transaction->rollback(); //行ロック開放
            $msg = '対象決済取引情報は処理中です。:order_key='. $order_key;
            $this->setLog($log_id. '-'. ConstApi::LID_DAT_TRAN_PROCESSED, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
            return ConstApi::PROCESS_CHECK_FALSE;
        } else {
            //未処理なのにOFFしようとした→OK扱いとする
            $transaction->rollback(); //行ロック開放してtrueを返す。
            return ConstApi::PROCESS_CHECK_TRUE;
        }
        //更新処理
        try {
            EmvTransaction::model()->updateProcessFlag($order_key, $process_flag);
            $transaction->commit();
            return ConstApi::PROCESS_CHECK_TRUE;
        } catch (Exception $e) {
            $transaction->rollback();
            $msg = 'データベースエラーが発生しました。:'. $e->getMessage();
            $this->setLog($log_id. '-'. ConstApi::LID_DB_ERR, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
            return ConstApi::PROCESS_CHECK_ERR;
        }
    }

    /**
     * ログ出力(改善)
     *
     * @param string $log_id ログID[API NAME]-[NNN]
     * @param string $log_level [info/warning/error/trace/profile]のいずれか
     * @param string $cls クラス名
     * @param string $fnc 機能名
     * @param string $line 行番号
     * @param string $message メッセージ内容
     * @param string $shop_code ショップコード => 空文字
     * @param string $agent_id エージェントID => 空文字
     */
    protected function setLog($log_id, $log_level, $cls, $fnc, $line, $message, $shop_code = '', $agent_id = '')
    {
        $log = array();
        $log[] = str_pad('-', ConstBatch::LEN_TOKEN, ' ', STR_PAD_RIGHT);
        $log[] = $this->request_id;
        $log[] = str_pad($log_id, ConstBatch::LEN_LOG_ID, ' ', STR_PAD_RIGHT);
        if (!empty($shop_code)) {
            $log[] = str_pad($shop_code, ConstBatch::LEN_SHOP_CODE, ' ', STR_PAD_RIGHT);
        } else {
            $log[] = str_pad('-', ConstBatch::LEN_SHOP_CODE, ' ', STR_PAD_RIGHT);
        }
        if (!empty($agent_id)) {
            $log[] = str_pad($agent_id, ConstBatch::LEN_AGENT_ID, ' ', STR_PAD_RIGHT);
        } else {
            $log[] = str_pad('-', ConstBatch::LEN_AGENT_ID, ' ', STR_PAD_RIGHT);
        }
        $log[] = str_pad($cls, ConstBatch::LEN_CLASS_NAME, ' ', STR_PAD_RIGHT);
        $log[] = str_pad($fnc, ConstBatch::LEN_FUNC_NAME, ' ', STR_PAD_RIGHT);
        $log[] = str_pad($line, ConstBatch::LEN_LINE_NUM, ' ', STR_PAD_RIGHT);
        $log[] = '-';
        $log[] = '-';
        $log[] = '-';
        $log[] = $message;

        Yii::log(implode("\t", $log), $log_level);
    }

    /**
     * 現在のメモリ使用量をダンプ
     * @param $logId ログID
     * @param $procName 処理名
     */
    protected function dumpUsedMemory($logId, $procName)
    {
        $usedMemory = round(memory_get_usage(true) / 1024 / 1024, 3);
        $msg = sprintf('%sメモリ: %s Byte', $procName, number_format(memory_get_usage(true)));

        if ($usedMemory < ConstBatch::MEMORY_USED_LOW) {
            $this->setLog($logId, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
        } else if ($usedMemory >= ConstBatch::MEMORY_USED_LOW && $usedMemory <= ConstBatch::MEMORY_USED_HIGH) {
            $this->setLog($logId, 'warning', __CLASS__, __FUNCTION__, __LINE__, $msg);
        } else if ($usedMemory > ConstBatch::MEMORY_USED_HIGH) {
            $this->setLog($logId, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
        } else {
            $this->setLog($logId, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
        }
    }

    /**
     * 処理経過時間を出力
     * @param $logId ログID
     * @param $watchStart 開始時刻(timestamp)
     * @param $threshold 閾値
     */
    protected function dumpElapsedTime($logId, $watchStart, $threshold)
    {
        $watchEnd = microtime(true);
        $elapsedTime = $watchEnd - $watchStart;

        $msg = sprintf('経過時間=%s 秒', $elapsedTime);
        if ($elapsedTime < $threshold['warning']) {
            $this->setLog($logId, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
        } else if ($elapsedTime >= $threshold['warning'] && $elapsedTime < $threshold['error']) {
            $this->setLog($logId, 'warning', __CLASS__, __FUNCTION__, __LINE__, $msg);
        } else if ($elapsedTime >= $threshold['error']) {
            $this->setLog($logId, 'error', __CLASS__, __FUNCTION__, __LINE__, $msg);
        } else {
            $this->setLog($logId, 'info', __CLASS__, __FUNCTION__, __LINE__, $msg);
        }
    }

    function __destruct()
    {
        if (file_exists($this->lock_file) && $this->exit_code === 0) {
            unlink($this->lock_file);
        }
        Yii::log('Batch '. $this->name. ' end', 'info');
        Yii::app()->end();
    }
}