<?php
//	AJAXによるアクセスかどうかを判別する
    header("Content-type: text/plain; charset=UTF-8");
    if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') die("This access is not valid.");
?>