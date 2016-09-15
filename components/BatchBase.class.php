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

    protected function setLog($log_id, $log_level, $cls, $fnc, $line, $message)
    {
        $log = array();
        $log[] = str_pad('-', ConstBatch::LEN_TOKEN, ' ', STR_PAD_RIGHT);
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
        if (file_exists($this->lock_file) && $this->exit_code === 0) {
            unlink($this->lock_file);
        }
        Yii::log('Batch '. $this->name. ' end', 'info');
        Yii::app()->end();
    }
}
