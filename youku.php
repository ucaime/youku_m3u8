<?php
require 'youku.class.php';
$youku = new youku();
var_dump($youku->getStreamTypes('XNTA1MzA4MDc2'));
var_dump($youku->getVideoUrl('XNTA1MzA4MDc2','hd2'));