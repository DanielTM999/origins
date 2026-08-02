<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Daniel\Origins\Annotations\Profile;
use Daniel\Origins\Exceptions\InactiveProfileException;
use Daniel\Origins\ProfileMatcher;
use Daniel\Origins\ServerAutoload;
use Daniel\Origins\ServerDependencyManager;

class AlwaysActiveProfileFixture
{
}

#[Profile('dev')]
class DevelopmentProfileFixture
{
}

#[Profile('dev', 'test')]
class MultipleProfilesFixture
{
}

#[Profile('prod')]
class ProductionDependencyFixture
{
}

class ActiveDependencyWithInactiveConstructorFixture
{
    public function __construct(ProductionDependencyFixture $dependency)
    {
    }
}

function assertProfileTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function profileIsActive(string $className): bool
{
    return ProfileMatcher::isActive(new ReflectionClass($className));
}

unset($_ENV[ProfileMatcher::ENV_KEY]);
assertProfileTest(profileIsActive(AlwaysActiveProfileFixture::class), 'Unannotated classes must always be active.');
assertProfileTest(!profileIsActive(DevelopmentProfileFixture::class), 'Annotated classes must be inactive without app.profile.');

$_ENV[ProfileMatcher::ENV_KEY] = ' dev ';
assertProfileTest(ProfileMatcher::getActiveProfile() === 'dev', 'The active profile must be trimmed.');
assertProfileTest(profileIsActive(DevelopmentProfileFixture::class), 'The matching profile must activate the class.');
assertProfileTest(profileIsActive(MultipleProfilesFixture::class), 'Any profile listed by the attribute must match.');
assertProfileTest(!profileIsActive(ProductionDependencyFixture::class), 'A different profile must keep the class inactive.');

$_ENV['enviroment'] = 'dev';
$dependencyManager = new ServerDependencyManager();
$inactiveDependencyWasRejected = false;
try {
    $dependencyManager->tryCreate(ProductionDependencyFixture::class);
} catch (InactiveProfileException $exception) {
    $inactiveDependencyWasRejected = str_contains($exception->getMessage(), ProductionDependencyFixture::class)
        && str_contains($exception->getMessage(), 'dev');
}
assertProfileTest($inactiveDependencyWasRejected, 'The dependency manager must reject inactive concrete dependencies.');

$transitiveDependencyWasRejected = false;
try {
    $dependencyManager->tryCreate(ActiveDependencyWithInactiveConstructorFixture::class);
} catch (InactiveProfileException $exception) {
    $transitiveDependencyWasRejected = str_contains($exception->getMessage(), ProductionDependencyFixture::class);
}
assertProfileTest($transitiveDependencyWasRejected, 'Constructor injection must not bypass an inactive profile.');

$originalDocumentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
$scanRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'origins-profile-' . uniqid();
mkdir($scanRoot);
$scanFixture = $scanRoot . DIRECTORY_SEPARATOR . 'ProfileScanFixtures.php';
file_put_contents($scanFixture, <<<'PHP'
<?php

namespace ProfileScanFixtures;

use Daniel\Origins\Annotations\Controller;
use Daniel\Origins\Annotations\Dependency;
use Daniel\Origins\Annotations\Profile;

#[Dependency]
#[Profile('dev')]
class DevelopmentDependency
{
}

#[Dependency]
#[Profile('prod')]
class ProductionDependency
{
}

#[Controller]
#[Profile('dev')]
class DevelopmentController
{
}

#[Controller]
#[Profile('prod')]
class ProductionController
{
}
PHP);

$_SERVER['DOCUMENT_ROOT'] = $scanRoot;
(new ServerAutoload())->load();

assertProfileTest(
    in_array('ProfileScanFixtures\\DevelopmentDependency', $_SESSION['origins.dependencys'], true),
    'The scanner must register a dependency for the active profile.'
);
assertProfileTest(
    !in_array('ProfileScanFixtures\\ProductionDependency', $_SESSION['origins.dependencys'], true),
    'The scanner must exclude a dependency for an inactive profile.'
);
assertProfileTest(
    in_array('ProfileScanFixtures\\DevelopmentController', $_SESSION['origins.controllers'], true),
    'The scanner must register a controller for the active profile.'
);
assertProfileTest(
    !in_array('ProfileScanFixtures\\ProductionController', $_SESSION['origins.controllers'], true),
    'The scanner must exclude a controller for an inactive profile.'
);
assertProfileTest($_SESSION['origins.profile'] === 'dev', 'The session must record the profile used by the scanner.');

if ($originalDocumentRoot === null) {
    unset($_SERVER['DOCUMENT_ROOT']);
} else {
    $_SERVER['DOCUMENT_ROOT'] = $originalDocumentRoot;
}
unlink($scanFixture);
@rmdir($scanRoot . DIRECTORY_SEPARATOR . 'runtime');
rmdir($scanRoot);

$originalMetadataPath = ServerAutoload::$metaDadosPath;
$metadataPath = tempnam(sys_get_temp_dir(), 'origins-profile-');
ServerAutoload::$metaDadosPath = $metadataPath;
$getCache = new ReflectionMethod(ServerAutoload::class, 'getCache');
$loader = new ServerAutoload();

file_put_contents($metadataPath, json_encode(['activeProfile' => 'dev', 'loadedFiles' => []]));
assertProfileTest(is_array($getCache->invoke($loader)), 'Cache for the active profile must be accepted.');

file_put_contents($metadataPath, json_encode(['activeProfile' => 'prod', 'loadedFiles' => []]));
assertProfileTest($getCache->invoke($loader) === null, 'Cache for another profile must be invalidated.');

file_put_contents($metadataPath, json_encode(['loadedFiles' => []]));
assertProfileTest($getCache->invoke($loader) === null, 'Legacy cache without a profile marker must be invalidated.');

ServerAutoload::$metaDadosPath = $originalMetadataPath;
unlink($metadataPath);

echo "Profile tests passed.\n";
