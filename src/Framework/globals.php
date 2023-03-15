<?php

$extensions = ["pdo", "yaml", "readline", "dom", "curl", "mbstring"];
foreach($extensions as $extension){
    if(!extension_loaded($extension)){
        throw new Exception(sprintf("Extension '%s' is not loaded (required extensions are: '%s')", $extension, implode(',', $extensions)));
    }
}

if(!function_exists('dump')){
    function dump($data){
        \Os\Framework\Debug\Dumper::dump($data);
    }
}
if(!function_exists('dd')){
    function dd($data){
        \Os\Framework\Debug\Dumper::dd($data);
    }
}