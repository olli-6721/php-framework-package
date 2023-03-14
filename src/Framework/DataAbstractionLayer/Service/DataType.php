<?php

namespace Os\Framework\DataAbstractionLayer\Service;

enum DataType
{
    case STRING;
    case UUID;
    case INT;
    case BOOL;
    case FLOAT;
    case JSON;
    case DATETIME;

    public static function createFrom(?string $type): ?DataType
    {
        return match(strtolower($type)){
            "string", null => DataType::STRING,
            "int" => DataType::INT,
            "array", "json" => DataType::JSON,
            "float" => DataType::FLOAT,
            "bool" => DataType::BOOL,
            "datetime", "datetimeinterface", \DateTimeInterface::class, \DateTime::class => DataType::DATETIME,
            default => null
        };
    }

    public function getSqlType(): string
    {
        return match($this){
            DataType::STRING, DataType::UUID => "VARCHAR(255)",
            DataType::INT => "INT",
            DataType::BOOL => "BOOL",
            DataType::FLOAT => "FLOAT",
            DataType::JSON => "MEDIUMTEXT",
            DataType::DATETIME => "DATETIME"
        };
    }

    public function getPhpType(): string
    {
        return match($this){
            DataType::STRING, DataType::UUID => "string",
            DataType::INT => "int",
            DataType::BOOL => "bool",
            DataType::FLOAT => "float",
            DataType::JSON => "array",
            DataType::DATETIME => sprintf("\%s", \DateTimeInterface::class)
        };
    }

    public static function resolveBasicTypeAndValue(string $origin, ?string &$value, ?DataType &$type){
        $_value = null;
        $_type = null;
        if(
            (
                (str_starts_with($origin, '\'') && str_ends_with($origin, '\'')) ||
                (str_starts_with($origin, '"') && str_ends_with($origin, '"'))
            ) ||
            is_numeric($origin) ||
            $origin === "true" ||
            $origin === "false"
        ){
            if(is_numeric($origin)){
                if(str_contains($origin, ".")){
                    $_type = DataType::FLOAT;
                    $_value = floatval($origin);
                }
                else{
                    $_type = DataType::INT;
                    $_value = intval($origin);
                }
            }
            elseif($origin === "true" || $origin === "false"){
                $_type = DataType::BOOL;
                $_value = filter_var($origin, FILTER_VALIDATE_BOOL);
            }
            else{
                $_type = DataType::STRING;
                $_value = trim($origin, '\'"');
            }
        }
        $value = $_value;
        $type = $_type;
    }
}