<?php

function Dwoo_Plugin_tinytime(Dwoo $dwoo, $input) {
    if (!$input) {
        return '';
    }
    $time = strtotime($input);

    if ($time > time() - 3600) {
        return intval((time() - $time) / 60) . '分前';
    }
    else if ($time > time() - 3600 * 24) {
        return intval((time() - $time) / 3600) . '時間前';
    }
    else if ($time > time() - 3600 * 24 * 90) {
        return intval((time() - $time) / (3600 * 24)) . '日前';
    }
    else {
        return date('n月j日', $time);
    }
}

