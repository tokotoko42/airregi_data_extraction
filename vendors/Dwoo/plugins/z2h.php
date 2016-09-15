<?php

function Dwoo_Plugin_z2h_compile(Dwoo_Compiler $compiler, $value)
{
    return 'mb_convert_kana((string) '.$value.', "ak", "utf-8")';
}
