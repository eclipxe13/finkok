<?php

declare(strict_types=1);

namespace PhpCfdi\Finkok\Tests;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

final class LoggerPrinter extends AbstractLogger implements LoggerInterface
{
    /** @var string */
    public $outputFile;

    public function __construct(string $outputFile = 'php://stdout')
    {
        $this->outputFile = $outputFile;
    }

    /**
     * @inheritDoc
     * @param string|\Stringable $message
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        file_put_contents(
            $this->outputFile,
            PHP_EOL . print_r(json_decode(strval($message)), true),
            FILE_APPEND
        );
    }
}
