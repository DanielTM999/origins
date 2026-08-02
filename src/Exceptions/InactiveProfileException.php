<?php

namespace Daniel\Origins\Exceptions;

use RuntimeException;

class InactiveProfileException extends RuntimeException
{
    public function __construct(string $className, ?string $activeProfile)
    {
        $profile = $activeProfile ?? '<not configured>';
        parent::__construct("Class '$className' is disabled for active profile '$profile'.");
    }
}

