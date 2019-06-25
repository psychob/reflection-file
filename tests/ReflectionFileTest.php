<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\Exception\FunctionNotFoundException;
    use PsychoB\ReflectionFile\ReflectionFile;
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
                                                   int $traits)
        {
            $this->assertCount($namespaces, $file->getNamespaceNames(), 'Invalid namespace count');
            $this->assertCount($abstractClasses, $file->getAbstractClassNames(), 'Invalid abstract classes count');
            $this->assertCount($classes, $file->getClassNames(), 'Invalid classes count');
            $this->assertCount($functions, $file->getFunctionNames(), 'Invalid function count');
            $this->assertCount($interfaces, $file->getInterfaceNames(), 'Invalid interfaces count');
            $this->assertCount($traits, $file->getTraitNames(), 'Invalid traits count');
        }

        private function assertReflectionFileFunctions(ReflectionFile $file, array $functions)
        {
            foreach ($functions as $fun) {
                $this->assertInstanceOf(\ReflectionFunction::class, $file->getFunction($fun));
            }
        }

        /** @runInSeparateProcess */
        public function testReflectionFileEmptyFile()
        {
            $reflection = new ReflectionFile($this->fileToTest('EmptyFile.php'), false);

            $this->assertReflectionFileCount($reflection, 0, 0, 0, 0, 0, 0);
        }

        /** @runInSeparateProcess */
        public function testReflectionFileFunctions()
        {
            $reflection = new ReflectionFile($this->fileToTest('SimpleFunctions.php'), false);

            $this->assertReflectionFileCount($reflection, 0, 0, 0, 5, 0, 0);
            $this->assertReflectionFileFunctions($reflection, ['simple_functions_foo', 'simple_functions_bar',
                                                               'simple_functions_baz', 'simple_functions_faz',
                                                               'simple_functions_far']);
        }

        /** @runInSeparateProcess */
        public function testReflectionFileFunctionsFailure()
        {
            $reflection = new ReflectionFile($this->fileToTest('SimpleFunctions.php'), false);

            $this->expectException(FunctionNotFoundException::class);
            $reflection->getFunction('function_that_dosent_exist');
        }

        /** @runInSeparateProcess */
        public function testReflectionFileNamesapcedFunctions()
        {
            $reflection = new ReflectionFile($this->fileToTest('NamespacedFunctions.php'), false);

            $this->assertReflectionFileCount($reflection, 1, 0, 0, 5, 0, 0);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles'], $reflection->getNamespaceNames());
            $this->assertReflectionFileFunctions($reflection,
                                                 ['Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_foo',
                                                  'Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_bar',
                                                  'Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_baz',
                                                  'Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_faz',
                                                  'Tests\PsychoB\ReflectionFile\TestFiles\simple_functions_far']);
        }

        /** @runInSeparateProcess */
        public function testReflectionFileDoubleNamesapcedFunctions()
        {
            $reflection = new ReflectionFile($this->fileToTest('DoubleNamespaceFunctions.php'), false);

            $this->assertReflectionFileCount($reflection, 2, 0, 0, 5, 0, 0);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\Single',
                                 'Tests\PsychoB\ReflectionFile\TestFiles\Double'], $reflection->getNamespaceNames());
        }

        public function testReflectionFileClass()
        {
            $reflection = new ReflectionFile($this->fileToTest('SimpleClass.php'), false);

            $this->assertReflectionFileCount($reflection, 1, 0, 1, 0, 0, 0);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles',], $reflection->getNamespaceNames());
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getClass(SimpleClass::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getObject(SimpleClass::class));
        }

        public function testReflectionFileMultipleClasses()
        {
            $reflection = new ReflectionFile($this->fileToTest('MultipleClasses.php'), false);

            $this->assertReflectionFileCount($reflection, 1, 1, 2, 0, 1, 1);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\Classes'], $reflection->getNamespaceNames());
        }

        public function testReflectionFileInjector()
        {
            $reflection = new ReflectionFile($this->fileToTest('BadFormatting.php'), false);

            $this->assertReflectionFileCount($reflection, 1, 0, 1, 0, 0, 0);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\BadFormattingOf'],
                                $reflection->getNamespaceNames());
        }
    }
