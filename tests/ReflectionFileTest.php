<?php
    //
    //  psychob/reflection-file
    //  (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\Exception\FileNotFoundException;
    use PsychoB\ReflectionFile\ReflectionFile;
    use Tests\PsychoB\ReflectionFile\TestFiles\Classes\AbstractClass;
    use Tests\PsychoB\ReflectionFile\TestFiles\Classes\FinalClass;
    use Tests\PsychoB\ReflectionFile\TestFiles\Classes\InterfaceForClass;
    use Tests\PsychoB\ReflectionFile\TestFiles\Classes\SimpleClass as SimpleClassClasses;
    use Tests\PsychoB\ReflectionFile\TestFiles\Classes\TraitForClass;
    use Tests\PsychoB\ReflectionFile\TestFiles\SimpleClass;

    class ReflectionFileTest extends TestCase
    {
        private function fileToTest(string $file): string
        {
            return sprintf('%s%sTestFiles%s%s', __DIR__, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $file);
        }

        private function assertReflectionFileCount(ReflectionFile $file,
            int $namespaces,
            int $abstractClasses,
            int $classes,
            int $functions,
            int $interfaces,
            int $traits,
            int $enums = 0)
        {
            $this->assertCount($namespaces, $file->getNamesOfNamespaces(), 'Invalid namespace count');
            $this->assertCount($abstractClasses, $file->getNamesOfAbstractClasses(), 'Invalid abstract classes count');
            $this->assertCount($classes, $file->getNamesOfClasses(), 'Invalid classes count');
            $this->assertCount($functions, $file->getNamesOfFunctions(), 'Invalid function count');
            $this->assertCount($interfaces, $file->getNamesOfInterfaces(), 'Invalid interfaces count');
            $this->assertCount($traits, $file->getNamesOfTraits(), 'Invalid traits count');
            $this->assertCount($enums, $file->getNamesOfEnums(), 'Invalid enum count');
        }

        private function assertReflectionFileClasses(array $classes, array $names)
        {
            foreach ($classes as $class) {
                $this->assertContains($class->getName(), $names);
            }
        }

        private function assertReflectionFileIsNotLoaded(ReflectionFile $file): void
        {
            $this->assertFalse($file->isLoaded());
        }

        public function testReflectionFileEmptyFile()
        {
            $reflection = new ReflectionFile($this->fileToTest('EmptyFile.php'));

            $this->assertReflectionFileCount($reflection, 0, 0, 0, 0, 0, 0);
        }

        public function testReflectionFileFunctions()
        {
            $reflection = new ReflectionFile($this->fileToTest('SimpleFunctions.php'));

            $this->assertReflectionFileCount($reflection, 0, 0, 0, 5, 0, 0);
            $this->assertSame(['simple_functions_foo',
                'simple_functions_bar',
                'simple_functions_baz',
                'simple_functions_faz',
                'simple_functions_far'], $reflection->getNamesOfFunctions());
        }

        public function testReflectionFileNamespacedFunctions()
        {
            $reflection = new ReflectionFile($this->fileToTest('NamespacedFunctions.php'));

            $this->assertReflectionFileCount($reflection, 1, 0, 0, 5, 0, 0);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles'], $reflection->getNamesOfNamespaces());
            $this->assertSame(['Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_foo',
                'Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_bar',
                'Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_baz',
                'Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_faz',
                'Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_far'], $reflection->getNamesOfFunctions());
        }

        public function testReflectionFileDoubleNamespacedFunctions()
        {
            $reflection = new ReflectionFile($this->fileToTest('DoubleNamespaceFunctions.php'));

            $this->assertReflectionFileCount($reflection, 2, 0, 0, 5, 0, 0);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\Single',
                'Tests\PsychoB\ReflectionFile\TestFiles\Double'], $reflection->getNamesOfNamespaces());
        }

        public function testReflectionFileClass()
        {
            $reflection = new ReflectionFile($this->fileToTest('SimpleClass.php'));

            $this->assertReflectionFileCount($reflection, 1, 0, 1, 0, 0, 0);
            $this->assertSame([SimpleClass::class], $reflection->getNamesOfClasses());
            $this->assertReflectionFileIsNotLoaded($reflection);

            $refClass = new \ReflectionClass(SimpleClass::class);
            $this->assertInstanceOf(\ReflectionClass::class, $refClass);
        }

        public function testReflectionFileMultipleClasses()
        {
            $reflection = new ReflectionFile($this->fileToTest('MultipleClasses.php'));

            $this->assertReflectionFileCount($reflection, 1, 1, 2, 0, 1, 1);
            $this->assertReflectionFileIsNotLoaded($reflection);
            $this->assertSame([SimpleClassClasses::class, FinalClass::class], $reflection->getNamesOfClasses());
            $this->assertSame([AbstractClass::class,], $reflection->getNamesOfAbstractClasses());
            $this->assertSame([InterfaceForClass::class,], $reflection->getNamesOfInterfaces());
            $this->assertSame([TraitForClass::class,], $reflection->getNamesOfTraits());

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\Classes'],
                $reflection->getNamesOfNamespaces());
        }

        public function testReflectionFileInjector()
        {
            $reflection = new ReflectionFile($this->fileToTest('BadFormatting.php'));

            $this->assertReflectionFileCount($reflection, 1, 0, 1, 0, 0, 0);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\BadFormattingOf'],
                $reflection->getNamesOfNamespaces());
        }

        public function testReflectionFileDontExist()
        {
            $this->expectException(FileNotFoundException::class);

            $reflection = new ReflectionFile($this->fileToTest('NotExistingFile.php'));
        }

        public function testReflectionFileRequireFiles()
        {
            $reflection = new ReflectionFile($this->fileToTest('ReallyOldPhp.php'));

            $this->assertReflectionFileCount($reflection, 0, 0, 0, 1, 0, 0);
        }

        public function testReflectionFileWebIndex()
        {
            $reflection = new ReflectionFile($this->fileToTest('WebIndex.php'));

            $this->assertReflectionFileCount($reflection, 0, 0, 0, 0, 0, 0);
        }

        public function testReflectionFileNoPHP()
        {
            $reflection = new ReflectionFile($this->fileToTest('FileWithoutPHP.php'));

            $this->assertReflectionFileCount($reflection, 0, 0, 0, 0, 0, 0);
        }

        public function testReflectionFileWithAttribute()
        {
            $reflection = new ReflectionFile($this->fileToTest('WithAttributes.php'));

            $this->assertReflectionFileCount($reflection, 1, 0, 2, 0, 0, 0);
        }

        public function testReflectionFilePhpEnum()
        {
            $reflection = new ReflectionFile($this->fileToTest('PhpEnum.php'));

            $this->assertReflectionFileCount($reflection, 1, 0, 0, 0, 0, 0, 2);
        }
    }
