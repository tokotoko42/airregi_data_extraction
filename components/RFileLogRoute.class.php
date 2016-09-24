<?php
/**
 * ログ出力クラス
 */
class RFileLogRoute extends CFileLogRoute
{
    /**
     * 即時出力処理
     *
     * @param type $logger
     * @param type $processLogs
     */
    public function collectLogs($logger, $processLogs = false)
    {
        parent::collectLogs($logger, true);
    }

    /**
     * destructor
     *
     * @param void
     */
    public function __destruct()
    {
        $this->processLogs($this->logs);
    }
}