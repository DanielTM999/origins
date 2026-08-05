<?php

namespace Daniel\Origins\Tester;

class TestResult
{
    public const PASSED  = 'passed';
    public const FAILED  = 'failed';
    public const ERROR   = 'error';
    public const SKIPPED = 'skipped';

    public function __construct(
        public string $class,
        public string $method,
        public string $displayName,
        public string $status,
        public float $durationMs = 0.0,
        public ?string $message = null,
        public ?string $trace = null,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === self::PASSED || $this->status === self::SKIPPED;
    }
}
