# ReflectionFile

[![Latest Stable Version](https://img.shields.io/packagist/v/psychob/reflection-file.svg)](https://packagist.org/packages/psychob/reflection-file)
[![License](https://img.shields.io/badge/license-MPL--2.0-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-8892BF.svg)](https://php.net/)

A powerful PHP library for analyzing PHP files through reflection. This library extends PHP's native reflection capabilities by allowing you to examine PHP files without loading them into memory, providing information about classes, interfaces, traits, and other declarations.

## Features

- Analyze PHP files without executing them
- Extract information about:
    - Namespaces
    - Classes (including abstract classes)
    - Interfaces
    - Traits
    - Functions
    - Objects
    - Enums
- Lazy loading support for better performance
- Safe file parsing

## Requirements

- PHP 8.3 or higher
- Composer

## Installation

Install via Composer:

```bash
composer require psychob/reflection-file
```

## Usage

### Basic Usage

```php
<?php
use PsychoB\ReflectionFile\ReflectionFile;

$reflection = new ReflectionFile('path/to/your/file.php');

// Get all class names
$classes = $reflection->getNamesOfClasses();

// Get all interfaces
$interfaces = $reflection->getNamesOfInterfaces();

// Get all traits
$traits = $reflection->getNamesOfTraits();

// Get namespace information
$namespace = $reflection->getFirstNameOfNamespace();
```

### Performance Optimization

The library supports deferred parsing and loading for better performance:

```php
<?php
use PsychoB\ReflectionFile\ReflectionFile;

// Defer both parsing and loading until needed
$reflection = new ReflectionFile(
    'path/to/file.php',
    deferParsing: true,
    deferLoading: true
);

// Only parse when needed
$classes = $reflection->getNamesOfClasses(); // Triggers parsing

// Manual control over loading
$reflection->load(); // Explicitly load the file
```

## Advanced Features

### Namespace Analysis

```php
$namespaces = $reflection->getNamesOfNamespaces();
$firstNamespace = $reflection->getFirstNameOfNamespace();
```

### Class Analysis

```php
$classes = $reflection->getNamesOfClasses();
$abstractClasses = $reflection->getNamesOfAbstractClasses();
```

### Other Declarations

```php
$interfaces = $reflection->getNamesOfInterfaces();
$traits = $reflection->getNamesOfTraits();
$functions = $reflection->getNamesOfFunctions();
$enums = $reflection->getNamesOfEnums();
```

## Security Considerations

This library parses PHP files. Always ensure you're analyzing trusted files, as parsing untrusted PHP files could potentially expose your application to security risks.

## License

This project is licensed under the Mozilla Public License Version 2.0 - see the [LICENSE](https://www.mozilla.org/en-US/MPL/2.0/) file for details.

## Author

- Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
