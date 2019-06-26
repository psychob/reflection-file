<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\Exception\ClassNotFoundException;
    use ReflectionClass;
    use ReflectionException;
    use RuntimeException;

    /**
     * @internal
     */
    trait ReflectionFileBaseTrait
    {
        /** @var string[] */
        protected $objObjects = [];

        /** @var ReflectionClass[] */
        protected $cacheClass = [];

        /**
         * Get all object names in file (traits, interfaces, abstract classes, classes). This method won't load file,
         * only parse it.
         *
         * @return array
         */
        public function getObjectNames(): array
        {
            $this->ensureParsed();

            return $this->objObjects;
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
            return $this->fetchObject(function () {
                return true; // no filtering
            }, $name);
        }

        /**
         * @param callable $filter
         *
         * @return ReflectionClass[]
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
         * @param string   $name
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
    }
