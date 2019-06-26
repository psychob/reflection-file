<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\Exception\ClassNotFoundException;
    use PsychoB\ReflectionFile\Exception\FileNotFoundException;
    use PsychoB\ReflectionFile\Exception\FunctionNotFoundException;
    use PsychoB\ReflectionFile\Exception\InvalidTokenException;
    use ReflectionClass;
    use ReflectionException;
    use ReflectionFunction;
    use RuntimeException;

    class ReflectionFile
    {
        use ReflectionFileBaseTrait;

        /** @var string */
        protected $fileName;

        /** @var string[] */
        protected $objNamespaces = [];

        /** @var string[] */
        protected $objAbstractClass = [];

        /** @var string[] */
        protected $objInterfaces = [];

        /** @var string[] */
        protected $objTraits = [];

        /** @var string[] */
        protected $objFunctions = [];

        /** @var string[] */
        protected $objClass = [];

        /** @var ReflectionFunction[] */
        protected $cacheFunctions = [];

        protected $parsed = false;
        protected $loaded = false;

        /**
         * ReflectionFile constructor.
         *
         * @param string $fileName     File name of loaded file
         * @param bool   $deferLoading Defer loading $fileName until one of get* function are called
         *
         * @throws FileNotFoundException File not found
         */
        public function __construct(string $fileName, bool $deferLoading = true)
        {
            $this->fileName = $fileName;

            if (!file_exists($this->fileName)) {
                throw new FileNotFoundException($fileName);
            }

            if (!$deferLoading) {
                $this->parse();
                $this->load();
            }
        }

        /**
         * Get namespace names defined in file. This method won't load file, only parse it.
         *
         * @return string[]
         */
        public function getNamespaceNames(): array
        {
            $this->ensureParsed();

            return $this->objNamespaces;
        }

        /**
         * Get abstract class names defined in file. This method won't load file, only parse it.
         *
         * @return string[]
         */
        public function getAbstractClassNames(): array
        {
            $this->ensureParsed();

            return $this->objAbstractClass;
        }

        /**
         * Load all Abstract Classes and return collection
         *
         * @return ReflectionClass[]
         */
        public function getAbstractClasses(): array
        {
            return $this->fetchObjects(function (ReflectionClass $class) {
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
            return $this->fetchObject(function (ReflectionClass $class) {
                return $class->isAbstract() && !$class->isTrait() && !$class->isInterface();
            }, $class);
        }

        /**
         * Get interfaces names defined in file. This method won't load file, only parse it.
         *
         * @return string[]
         */
        public function getInterfaceNames(): array
        {
            $this->ensureParsed();

            return $this->objInterfaces;
        }

        /**
         * Get Interfaces defined in file.
         *
         * @return ReflectionClass[]
         */
        public function getInterfaces(): array
        {
            return $this->fetchObjects(function (ReflectionClass $class) {
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
            return $this->fetchObject(function (ReflectionClass $class) {
                return $class->isInterface();
            }, $interface);
        }

        public function getTraitNames(): array
        {
            $this->ensureParsed();

            return $this->objTraits;
        }

        public function getTraits(): array
        {
            return $this->fetchObjects(function (ReflectionClass $class) {
                return $class->isTrait();
            });
        }

        public function getTrait(string $name): ReflectionClass
        {
            return $this->fetchObject(function (ReflectionClass $class) {
                return $class->isTrait();
            }, $name);
        }

        public function getFunctionNames(): array
        {
            $this->ensureParsed();

            return $this->objFunctions;
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
         * Get all classes names in file. This method won't load file, only parse it.
         *
         * @return string[]
         */
        public function getClassNames(): array
        {
            if ($this->parsed) {
                $this->parse();
            }

            return $this->objClass;
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
            return $this->fetchObjects(function (ReflectionClass $class) {
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
            return $this->fetchObject(function (ReflectionClass $class) {
                return !$class->isAbstract() && !$class->isInterface() && !$class->isTrait();
            }, $name);
        }

        private function parse()
        {
            $this->objClass = [];
            $this->objObjects = [];
            $this->objInterfaces = [];
            $this->objNamespaces = [];
            $this->objAbstractClass = [];
            $this->objFunctions = [];
            $this->objTraits = [];

            $this->cacheClass = [];
            $this->cacheFunctions = [];

            $loaded = file_get_contents($this->fileName);

            $tokens = token_get_all($loaded);
            $tokenCount = count($tokens);

            for ($it = 0; $it < $tokenCount; ++$it) {
                // we want to start parsing with T_PHP_OPEN
                $this->parse_skipToToken($tokens, $it, $tokenCount, T_OPEN_TAG);
                $this->parse_phpContent($tokens, $it, $tokenCount);
            }

            $this->parsed = true;
            $this->loaded = false;
        }

        public function load(): void
        {
            __anonymous_load_file($this->fileName);
            $this->loaded = true;
        }

        private function parse_skipToToken(array $tokens, int &$it, int $tokenCount, $upTo): void
        {
            if (!is_array($upTo)) {
                $upTo = [$upTo];
            }

            for (; $it < $tokenCount; ++$it) {
                if (is_array($tokens[$it]) && in_array($tokens[$it][0], $upTo)) {
                    return;
                }
            }
        }

        private function parse_skipToSymbol(array $tokens, int &$it, int $tokenCount, $upTo): void
        {
            if (!is_array($upTo)) {
                $upTo = [$upTo];
            }

            for (; $it < $tokenCount; ++$it) {
                if (!is_array($tokens[$it]) && in_array($tokens[$it], $upTo)) {
                    return;
                }
            }
        }

        private function parse_phpContent(array $tokens,
                                          int &$it,
                                          int $tokenCount,
                                          string $namespace = '',
                                          bool $subExpression = false): void
        {
            if (!$subExpression) {
                $this->assertToken($tokens, $it, T_OPEN_TAG);
            } else {
                $this->assertSymbol($tokens, $it, '{');
            }

            $it++;

            for (; $it < $tokenCount; ++$it) {
                $currentToken = $tokens[$it];

                if (is_array($currentToken)) {
                    switch ($currentToken[0]) {
                        case T_WHITESPACE:
                        case T_COMMENT:
                        case T_DOC_COMMENT:
                            continue;

                        case T_USE:
                            $this->parse_use($tokens, $it, $tokenCount);
                            break;
                        //
                        //                        case T_CLOSE_TAG:
                        //                            if (!$subExpression)
                        //                                return;
                        //                            break;
                        //
                        case T_FUNCTION:
                            $this->parse_function($tokens, $it, $tokenCount, $namespace);
                            break;

                        case T_NAMESPACE:
                            $namespace = $this->parse_namespace($tokens, $it, $tokenCount);
                            break;

                        case T_ABSTRACT:
                            $this->parse_abstractClass($tokens, $it, $tokenCount, $namespace);
                            break;

                        case T_FINAL:
                            $this->parse_finalClass($tokens, $it, $tokenCount, $namespace);
                            break;

                        case T_CLASS:
                            $this->parse_class($tokens, $it, $tokenCount, $namespace);
                            break;

                        case T_TRAIT:
                            $this->parse_trait($tokens, $it, $tokenCount, $namespace);
                            break;

                        case T_INTERFACE:
                            $this->parse_interface($tokens, $it, $tokenCount, $namespace);
                            break;

                        default:
                            throw new InvalidTokenException($tokens, $it);
                    }
                } else {
                    switch ($currentToken) {
                        case '}':
                            if ($subExpression) {
                                return;
                            }

                        default:
                            throw new InvalidTokenException($tokens, $it);
                    }
                }
            }
        }

        private function parse_function(array $tokens,
                                        int &$it,
                                        int $tokenCount,
                                        string $namespace,
                                        bool $skip = false): void
        {
            $this->assertToken($tokens, $it, T_FUNCTION);
            $it += 2; // we skip over function and whitespace

            if (!$skip) {
                if (empty($namespace)) {
                    $this->objFunctions[] = $tokens[$it][1];
                } else {
                    $this->objFunctions[] = $namespace . '\\' . $tokens[$it][1];
                }
            }

            // skip over prologue of function
            $this->parse_skipToSymbol($tokens, $it, $tokenCount, '{');

            // now we balance brackets
            $this->parse_balanceBrackets($tokens, $it, $tokenCount);
        }

        private function parse_balanceBrackets(array $tokens, int &$it, int $tokenCount, int $depth = 0)
        {
            $this->assertSymbol($tokens, $it, '{');
            $it++;

            for (; $it < $tokenCount; ++$it) {
                $this->parse_skipToSymbol($tokens, $it, $tokenCount, ['{', '}', '"']);

                if ($it < $tokenCount) {
                    switch ($tokens[$it]) {
                        case '{':
                            $this->parse_balanceBrackets($tokens, $it, $tokenCount, $depth + 1);
                            break;

                        case '}':
                            $it++;
                            return;

                        case '"':
                            $this->parse_string($tokens, $it, $tokenCount, $tokens[$it]);
                            break;
                    }
                }
            }
        }

        private function parse_namespace(array $tokens, int &$it, int $tokenCount): string
        {
            // this function can either swallow full namespace block
            // in form of:
            // namespace X {
            // }
            // or just:
            // namespace X;
            // and return X

            $this->assertToken($tokens, $it, T_NAMESPACE);
            $it += 2; // we skip over namespace and whitespace

            $ns = '';

            for (; $it < $tokenCount; ++$it) {
                if (!is_array($tokens[$it]) && $tokens[$it] === ';') {
                    $this->objNamespaces[] = $ns;
                    return $ns;
                } else if (!is_array($tokens[$it]) && $tokens[$it] === '{') {
                    $this->objNamespaces[] = $ns;
                    $this->parse_phpContent($tokens, $it, $tokenCount, $ns, true);
                    return '';
                } else {
                    switch ($tokens[$it][0]) {
                        case T_STRING:
                        case T_NS_SEPARATOR:
                            $ns .= $tokens[$it][1];
                            break;

                        case T_WHITESPACE:
                            continue;

                        default:
                            throw new InvalidTokenException($tokens, $it);
                    }
                }
            }

            return '';
        }

        private function parse_finalClass(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_FINAL);
            $it += 4;

            $this->objClass[] = $this->parse_classViscera($tokens, $it, $tokenCount, $namespace);
        }

        private function parse_abstractClass(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_ABSTRACT);
            $it += 4;

            $this->objAbstractClass[] = $this->parse_classViscera($tokens, $it, $tokenCount, $namespace);
        }

        private function parse_trait(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_TRAIT);
            $it += 2;

            $this->objTraits[] = $this->parse_classViscera($tokens, $it, $tokenCount, $namespace);
        }

        private function parse_interface(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_INTERFACE);
            $it += 2;

            $this->objInterfaces[] = $this->parse_classViscera($tokens, $it, $tokenCount, $namespace);
        }

        private function parse_class(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_CLASS);
            $it += 2;

            $this->objClass[] = $this->parse_classViscera($tokens, $it, $tokenCount, $namespace);
        }

        /**
         * @param array  $tokens
         * @param int    $it
         * @param int    $tokenCount
         * @param string $namespace
         *
         * @return string
         */
        private function parse_classViscera(array $tokens, int &$it, int $tokenCount, string $namespace): string
        {
            if (empty($namespace)) {
                $name = $tokens[$it][1];
            } else {
                $name = $namespace . '\\' . $tokens[$it][1];
            }

            $this->objObjects[] = $name;

            // skip over prologue of function
            $this->parse_skipToSymbol($tokens, $it, $tokenCount, '{');

            // now we balance brackets
            $this->parse_balanceBrackets($tokens, $it, $tokenCount);

            return $name;
        }

        private function parse_use(array $tokens, int &$it, int $tokenCount)
        {
            $this->assertToken($tokens, $it, T_USE);

            $this->parse_skipToSymbol($tokens, $it, $tokenCount, ';');
        }

        private function parse_string(array $tokens, int &$it, int $tokenCount, string $deli)
        {
            $this->assertSymbol($tokens, $it, $deli);
            $it++;

            $this->parse_skipToSymbol($tokens, $it, $tokenCount, $deli);
        }

        private function assertToken(array $tokens, int $it, int $type): void
        {
            if (!(is_array($tokens[$it]) && $tokens[$it][0] === $type)) {
                throw new InvalidTokenException($tokens, $it, $type);
            }
        }

        private function assertSymbol(array $tokens, int $it, string $symbol): void
        {
            if (!(!is_array($tokens[$it]) && $tokens[$it] === $symbol)) {
                throw new InvalidTokenException($tokens, $it, $symbol);
            }
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
        require_once $fileName;
    }
