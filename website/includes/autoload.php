<?php

spl_autoload_register(function ($class_name) {
    $base_dir = $_SERVER['DOCUMENT_ROOT'] . '/includes/OOP/';
    $relative_class = str_replace('\\', '/', $class_name);
    $file = $base_dir . $relative_class . '.php';

    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("Autoloader failed to find class: " . $class_name . " at path: " . $file);
    }
});
