<?php

class Dwoo_Plugin_emoji extends Dwoo_Plugin
{
	public function process($id = null, $num = null, $assign = null, $reset = false)
	{
        #if (isset($id)) {
        #    $out = $id + 1;
        #}
        #if (isset($num)) {
        #    $out = $num + 100;
        #}
        if (!isset($id) && !isset($num)) {
            return;
        }
    
        if ($id == '' && $num == '') {
            return;
        }
    
        if (isset($num)) {
            if (!is_numeric($num)) {
                return;
            }
            else if($num > 10) {
                return;
            }
            else {
                if ($num == 0) {
                    $id = 134;
                }
                else {
                    $id = $num + 124;
                }
            }
        }

        $out = $this->get_emoji_by_id($id);
		if ($assign === null) {
			return mb_convert_encoding($out, 'utf-8', 'cp932');
		}
		$this->dwoo->assignInScope($out, $assign);
	}

    //携帯端末のユーザエージェントを判定
    private function get_carrier($data)
    {
        if (preg_match("/^DoCoMo\/[12]\.0/i", $data)) {
            return "i"; // i-mode
        } else if (preg_match("/^(J\-PHONE|Vodafone|MOT\-[CV]980|SoftBank)\//i", $data)) {
            return "s"; // softbank
        } else if (preg_match("/^KDDI\-/i", $data) || preg_match("/UP\.Browser/i", $data)) {
            return "e"; // ezweb
        } else if (preg_match("/^PDXGW/i", $data) || preg_match("/(DDIPOCKET|WILLCOM);/i", $data)) {
            return "w"; // willcom
        } else if (preg_match("/^L\-mode/i", $data)) {
            return "l"; // l-mode
        } else {
            return "p"; // pc
        }
    }
    
    //携帯キャリアに合わせて絵文字を出力
    private function get_emoji_by_id($data)
    {
        //絵文字変換表
        $emoji_data = dirname(__FILE__) . "/emoji.csv";
    
        //変換表を配列に格納
        $file = file_get_contents($emoji_data);
        $emojis = preg_split('|[\r\n]+|', $file);
        foreach ($emojis as $num => $line) {
            $line_data = explode(',', $line);
            $emoji_array[$line_data[0]] = $line_data;
        }
  
        //携帯UA取得
        $agent = null;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        if (preg_match("/[0-9]{1,3}/", $data) && is_numeric($data) && 0 < $data && $data < 253) {
            switch ($this->get_carrier($agent)) {
                case "i";
                    $put = $emoji_array[$data][1];
                    break;
                case "e";
                    if (preg_match("/[^0-9]/", $emoji_array[$data][2])) {
                        $put = $emoji_array[$data][2];
                    } else {
                        $put = "<img localsrc=\"".$emoji_array[$data][2]."\" />";
                    }
                    break;
                case "s";
                    if (preg_match("/^[A-Z]{1}?/", $emoji_array[$data][3])) {
                        $hex = unpack('H*', $emoji_array[$data][3]);
                        $pos = substr($hex[1], 0, 2);
                        $code = hexdec(substr($hex[1], 2));
                        if ($pos=='47')
                        {
                            $offset = hexdec('e001')-hexdec('21');
                        }
                        else if ($pos=='45')
                        {
                            $offset = hexdec('e101')-hexdec('21');
                        }
                        else if ($pos=='46')
                        {
                            $offset = hexdec('e201')-hexdec('21');
                        }
                        else if ($pos=='4f')
                        {
                            $offset = hexdec('e301')-hexdec('21');
                        }
                        else if ($pos=='50')
                        {
                            $offset = hexdec('e401')-hexdec('21');
                        }
                        else if ($pos=='51')
                        {
                            $offset = hexdec('e501')-hexdec('21');
                        }
                        else
                        {
                            $offset = 0;
                        }
                        $code += $offset;
                        $put = sprintf('&#x%s;', dechex($code));
                        //$put = "\x1B\$".$emoji_array[$data][3]."\x0F";
                    } else {
                        $put = $emoji_array[$data][3];
                    }
                    break;
                case "p";
                    $put = "*";
                    break;
            }
            return $put;
        }
        else {
            return "[Error!]¥n";
        }
    }
}

