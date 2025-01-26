<?php
    //
    //  psychob/reflection-file
    //  (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\Exception\FileNotFoundException;

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
                $this->_parse();
            }

            if (!$deferLoading) {
                $this->_parse();
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
            $this->_ensureParsed();

            return $this->objNamespaces;
        }

        /**
         * Get the first name of the namespace.
         *
         * @return string|null The first namespace name or null if no namespaces are defined.
         */
        public function getFirstNameOfNamespace(): ?string
        {
            $this->_ensureParsed();

            if (empty($this->objNamespaces)) {
                return null;
            }

            return $this->objNamespaces[0];
        }

        /**
         * Get the names of abstract classes defined in the file.
         *
         * @return array The list of abstract class names.
         */
        public function getNamesOfAbstractClasses(): array
        {
            $this->_ensureParsed();

            return $this->objAbstractClass;
        }

        /**
         * Get the names of interfaces defined in the file.
         *
         * @return array The list of interfaces.
         */
        public function getNamesOfInterfaces(): array
        {
            $this->_ensureParsed();

            return $this->objInterfaces;
        }

        /**
         * Get the names of traits defined in the file.
         *
         * @return array The list of traits.
         */
        public function getNamesOfTraits(): array
        {
            $this->_ensureParsed();

            return $this->objTraits;
        }

        /**
         * Get the names of functions defined in the file.
         *
         * @return array The list of functions.
         */
        public function getNamesOfFunctions(): array
        {
            $this->_ensureParsed();

            return $this->objFunctions;
        }

        /**
         * Get the names of classes defined in the file.
         *
         * @return array The list of class names.
         */
        public function getNamesOfClasses(): array
        {
            $this->_ensureParsed();

            return $this->objClass;
        }

        /**
         * Get the names of enums defined in the file.
         *
         * @return array The list of enum names.
         */
        public function getNamesOfEnums(): array
        {
            $this->_ensureParsed();

            return $this->objEnums;
        }

        /**
         * Get the names of objects defined in the file.
         *
         * @return array The list of objects.
         */
        public function getNamesOfObjects(): array
        {
            $this->_ensureParsed();

            return $this->objObjects;
        }

        private function _parse(): void
        {
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

        private function _ensureParsed(): void
        {
            if (!$this->parsed) {
                $this->_parse();
            }
        }

        public function isLoaded(): bool
        {
            return $this->loaded;
        }

        private function _ensureLoaded(): void
        {
            $this->_ensureParsed();

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
