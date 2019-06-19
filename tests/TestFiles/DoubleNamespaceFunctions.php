<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile\TestFiles\Single
    {

        function simple_functions_foo()
        {
        }

        function simple_functions_bar(int $abc)
        {
        }
    }

    namespace Tests\PsychoB\ReflectionFile\TestFiles\Double
    {

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

        function simple_functions_faz(int $abc, \Iterator $cde): string
        {
        }

        function simple_functions_far(int $abc, \Iterator $cde): \ArrayAccess
        {
        }
    }
