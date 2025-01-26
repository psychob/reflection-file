<?php
    //
    //  psychob/reflection-file
    //  (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile\TestFiles;

    enum PhpEnum
    {
        case NewEnum;
    }


    enum PhpEnumWithBackingType: int
    {
        case NewEnum = 1;
    }
