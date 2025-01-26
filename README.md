Reflection File
--
(c) by Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>

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
    
    $reflection->getNamesOfFunctions();
    $reflection->getNamesOfClasses();
    $reflection->getNamesOfTraits();
    $reflection->getNamesOfInterfaces();
```
