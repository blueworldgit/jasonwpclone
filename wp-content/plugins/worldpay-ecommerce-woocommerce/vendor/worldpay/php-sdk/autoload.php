<?php
/**
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {
    $namespacePrefix = 'Worldpay\\Api\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($namespacePrefix);
    if (strncmp($namespacePrefix, $class, $len) !== 0) {
        return;
    }

    $relativeClassName = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClassName) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
