<?php
    //
    // psychob/reflection-file
    // (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile\TestFiles;

    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS)]
    class MyAttribute
    {
        //
    }

    #[MyAttribute]
    class WithAttributes
    {
        //
    }
