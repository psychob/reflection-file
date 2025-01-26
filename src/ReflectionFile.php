<?php
    //
    //  psychob/reflection-file
    //  (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\Exception\ClassNotFoundException;
    use PsychoB\ReflectionFile\Exception\FileNotFoundException;
    use PsychoB\ReflectionFile\Exception\FunctionNotFoundException;
    use ReflectionClass;
    use ReflectionException;
    use ReflectionFunction;
    use RuntimeException;

    class ReflectionFile
    {
        /** @var string[] */
        private array $objNamespaces = [];

        /** @var string[] */
        private array $objAbstractClass = [];

        /** @var string[] */
        private array $objInterfaces = [];

        /** @var string[] */
        private array $objTraits = [];

        /** @var string[] */
        private array $objFunctions = [];

        /** @var string[] */
        private array $objClass = [];

        /** @var string[] */
        private array $objObjects = [];

        /** @var string[] */
        private array $objEnums = [];

        /** @var ReflectionClass[] */
        private array $cacheClass = [];

        /** @var ReflectionFunction[] */
        private array $cacheFunctions = [];

        private bool $parsed = false;
        private bool $loaded = false;

        /**
         * ReflectionFile constructor.
         *
         * @param string $fileName File name of loaded file
         * @param bool $deferLoading Defer loading $fileName until one of get* function are called
         *
         * @throws FileNotFoundException File not found
         */
        public function __construct(private readonly string $fileName,
            bool $deferParsing = true,
            bool $deferLoading = true)
        {
            if (!file_exists($this->fileName)) {
                throw new FileNotFoundException($fileName);
            }

            if (!$deferParsing) {
                $this->parse();
            }

            if (!$deferLoading) {
                $this->parse();
                $this->load();
            }
        }

        // get names

        /**
         * Get the names of namespaces defined in the file.
         *
         * @return array The list of namespaces.
         */
        public function getNamesOfNamespaces(): array
        {
            $this->ensureParsed();
            return $this->objNamespaces;
        }

        /**
         * Get the first name of the namespace.
         *
         * @return string|null The first namespace name or null if no namespaces are defined.
         */
        public function getFirstNameOfNamespace(): ?string
        {
            $this->ensureParsed();

            if (empty($this->objNamespaces)) {
                return null;
            }

            return $this->objNamespaces[0];
        }

        public function getNamesOfAbstractClasses(): array
        {
            $this->ensureParsed();

            return $this->objAbstractClass;
        }

        public function getNamesOfInterfaces(): array
        {
            $this->ensureParsed();

            return $this->objInterfaces;
        }

        public function getNamesOfTraits(): array
        {
            $this->ensureParsed();

            return $this->objTraits;
        }

        public function getNamesOfFunctions(): array
        {
            $this->ensureParsed();

            return $this->objFunctions;
        }

        public function getNamesOfClasses(): array
        {
            $this->ensureParsed();

            return $this->objClass;
        }

        public function getNamesOfEnums(): array
        {
            $this->ensureParsed();

            return $this->objEnums;
        }

        public function getNamesOfObjects(): array
        {
            $this->ensureParsed();

            return $this->objObjects;
        }

        /**
         * Load all Abstract Classes and return collection
         *
         * @return ReflectionClass[]
         */
        public function getAbstractClasses(): array
        {
            return $this->fetchObjects(function (ReflectionClass $class)
            {
                return $class->isAbstract() && !$class->isTrait() && !$class->isInterface();
            });
        }

        /**
         * Get Abstract Class with $class name
         *
         * @param string $class Class Name
         *
         * @return ReflectionClass
         *
         * @throws ClassNotFoundException Class not found
         */
        public function getAbstractClass(string $class): ReflectionClass
        {
            return $this->fetchObject(function (ReflectionClass $class)
            {
                return $class->isAbstract() && !$class->isTrait() && !$class->isInterface();
            }, $class);
        }

        /**
         * Get Interfaces defined in file.
         *
         * @return ReflectionClass[]
         */
        public function getInterfaces(): array
        {
            return $this->fetchObjects(function (ReflectionClass $class)
            {
                return $class->isInterface();
            });
        }

        /**
         * Get interface with $interface name
         *
         * @param string $interface
         *
         * @return ReflectionClass
         *
         * @throws ClassNotFoundException Thrown when interface is not found.
         */
        public function getInterface(string $interface): ReflectionClass
        {
            return $this->fetchObject(function (ReflectionClass $class)
            {
                return $class->isInterface();
            }, $interface);
        }

        public function getTraits(): array
        {
            return $this->fetchObjects(function (ReflectionClass $class)
            {
                return $class->isTrait();
            });
        }

        public function getTrait(string $name): ReflectionClass
        {
            return $this->fetchObject(function (ReflectionClass $class)
            {
                return $class->isTrait();
            }, $name);
        }

        public function getFunctions(): array
        {
            $this->ensureLoaded();

            if (empty($this->cacheFunctions) && !empty($this->objFunctions)) {
                foreach ($this->objFunctions as $function) {
                    try {
                        $this->cacheFunctions[] = new ReflectionFunction($function);
                        // @codeCoverageIgnoreStart
                    } catch (ReflectionException $e) {
                        // should never happen
                        throw new RuntimeException("Failed loading function: {$function}", 0, $e);
                    }
                    // @codeCoverageIgnoreEnd
                }
            }

            return $this->cacheFunctions;
        }

        public function getFunction(string $name): ReflectionFunction
        {
            foreach ($this->getFunctions() as $function) {
                if ($function->getName() === $name) {
                    return $function;
                }
            }

            throw new FunctionNotFoundException($name);
        }

        /**
         * Get all classes in file (traits, interfaces, abstract classes, classes).
         *
         * @return ReflectionClass[]
         *
         * @throws ReflectionException
         */
        public function getClasses(): array
        {
            return $this->fetchObjects(function (ReflectionClass $class)
            {
                return !$class->isAbstract() && !$class->isInterface() && !$class->isTrait();
            });
        }

        /**
         * Get class from file with $name.
         *
         * @param string $name
         *
         * @return ReflectionClass
         *
         * @throws ClassNotFoundException When
         */
        public function getClass(string $name): ReflectionClass
        {
            return $this->fetchObject(function (ReflectionClass $class)
            {
                return !$class->isAbstract() && !$class->isInterface() && !$class->isTrait();
            }, $name);
        }

        public function getEnums(): array
        {
            return $this->fetchObjects(function (ReflectionClass $class)
            {
                return $class->isEnum();
            });
        }

        /**
         * Get all objects defined in file (traits, interfaces, abstract classes, classes).
         *
         * @return ReflectionClass[]
         */
        public function getObjects(): array
        {
            $this->ensureLoaded();

            if (empty($this->cacheClass) && !empty($this->objObjects)) {
                foreach ($this->objObjects as $object) {
                    try {
                        $this->cacheClass[] = new ReflectionClass($object);
                        // @codeCoverageIgnoreStart
                    } catch (ReflectionException $e) {
                        // should never happen
                        throw new RuntimeException("Failed loading class: {$object}", 0, $e);
                    }
                    // @codeCoverageIgnoreEnd
                }
            }

            return $this->cacheClass;
        }

        /**
         * Get object with $name from pool of all defined objects (traits, interfaces, abstract classes, classes) in
         * file.
         *
         * @param string $name
         *
         * @return ReflectionClass
         * @throws ClassNotFoundException
         */
        public function getObject(string $name): ReflectionClass
        {
            return $this->fetchObject(function ()
            {
                return true; // no filtering
            }, $name);
        }

        /**
         * @param callable $filter
         *
         * @return array
         */
        protected function fetchObjects(callable $filter): array
        {
            $this->ensureParsed();

            $ret = [];

            foreach ($this->getObjects() as $object) {
                if ($filter($object)) {
                    $ret[] = $object;
                }
            }

            return $ret;
        }

        /**
         * @param callable $filter
         * @param string $name
         *
         * @return ReflectionClass
         * @throws ClassNotFoundException
         */
        protected function fetchObject(callable $filter, string $name): ReflectionClass
        {
            $this->ensureParsed();

            foreach ($this->fetchObjects($filter) as $class) {
                if ($class->getName() === $name) {
                    return $class;
                }
            }

            throw new ClassNotFoundException($name);
        }

        private function parse()
        {
            $this->cacheClass = [];
            $this->cacheFunctions = [];

            $parser = new Parser($this->fileName);
            [$this->objClass,
                $this->objObjects,
                $this->objInterfaces,
                $this->objNamespaces,
                $this->objAbstractClass,
                $this->objFunctions,
                $this->objTraits,
                $this->objEnums] = $parser->parse();

            $this->parsed = true;
            $this->loaded = false;
        }

        public function load(): void
        {
            __anonymous_load_file($this->fileName);
            $this->loaded = true;
        }

        private function ensureParsed(): void
        {
            if (!$this->parsed) {
                $this->parse();
            }
        }

        private function ensureLoaded(): void
        {
            $this->ensureParsed();

            if (!$this->loaded) {
                $this->load();
            }
        }

    }

    function __anonymous_load_file(string $fileName)
    {
        // We outsource loading file to free standing function, so loaded file won't have access to $this
        require_once $fileName;
    }
