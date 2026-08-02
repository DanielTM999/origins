<?php

namespace Daniel\Origins\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Profile
{
    public array $profiles;

    public function __construct(string $profile, string ...$additionalProfiles)
    {
        $this->profiles = array_merge([$profile], $additionalProfiles);
    }
}

