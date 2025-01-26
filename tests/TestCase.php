<?php
    //
    //  psychob/reflection-file
    //  (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile;

    use PHPUnit\Framework\TestCase as PhpUnitTestCase;

    class TestCase extends PhpUnitTestCase
    {
        protected function tearDown(): void
        {
            if ($container = \Mockery::getContainer()) {
                $this->addToAssertionCount($container->mockery_getExpectationCount());
            }

            \Mockery::close();
        }
    }
