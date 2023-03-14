<?php

namespace Os\Framework\Logger;

enum LogLevel
{
    case DEBUG;
    case INFO;
    case NOTICE;
    case WARNING;
    case ERROR;
    case CRITICAL;
    case ALERT;
    case EMERGENCY;
}