<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace Tests\PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\Exception\ClassNotFoundException;
    use PsychoB\ReflectionFile\Exception\FileNotFoundException;
    use PsychoB\ReflectionFile\Exception\FunctionNotFoundException;
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

        private function assertReflectionFileClasses(array $classes, array $names)
        {
            foreach ($classes as $class) {
                $this->assertContains($class->getName(), $names);
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
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getObject(SimpleClass::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getClass(SimpleClass::class));

            $this->assertReflectionFileClasses($reflection->getClasses(), [SimpleClass::class]);
        }

        public function testReflectionFileClassFailure()
        {
            $reflection = new ReflectionFile($this->fileToTest('SimpleClass.php'), false);

            $this->assertReflectionFileCount($reflection, 1, 0, 1, 0, 0, 0);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles',], $reflection->getNamespaceNames());
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getObject(SimpleClass::class));

            $this->expectException(ClassNotFoundException::class);
            $reflection->getInterface(SimpleClass::class);
        }

        public function testReflectionFileMultipleClasses()
        {
            $reflection = new ReflectionFile($this->fileToTest('MultipleClasses.php'), false);

            $this->assertReflectionFileCount($reflection, 1, 1, 2, 0, 1, 1);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\Classes'], $reflection->getNamespaceNames());

            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getObject(SimpleClassClasses::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getObject(AbstractClass::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getObject(InterfaceForClass::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getObject(TraitForClass::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getObject(FinalClass::class));

            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getClass(SimpleClassClasses::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getAbstractClass(AbstractClass::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getInterface(InterfaceForClass::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getTrait(TraitForClass::class));
            $this->assertInstanceOf(\ReflectionClass::class, $reflection->getClass(FinalClass::class));

            $this->assertReflectionFileClasses($reflection->getClasses(),
                                               [SimpleClassClasses::class, FinalClass::class]);
            $this->assertReflectionFileClasses($reflection->getAbstractClasses(), [AbstractClass::class]);
            $this->assertReflectionFileClasses($reflection->getInterfaces(), [InterfaceForClass::class]);
            $this->assertReflectionFileClasses($reflection->getTraits(), [TraitForClass::class]);

            $this->assertReflectionFileClasses($reflection->getObjects(),
                                               [SimpleClassClasses::class, FinalClass::class, AbstractClass::class,
                                                InterfaceForClass::class, TraitForClass::class]);
        }

        public function testReflectionFileInjector()
        {
            $reflection = new ReflectionFile($this->fileToTest('BadFormatting.php'));

            $this->assertReflectionFileCount($reflection, 1, 0, 1, 0, 0, 0);

            $this->assertEquals(['Tests\PsychoB\ReflectionFile\TestFiles\BadFormattingOf'],
                                $reflection->getNamespaceNames());
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
    }
