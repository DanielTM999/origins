<?php

namespace Daniel\Origins;

use Daniel\Origins\Annotations\Profile;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;

final class ProfileMatcher
{
    public const ENV_KEY = 'app.profile';

    public static function getActiveProfile(): ?string
    {
        if (!isset($_ENV[self::ENV_KEY])) {
            return null;
        }

        $profile = trim((string) $_ENV[self::ENV_KEY]);
        return $profile === '' ? null : $profile;
    }

    /**
     * @param ReflectionAttribute[]|null $attributes
     */
    public static function isActive(ReflectionClass $class, ?array $attributes = null): bool
    {
        $profileAttribute = null;

        if ($attributes === null) {
            $profileAttributes = $class->getAttributes(Profile::class);
            $profileAttribute = $profileAttributes[0] ?? null;
        } else {
            foreach ($attributes as $attribute) {
                if ($attribute->getName() === Profile::class) {
                    $profileAttribute = $attribute;
                    break;
                }
            }
        }

        if ($profileAttribute === null) {
            return true;
        }

        $activeProfile = self::getActiveProfile();
        if ($activeProfile === null) {
            return false;
        }

        $profiles = array_values($profileAttribute->getArguments());
        if (empty($profiles)) {
            throw new InvalidArgumentException(
                "The #[Profile] attribute on '{$class->getName()}' must declare at least one profile."
            );
        }

        foreach ($profiles as $profile) {
            if (!is_string($profile) || trim($profile) === '') {
                throw new InvalidArgumentException(
                    "The #[Profile] attribute on '{$class->getName()}' only accepts non-empty strings."
                );
            }
        }

        return in_array($activeProfile, $profiles, true);
    }
}
