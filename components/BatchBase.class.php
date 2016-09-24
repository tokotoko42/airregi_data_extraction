<?php
/**
 * Batch base class.
 *
 */
class BatchBase extends CConsoleCommand
{
    public  $exit_code = 0;
    public  $log_id = '';
    public  $request_id = '';

    public function init()
    {
        // 定数ファイル
        Yii::import('application.const.*');

        register_shutdown_function(function() {
            Yii::app()->end();
        });

        // ログ出力用 リクエスト固有ID生成
        $this->request_id = md5(time(). rand(0, 10000));
    }

    protected function setLog($log_id, $log_level, $cls, $fnc, $line, $message)
    {
        $log = array();
        $log[] = $this->request_id;
        $log[] = str_pad($log_id, ConstBatch::LEN_LOG_ID, ' ', STR_PAD_RIGHT);
        $log[] = str_pad($cls, ConstBatch::LEN_CLASS_NAME, ' ', STR_PAD_RIGHT);
        $log[] = str_pad($fnc, ConstBatch::LEN_FUNC_NAME, ' ', STR_PAD_RIGHT);
        $log[] = str_pad($line, ConstBatch::LEN_LINE_NUM, ' ', STR_PAD_RIGHT);
        $log[] = $message;

        Yii::log(implode("\t", $log), $log_level);
    }

    function __destruct()
    {
        Yii::app()->end();
    }
}
