<?php

namespace Daniel\Origins\Tester;

use Attribute;

/**
 * Marca um método como um caso de teste (estilo JUnit @Test).
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Test
{
    public function __construct(public string $name = "") {}
}

/**
 * Nome amigável exibido no relatório, para o método ou para a classe.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class DisplayName
{
    public function __construct(public string $value = "") {}
}

/**
 * Executado uma única vez, antes de todos os testes da classe.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class BeforeAll {}

/**
 * Executado uma única vez, depois de todos os testes da classe.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AfterAll {}

/**
 * Executado antes de cada método de teste.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class BeforeEach {}

/**
 * Executado depois de cada método de teste.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AfterEach {}

/**
 * Ignora o teste (ou toda a classe), marcando-o como "skipped" no relatório.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Disabled
{
    public function __construct(public string $reason = "") {}
}
