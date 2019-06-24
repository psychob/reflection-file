<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\ReflectionFile;

    class ReflectionFileTest extends TestCase
    {
        private function fileToTest(string $file): string
        {
            return sprintf('%s%sTestFiles%s%s', __DIR__, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $file);
        }

        /** @runInSeparateProcess */
        public function testReflectionFileEmptyFile()
        {
            $reflection = new ReflectionFile($this->fileToTest('EmptyFile.php'), false);

            $this->assertEmpty($reflection->getNamespaces());
            $this->assertEmpty($reflection->getAbstractClasses());
            $this->assertEmpty($reflection->getClasses());
            $this->assertEmpty($reflection->getFunctions());
            $this->assertEmpty($reflection->getInterfaces());
            $this->assertEmpty($reflection->getTraits());
        }

        /** @runInSeparateProcess */
        public function testReflectionFileFunctions()
        {
            $reflection = new ReflectionFile($this->fileToTest('SimpleFunctions.php'), false);

            $this->assertEmpty($reflection->getNamespaces());
            $this->assertEmpty($reflection->getAbstractClasses());
            $this->assertEmpty($reflection->getClasses());
            $this->assertNotEmpty($reflection->getFunctions());
            $this->assertCount(5, $reflection->getFunctions());
            $this->assertEmpty($reflection->getInterfaces());
            $this->assertEmpty($reflection->getTraits());
        }

        /** @runInSeparateProcess */
        public function testReflectionFileNamesapcedFunctions()
        {
            $reflection = new ReflectionFile($this->fileToTest('NamespacedFunctions.php'), false);

            $this->assertNotEmpty($reflection->getNamespaces());
            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles'], $reflection->getNamespaces());
            $this->assertEmpty($reflection->getAbstractClasses());
            $this->assertEmpty($reflection->getClasses());
            $this->assertNotEmpty($reflection->getFunctions());
            $this->assertCount(5, $reflection->getFunctions());
            $this->assertEmpty($reflection->getInterfaces());
            $this->assertEmpty($reflection->getTraits());
        }

        /** @runInSeparateProcess */
        public function testReflectionFileDoubleNamesapcedFunctions()
        {
            $reflection = new ReflectionFile($this->fileToTest('DoubleNamespaceFunctions.php'), false);

            $this->assertNotEmpty($reflection->getNamespaces());
            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\Single',
                                 'Tests\PsychoB\ReflectionFile\TestFiles\Double'], $reflection->getNamespaces());
            $this->assertEmpty($reflection->getAbstractClasses());
            $this->assertEmpty($reflection->getClasses());
            $this->assertNotEmpty($reflection->getFunctions());
            $this->assertCount(5, $reflection->getFunctions());
            $this->assertEmpty($reflection->getInterfaces());
            $this->assertEmpty($reflection->getTraits());
        }

        public function testReflectionFileClass()
        {
            $reflection = new ReflectionFile($this->fileToTest('SimpleClass.php'), false);

            $this->assertNotEmpty($reflection->getNamespaces());
            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles'], $reflection->getNamespaces());
            $this->assertEmpty($reflection->getAbstractClasses());
            $this->assertNotEmpty($reflection->getClasses());
            $this->assertCount(1, $reflection->getClasses());
            $this->assertEmpty($reflection->getFunctions());
            $this->assertEmpty($reflection->getInterfaces());
            $this->assertEmpty($reflection->getTraits());
        }

        public function testReflectionFileMultipleClasses()
        {
            $reflection = new ReflectionFile($this->fileToTest('MultipleClasses.php'), false);

            $this->assertNotEmpty($reflection->getNamespaces());
            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\Classes'], $reflection->getNamespaces());
            $this->assertNotEmpty($reflection->getAbstractClasses());
            $this->assertCount(1, $reflection->getAbstractClasses());
            $this->assertNotEmpty($reflection->getClasses());
            $this->assertCount(5, $reflection->getClasses());
            $this->assertEmpty($reflection->getFunctions());
            $this->assertNotEmpty($reflection->getInterfaces());
            $this->assertCount(1, $reflection->getInterfaces());
            $this->assertNotEmpty($reflection->getTraits());
            $this->assertCount(1, $reflection->getTraits());
        }

        public function testReflectionFileInjector()
        {
            $reflection = new ReflectionFile($this->fileToTest('BadFormatting.php'), false);

            $this->assertNotEmpty($reflection->getNamespaces());
            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\BadFormattingOf'], $reflection->getNamespaces());
            $this->assertEmpty($reflection->getAbstractClasses());
            $this->assertNotEmpty($reflection->getClasses());
            $this->assertCount(1, $reflection->getClasses());
            $this->assertEmpty($reflection->getFunctions());
            $this->assertEmpty($reflection->getInterfaces());
            $this->assertEmpty($reflection->getTraits());
        }
    }
