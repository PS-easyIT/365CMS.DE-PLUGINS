<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Support;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

if (!defined('ABSPATH')) {
    exit;
}

final class LoggerFactory
{
    private static ?LoggerInterface $logger = null;

    public static function create(): LoggerInterface
    {
        if (self::$logger instanceof LoggerInterface) {
            return self::$logger;
        }

        if (class_exists('\\CMS\\Logger')) {
            $cmsLogger = \CMS\Logger::instance()->withChannel('knowledgebase');
            self::$logger = new CmsLoggerAdapter($cmsLogger);
            return self::$logger;
        }

        self::$logger = new NullLogger();
        return self::$logger;
    }
}
