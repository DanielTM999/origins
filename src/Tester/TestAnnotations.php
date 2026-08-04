<?php

namespace Daniel\Origins\Tester;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Test
{
    public function __construct(public string $name = "") {}
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class DisplayName
{
    public function __construct(public string $value = "") {}
}

#[Attribute(Attribute::TARGET_METHOD)]
class BeforeAll {}

#[Attribute(Attribute::TARGET_METHOD)]
class AfterAll {}

#[Attribute(Attribute::TARGET_METHOD)]
class BeforeEach {}

#[Attribute(Attribute::TARGET_METHOD)]
class AfterEach {}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Disabled
{
    public function __construct(public string $reason = "") {}
}
