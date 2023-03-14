<?php

namespace Os\Framework\Debug;

use Os\Framework\DataAbstractionLayer\Service\Uuid;

class Dumper
{
    public static function dump($value){
        switch(CONTEXT){
            case "http":
                echo '<pre style="background: #000;padding:5px;font: 12px Menlo, Monaco, Consolas, monospace;white-space: pre-wrap; ">';
                self::writeValue($value);
                echo '</pre>';
                self::writeJavascript();

                break;
            case "cli":
                var_dump(is_string($value) ? str_replace('<','&lt;', $value) : $value);
                break;
        }
    }

    public static function dd($value){
        self::dump($value);
        die();
    }

    private static function writeValue($value){
        if($value === null){
            echo "<span style='color: darkorange'>NULL</span>";
        }
        elseif(is_string($value)){
            echo sprintf("<!--<span style='color: darkorange'>STRING</span>-->%s", self::writeString($value));
        }
        elseif(is_float($value) || is_int($value) || is_double($value)){
            echo sprintf("<!--<span style='color: darkorange'>INT</span>-->%s", self::writeNumber($value));
        }
        elseif(is_object($value)) {
            self::writeObject($value);
        }
        elseif(is_array($value)){
            self::writeArray($value);
        }
        elseif(is_bool($value)){
            echo self::writeBoolean($value);
        }
        else {
            var_dump($value);
        }
    }

    private static function serializeString(string $value): string
    {
        return str_replace('<','&lt;', $value);
    }

    private static function writeBoolean(bool $value): string {
        $_value = $value === true ? "true" : "false";
        return sprintf("<span style='color: darkorange'>%s</span>", $_value);
    }

    private static function writeString(string $value): string
    {
        $uuid = Uuid::v4();
        $short = false;
        $hiddenValue = "";
        if(strlen($value) > 500){
            $hiddenValue = substr($value, 499);
            $value = substr($value, 0, 500);
            $short = true;
        }
        $str = sprintf("<span style='color: darkorange'>\"</span><span class='dump-content-%s' data-type='string' style='color: limegreen'>%s<span class='string-hidden' style='display:none;'>%s</span></span><span style='color: darkorange'>\"</span>", $uuid, self::serializeString($value), $hiddenValue);
        if($short){
            $str = sprintf("%s%s", $str, self::addDumpTrigger($uuid));
        }
        return $str;
    }

    private static function writeNumber(int|float $value): string
    {
        return sprintf("<span style='color: dodgerblue'>%s</span>", $value);
    }

    private static function writeObject($value){
        $uuid = Uuid::v4();
        $reflection = new \ReflectionObject($value);

        echo sprintf("<span style='color: dodgerblue'>%s:</span> <span style='color: darkorange'>{</span>", $value::class);
        echo sprintf("%s", self::addDumpTrigger($uuid, "left", "down"));
        echo "<ul style='list-style: none;padding-inline-start: 20px;margin:0;'>";
        foreach($reflection->getProperties() as $property){
            echo sprintf("<li class='dump-content-%s' data-type='list' style='display:none;'>", $uuid);
            echo sprintf("<span style='color: #fff'>%s</span>",$property->getName());
            echo "<span style='color: darkorange'>: </span>";
            try {
                $_value = $property->getValue($value);
                $property->setAccessible(true);
                self::writeValue($_value);
            }
            catch (\Throwable $e){
                self::writeValue(null);
            }
            echo "</li>";
        }
        echo "</ul>";
        echo "<span style='color: darkorange'>}</span>";
    }

    private static function writeArray(array $value){
        echo sprintf("<span style='color: dodgerblue'>ARRAY:%d</span> <span style='color: darkorange'>[</span><br>", count($value));
        echo "<ul style='list-style: none;padding-inline-start: 20px;margin:0;'>";
        foreach($value as $key => $item){
            echo "<li>";
            if(is_int($key) || is_float($key)){
                echo self::writeNumber($key);
            }
            else {
                echo self::writeString($key);
            }
            echo "<span style='color: darkorange'> => </span>";
            self::writeValue($item);
            echo "</li>";
        }
        echo "</ul>";
        echo "<span style='color: darkorange'>]</span>";
    }

    private static function addDumpTrigger(string $uuid, string $initDirection = "left", string $triggerDirection = "up"): string
    {
        switch($initDirection){
            case "up":
                $arrowInit = "^";
                break;
            case "down":
                $arrowInit = "▼";
                break;
            default:
            case "left":
                $arrowInit = "◀";
                break;
            case "right":
                $arrowInit = "▶";
                break;
        }
        switch($triggerDirection){
            case "up":
                $arrowTrigger = "^";
                break;
            case "down":
                $arrowTrigger = "▼";
                break;
            default:
            case "left":
                $arrowTrigger = "◀";
                break;
            case "right":
                $arrowTrigger = "▶";
                break;
        }
        return sprintf("<span class='dump-trigger' id='dump-trigger-%s' data-uuid='%s' data-trigger-arrow=' %s' data-init-arrow='%s' data-triggered='false' style='color:dimgrey;cursor:pointer;'>%s</span>", $uuid, $uuid, $arrowTrigger, $arrowInit, $arrowInit);
    }

    private static function writeJavascript(){
        echo "<script>
    if(!window.hasOwnProperty('dumper')){
        window.dumper = true;
        window.onload = function(){
            document.querySelectorAll('.dump-trigger').forEach((trigger) => {
                if(!trigger.hasAttribute('data-uuid')) return;
                let uuid = trigger.getAttribute('data-uuid');
                trigger.addEventListener('click', (event) => {
                     let triggered = trigger.getAttribute('data-triggered');
                     let elements = document.querySelectorAll('.dump-content-' + uuid);
                     if(triggered === 'false'){
                        trigger.setAttribute('data-triggered', 'true');
                        trigger.innerText = trigger.getAttribute('data-trigger-arrow');
                        elements.forEach((el) => {
                            if(el.getAttribute('data-type') === 'string'){
                                el.querySelector('.string-hidden').style.display = 'block';
                            }
                            else if(el.getAttribute('data-type') === 'list'){
                                el.style.display = 'block';
                            }
                        });
                     }
                     else if(triggered === 'true'){
                        trigger.setAttribute('data-triggered', 'false');
                        trigger.innerText = trigger.getAttribute('data-init-arrow');
                        elements.forEach((el) => {
                            if(el.getAttribute('data-type') === 'string'){
                                el.querySelector('.string-hidden').style.display = 'none';
                            }
                            else if(el.getAttribute('data-type') === 'list'){
                                el.style.display = 'none';
                            }
                        });
                     }
                });
            });
        }
    }
</script>";
    }
}