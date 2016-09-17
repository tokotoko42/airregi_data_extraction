<?php
/**
 * ログ出力クラス
 *
 * @package
 * @subpackage
 * @author ts-tosfunaki<ts-toshiharu.funaki@mail.rakuten.com>
 * @version $Revision$
 * $Id$
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