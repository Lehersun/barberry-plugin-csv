<?php

require_once __DIR__ . '/bootstrap.php';

$filter = $argv[1] ?? null;
$testFiles = array_values(array_filter(
    glob(__DIR__ . '/*Test.php') ?: array(),
    static function (string $path) use ($filter): bool {
        return $filter ? basename($path, '.php') === $filter : true;
    }
));

if (!$testFiles) {
    fwrite(STDERR, "No tests matched.\n");
    exit(1);
}

foreach ($testFiles as $testFile) {
    require_once $testFile;
}

$classes = array_values(array_filter(
    get_declared_classes(),
    static function (string $class) use ($filter): bool {
        if (!is_subclass_of($class, \Barberry\Plugin\Csv\Test\TestCase::class)) {
            return false;
        }

        return $filter ? substr(strrchr($class, '\\') ?: $class, 1) === $filter : true;
    }
));

$count = 0;
foreach ($classes as $class) {
    $reflection = new ReflectionClass($class);
    $instance = $reflection->newInstance();
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if (strpos($method->getName(), 'test') !== 0) {
            continue;
        }

        $count++;
        $instance->{$method->getName()}();
        echo $class . '::' . $method->getName() . " OK\n";
    }
}

echo sprintf("%d tests passed.\n", $count);
