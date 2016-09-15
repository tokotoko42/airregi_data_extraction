<?php

function Dwoo_Plugin_jp_date(Dwoo $dwoo, $date) {
    if (preg_match('/(\d{4})[\/\-]?(\d{2})[\/\-]?(\d{2})/', $date, $matched)) {
        return $matched[1] . '年' . (int)$matched[2] . '月' . (int)$matched[3] . '日';
    }
    else {
        return $date;
    }
}

