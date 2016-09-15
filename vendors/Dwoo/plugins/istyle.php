<?php

class Dwoo_Plugin_istyle extends Dwoo_Plugin
{
    public function process($id = null, $assign = null, $reset = false) {
        if (!isset($id) || !preg_match('/^[1-4]$/', $id)) {
            return '';
        }
        // Presence check HTTP_USER_AGENT
        $ua = null;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
        }
        $carrier = $this->get_carrier($ua);
        if ($carrier == 'i' || $carrier == 'e') {
            // docomo & au
            return 'istyle="' . $id . '"';
        }
        else if ($carrier == 's') {
            // softbank
            $mode_hash = array(
                1 => 'hiragana',
                2 => 'hankakukana',
                3 => 'alphabet',
                4 => 'numeric',
            );
            $out = 'mode="' . $mode_hash[$id] . '"';
        }
        else {
            $out = '';
        }

        if ($assign === null) {
            return $out;
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
}

