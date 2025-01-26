<?php
    //
    //  psychob/reflection-file
    //  (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile\TestFiles\BadFormattingOf;

    class BadFormatting
    {
        function bad_formatting()
        {
            // in old version of parsing this would fail
            return "{$foo}";
        }


        protected function good_formatting()
        {
            return '{$foo}';
        }
    }
