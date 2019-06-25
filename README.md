Reflection File
--
(c) by Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>

[![Maintainability](https://api.codeclimate.com/v1/badges/3df5fdf0b98867ab87a2/maintainability)](https://codeclimate.com/github/psychob/reflection-file/maintainability) [![Test Coverage](https://api.codeclimate.com/v1/badges/3df5fdf0b98867ab87a2/test_coverage)](https://codeclimate.com/github/psychob/reflection-file/test_coverage) [![Build Status](https://travis-ci.org/psychob/reflection-file.svg?branch=master)](https://travis-ci.org/psychob/reflection-file)

## License
MPL-2.0

## Brief
Missing from base PHP `ReferenceFile`

## Installation
Use composer:

```bash
composer require psychob/reflection-file
```

## Usage
```php
<?php
    use \PsychoB\ReflectionFile\ReflectionFile;

    $reflection = new ReflectionFile($fileName);
    
    $reflection->getFunctions();
    $reflection->getClasses();
    $reflection->getTraits();
    $reflection->getInterfaces();
```
