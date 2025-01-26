<?php
    //
    //  psychob/reflection-file
    //  (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile\TestFiles;

    function simple_functions_foo() {}

    function simple_functions_bar(int $abc) {}

    function simple_functions_baz(int $abc): string
    {
        if ($abc == 1) {
            //
        }

        switch ($abc) {
            case 2:
                {

                }
        }
    }

    function simple_functions_faz(int $abc, \Iterator $cde): string {}

    function simple_functions_far(int $abc, \Iterator $cde): \ArrayAccess {}
