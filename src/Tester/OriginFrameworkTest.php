<?php

namespace Daniel\Origins\Tester;

use Daniel\Origins\AnnotationsUtils;
use Daniel\Origins\Autoloader;
use Daniel\Origins\Config;
use Daniel\Origins\DependencyManager;
use Daniel\Origins\Dispacher;
use Daniel\Origins\Origin;
use Daniel\Origins\OriginTest;
use Daniel\Origins\ServerConfig;
use Daniel\Origins\ServerDependencyManager;
use Daniel\Origins\ServerVarEnv;
use Daniel\Origins\VarEnv;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}

class OriginFrameworkTest extends OriginTest
{
    protected Dispacher $dispacher;
    protected Autoloader $autoload;
    protected DependencyManager $Dmanager;
    protected Config $serverConfg;
    protected VarEnv $varEnvLoader;

    public function __construct()
    {
        ob_start();

        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->dispacher = $this->getDispacher();
        $this->autoload = $this->getAutoload();
        $this->Dmanager = $this->getDependecyManager();
        $this->serverConfg = $this->getConfigOnInit();
        $this->varEnvLoader = $this->getVarEnv();

        $this->varEnvLoader->load();

        $this->autoload->load();
        $this->dispacher->map();
        $this->Dmanager->load();

        $this->Dmanager->addDependency(Dispacher::class, $this->dispacher);

        $this->serverConfg->ConfigOnInit();
    }

    public function showMappedendPoints($writeAsJson = false)
    {
        $this->dispacher->ShowEndPoints($writeAsJson);
    }

    public function run()
    {
        $this->runTests();
        ob_end_flush();
    }

    public function runTests(array $classes = [], array $methods = [])
    {
        $testFolder = $this->resolveTestFolder();
        $results = [];

        if ($testFolder !== null && is_dir($testFolder)) {
            foreach ($this->discoverTestClasses($testFolder, $classes) as $className) {
                $results = array_merge($results, $this->runTestClass($className, $methods));
            }
        }

        $this->emitReport($results);
        return $results;
    }

    private function discoverTestClasses(string $testFolder, array $filter): array
    {
        $normTest = $this->normalizePath($testFolder);
        $found = [];

        foreach (get_declared_classes() as $className) {
            try {
                $reflect = new ReflectionClass($className);
            } catch (Throwable) {
                continue;
            }

            if ($reflect->isAbstract() || $reflect->isInterface()) {
                continue;
            }

            $file = $reflect->getFileName();
            if ($file === false) {
                continue;
            }

            $normFile = $this->normalizePath($file);
            if (!str_starts_with($normFile, $normTest . DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (!$this->hasTestMethods($reflect)) {
                continue;
            }

            $found[] = $className;
        }

        if (!empty($filter)) {
            $filterSet = array_map(static fn($c) => ltrim((string) $c, '\\'), $filter);
            $found = array_values(array_filter(
                $found,
                static fn($c) => in_array(ltrim($c, '\\'), $filterSet, true)
            ));
        }

        return $found;
    }

    private function runTestClass(string $className, array $methodsFilter): array
    {
        $reflect = new ReflectionClass($className);
        $results = [];

        $testMethods = $this->collectTestMethods($reflect, $methodsFilter);
        if (empty($testMethods)) {
            return $results;
        }

        if (AnnotationsUtils::isAnnotationPresent($reflect, Disabled::class)) {
            $reason = $this->disabledReason($reflect);
            foreach ($testMethods as $method) {
                $results[] = new TestResult(
                    $className,
                    $method->getName(),
                    $this->methodDisplayName($method),
                    TestResult::SKIPPED,
                    0.0,
                    $reason !== '' ? $reason : 'Classe desabilitada'
                );
            }
            return $results;
        }

        try {
            $instance = $this->Dmanager->tryCreate($className);
        } catch (Throwable $th) {
            $instance = null;
        }

        if ($instance === null) {
            $msg = "Não foi possível instanciar a classe de teste via injetor de dependência."
                . (isset($th) ? " (" . $th->getMessage() . ")" : "");
            foreach ($testMethods as $method) {
                $results[] = new TestResult(
                    $className,
                    $method->getName(),
                    $this->methodDisplayName($method),
                    TestResult::ERROR,
                    0.0,
                    $msg
                );
            }
            return $results;
        }

        $beforeEach = $this->collectHookMethods($reflect, BeforeEach::class);
        $afterEach = $this->collectHookMethods($reflect, AfterEach::class);

        try {
            $this->invokeHooks($this->collectHookMethods($reflect, BeforeAll::class), $instance);
        } catch (Throwable $th) {
            foreach ($testMethods as $method) {
                $results[] = new TestResult(
                    $className,
                    $method->getName(),
                    $this->methodDisplayName($method),
                    TestResult::ERROR,
                    0.0,
                    "Falha em @BeforeAll: " . $th->getMessage(),
                    $th->getTraceAsString()
                );
            }
            return $results;
        }

        foreach ($testMethods as $method) {
            $results[] = $this->runSingleTest($className, $instance, $method, $beforeEach, $afterEach);
        }

        try {
            $this->invokeHooks($this->collectHookMethods($reflect, AfterAll::class), $instance);
        } catch (Throwable) {
        }

        return $results;
    }

    private function runSingleTest(
        string $className,
        object $instance,
        ReflectionMethod $method,
        array $beforeEach,
        array $afterEach
    ): TestResult {
        $display = $this->methodDisplayName($method);

        if (AnnotationsUtils::isAnnotationPresent($method, Disabled::class)) {
            $reason = $this->disabledReason($method);
            return new TestResult(
                $className,
                $method->getName(),
                $display,
                TestResult::SKIPPED,
                0.0,
                $reason !== '' ? $reason : 'Teste desabilitado'
            );
        }

        $message = null;
        $trace = null;
        $start = hrtime(true);
        try {
            $this->invokeHooks($beforeEach, $instance);
            $this->invokeOn($instance, $method->getName());
            $status = TestResult::PASSED;
        } catch (AssertionFailedException $th) {
            $status = TestResult::FAILED;
            $message = $th->getMessage();
        } catch (Throwable $th) {
            $status = TestResult::ERROR;
            $message = get_class($th) . ': ' . $th->getMessage();
            $trace = $th->getTraceAsString();
        } finally {
            try {
                $this->invokeHooks($afterEach, $instance);
            } catch (Throwable) {
            }
        }
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        return new TestResult($className, $method->getName(), $display, $status, $durationMs, $message, $trace);
    }

    private function collectTestMethods(ReflectionClass $reflect, array $filter): array
    {
        $methods = [];
        $filterSet = array_map('strval', $filter);
        foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!AnnotationsUtils::isAnnotationPresent($method, Test::class)) {
                continue;
            }
            if (!empty($filterSet) && !in_array($method->getName(), $filterSet, true)) {
                continue;
            }
            $methods[] = $method;
        }
        return $methods;
    }

    private function collectHookMethods(ReflectionClass $reflect, string $annotation): array
    {
        $methods = [];
        foreach ($reflect->getMethods() as $method) {
            if (AnnotationsUtils::isAnnotationPresent($method, $annotation)) {
                $methods[] = $method;
            }
        }
        return $methods;
    }

    private function hasTestMethods(ReflectionClass $reflect): bool
    {
        foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (AnnotationsUtils::isAnnotationPresent($method, Test::class)) {
                return true;
            }
        }
        return false;
    }

    private function invokeHooks(array $methods, object $instance): void
    {
        foreach ($methods as $method) {
            $this->invokeOn($instance, $method->getName());
        }
    }

    private function invokeOn(object $instance, string $methodName): void
    {
        $method = new ReflectionMethod($instance, $methodName);
        $method->setAccessible(true);
        $method->invoke($instance);
    }

    private function methodDisplayName(ReflectionMethod $method): string
    {
        if (AnnotationsUtils::isAnnotationPresent($method, DisplayName::class)) {
            $args = AnnotationsUtils::getAnnotationArgs($method, DisplayName::class);
            if (!empty($args) && isset($args[0]) && $args[0] !== '') {
                return (string) $args[0];
            }
        }
        if (AnnotationsUtils::isAnnotationPresent($method, Test::class)) {
            $args = AnnotationsUtils::getAnnotationArgs($method, Test::class);
            if (!empty($args) && isset($args[0]) && $args[0] !== '') {
                return (string) $args[0];
            }
        }
        return $method->getName();
    }

    private function disabledReason(ReflectionClass|ReflectionMethod $target): string
    {
        $args = AnnotationsUtils::getAnnotationArgs($target, Disabled::class);
        if (!empty($args) && isset($args[0])) {
            return (string) $args[0];
        }
        return '';
    }

    private function resolveTestFolder(): ?string
    {
        $folder = $_ENV['test.folder'] ?? (($_ENV['base.dir'] ?? '') . DIRECTORY_SEPARATOR . 'test');
        $folder = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $folder), DIRECTORY_SEPARATOR);
        return $folder !== '' ? $folder : null;
    }

    private function normalizePath(string $path): string
    {
        $real = realpath($path);
        $path = $real !== false ? $real : $path;
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    private function emitReport(array $results): void
    {
        $html = (new TestReportGenerator())->generate($results);

        $dir = Origin::getRuntimeDir() . 'tests' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @file_put_contents($dir . 'report.html', $html);

        echo $html;
    }

    protected function getConfigOnInit(): Config
    {
        return new ServerConfig($this->Dmanager);
    }

    protected function getVarEnv(): VarEnv
    {
        return new ServerVarEnv();
    }

    protected function getDispacher(): Dispacher
    {
        return new ServerDispacherTester();
    }

    protected function getAutoload(): Autoloader
    {
        return new ServerAutoloadTester();
    }

    protected function getDependecyManager(): DependencyManager
    {
        return new ServerDependencyManager();
    }
}
