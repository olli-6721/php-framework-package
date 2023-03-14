<?php

namespace Os\Framework\Kernel\Data\Struct;

abstract class Struct
{
    public const BASIC_TYPES = ["string", "bool", "int", "float", "array"];

    public function toArray(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties();
        foreach($properties as $property){
            if($property->getType()?->getName() === null) continue;
            $property->setAccessible(true);
            if($property->getValue($this) instanceof Struct){
                $value = $property->getValue($this)->toArray();
            }
            elseif(in_array($property->getType()?->getName(), self::BASIC_TYPES)){
                $value = $property->getValue($this);
            }
            else {
                $value = null;
            }
            $data[$property->getName()] = $value;
        }
        return $data;
    }
}