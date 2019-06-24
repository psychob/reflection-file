<?php
    //
    // dependency-injection
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile\TestFiles\BadFormattingOf;

    class BadFormatting
    {
        function bad_formatting()
        {
            return "{$foo}";
        }


        protected function good_formatting()
        {
            // in old version of parsing this would fail
        }
    }
