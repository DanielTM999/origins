<?php

namespace Daniel\Origins\Tester;

use Exception;

/**
 * Lançada por {@see Assertions} quando uma verificação falha.
 *
 * Serve para diferenciar uma falha de asserção ("failed") de um erro inesperado ("error")
 * durante a execução de um teste.
 */
class AssertionFailedException extends Exception {}
