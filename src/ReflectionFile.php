<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\Exception\FileNotFoundException;
    use PsychoB\ReflectionFile\Exception\InvalidTokenException;

    class ReflectionFile
    {
        /** @var string */
        protected $fileName;

        protected $namespaces = [];
        protected $classes = [];
        protected $functions = [];

        protected $cachedClasses = [];
        protected $cachedFunctions = [];

        protected $parsed = false;
        protected $loaded = false;

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

        public function getNamespaces(): array
        {
            if (!$this->parsed) {
                $this->parse();
            }

            return $this->namespaces;
        }

        /**
         * @return \ReflectionClass[]
         */
        public function getClasses(): array
        {
            if (!$this->parsed) {
                $this->parse();
            }

            if (!empty($this->classes) && empty($this->cachedClasses)) {
                if (!$this->loaded) {
                    $this->load();
                }

                foreach ($this->classes as $class) {
                    $this->cachedClasses[] = new \ReflectionClass($class);
                }
            }

            return $this->cachedClasses;
        }

        /**
         * @return \ReflectionClass[]
         */
        public function getInterfaces(): array
        {
            $ret = [];

            foreach ($this->getClasses() as $class) {
                if ($class->isInterface()) {
                    $ret[] = $class;
                }
            }

            return $ret;
        }

        /**
         * @return \ReflectionClass[]
         */
        public function getTraits(): array
        {
            $ret = [];

            foreach ($this->getClasses() as $class) {
                if ($class->isTrait()) {
                    $ret[] = $class;
                }
            }

            return $ret;
        }

        /**
         * @return \ReflectionClass[]
         */
        public function getAbstractClasses(): array
        {
            $ret = [];

            foreach ($this->getClasses() as $class) {
                if ($class->isAbstract() && !$class->isTrait() && !$class->isInterface()) {
                    $ret[] = $class;
                }
            }

            return $ret;
        }

        /**
         * @return \ReflectionFunction[]
         */
        public function getFunctions(): array
        {
            if (!$this->parsed) {
                $this->parse();
            }

            if (!empty($this->functions) && empty($this->cachedFunctions)) {
                if (!$this->loaded) {
                    $this->load();
                }

                foreach ($this->functions as $function) {
                    $this->cachedFunctions[] = new \ReflectionFunction($function);
                }
            }

            return $this->cachedFunctions;
        }

        private function parse()
        {
            $this->functions = [];
            $this->classes = [];
            $this->namespaces = [];

            $this->cachedFunctions = [];
            $this->cachedClasses = [];

            $loaded = file_get_contents($this->fileName);

            $tokens = token_get_all($loaded);
            $tokenCount = count($tokens);

            for ($it = 0; $it < $tokenCount; ++$it) {
                // we want to start parsing with T_PHP_OPEN
                $currentToken = $tokens[$it];

                if (is_array($currentToken)) {
                    if ($currentToken[0] === T_OPEN_TAG) {
                        $this->parse_phpContent($tokens, $it, $tokenCount);
                    }
                }
            }

            $this->parsed = true;
            $this->loaded = false;
        }

        public function load(): void
        {
            __anonymous_load_file($this->fileName);
            $this->loaded = true;
        }

        private function parse_phpContent(array $tokens, int &$it, int $tokenCount, string $namespace = '',
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
                            $this->parse_skip_use($tokens, $it, $tokenCount, $namespace);
                            break;

                        case T_CLOSE_TAG:
                            if (!$subExpression)
                                return;
                            break;

                        case T_FUNCTION:
                            $this->parse_function($tokens, $it, $tokenCount, $namespace);
                            break;

                        case T_NAMESPACE:
                            $namespace = $this->parse_namespace($tokens, $it, $tokenCount);
                            break;

                        case T_ABSTRACT:
                            $this->parse_abstract($tokens, $it, $tokenCount, $namespace);
                            break;
                        case T_FINAL:
                            $this->parse_final($tokens, $it, $tokenCount, $namespace);
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
                            dump(token_name($currentToken[0]) . ' -> ' . $currentToken[1]);
                    }
                } else {
                    switch ($currentToken) {
                        case '}':
                            if ($subExpression) {
                                return;
                            }

                        default:
                            dump($currentToken);
                    }
                }
            }
        }

        private function parse_function(array $tokens, int &$it, int $tokenCount, string $namespace,
                                        bool $skip = false): void
        {
            $this->assertToken($tokens, $it, T_FUNCTION);
            $it += 2; // we skip over function and whitespace

            if (!$skip) {
                if (empty($namespace)) {
                    $this->functions[] = $tokens[$it][1];
                } else {
                    $this->functions[] = $namespace . '\\' . $tokens[$it][1];
                }
            }

            // skip over prologue of function
            for (; $it < $tokenCount; ++$it) {
                if (!is_array($tokens[$it]) && $tokens[$it] === '{')
                    break;
            }

            // now we balance brackets
            $this->parse_balanceBrackets($tokens, $it, $tokenCount);
        }

        private function parse_balanceBrackets(array $tokens, int &$it, int $tokenCount)
        {
            $this->assertSymbol($tokens, $it, '{');
            $it++;

            for (; $it < $tokenCount; ++$it) {
                if (!is_array($tokens[$it])) {
                    switch ($tokens[$it]) {
                        case '{':
                            $this->parse_balanceBrackets($tokens, $it, $tokenCount);
                            break;

                        case '}':
                            $it++;
                            return;
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
                    $this->namespaces[] = $ns;
                    return $ns;
                } else if (!is_array($tokens[$it]) && $tokens[$it] === '{') {
                    $this->namespaces[] = $ns;
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
                            dump(token_name($tokens[$it][0]));

                            throw new InvalidTokenException($tokens, $it);
                    }
                }
            }

            return '';
        }

        private function parse_final(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_FINAL);
            $it += 2;

            $this->parse_class($tokens, $it, $tokenCount, $namespace);
        }

        private function parse_abstract(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_ABSTRACT);
            $it += 2;

            $this->parse_class($tokens, $it, $tokenCount, $namespace);
        }

        private function parse_trait(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_TRAIT);
            $it += 2;

            $this->parse_class_viscera($tokens, $it, $tokenCount, $namespace);
        }

        private function parse_interface(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_INTERFACE);
            $it += 2;

            $this->parse_class_viscera($tokens, $it, $tokenCount, $namespace);
        }

        private function parse_class(array $tokens, int & $it, int $tokenCount, string $namespace)
        {
            $this->assertToken($tokens, $it, T_CLASS);
            $it += 2;

            $this->parse_class_viscera($tokens, $it, $tokenCount, $namespace);
        }

        /**
         * @param array  $tokens
         * @param int    $it
         * @param int    $tokenCount
         * @param string $namespace
         */
        private function parse_class_viscera(array $tokens, int &$it, int $tokenCount, string $namespace)
        {
            if (empty($namespace)) {
                $name = $tokens[$it][1];
            } else {
                $name = $namespace . '\\' . $tokens[$it][1];
            }

            $this->classes[] = $name;

            // skip over prologue of function
            for (; $it < $tokenCount; ++$it) {
                if (!is_array($tokens[$it]) && $tokens[$it] === '{')
                    break;
            }

            // now we balance brackets
            $this->parse_balanceBrackets($tokens, $it, $tokenCount);
        }

        private function parse_skip_use(array $tokens, int &$it, int $tokenCount)
        {
            $this->assertToken($tokens, $it, T_USE);

            for (; $it < $tokenCount; ++$it) {
                if (!is_array($tokens[$it]) && $tokens[$it] === ';')
                    break;
            }
        }

        private function assertToken(array $tokens, int $it, int $type): void {
            if (!(is_array($tokens[$it]) && $tokens[$it][0] === $type)) {
                throw new InvalidTokenException($tokens, $it, $type);
            }
        }

        private function assertSymbol(array $tokens, int $it, string $symbol): void {
            if (!(!is_array($tokens[$it]) && $tokens[$it] === $symbol)) {
                throw new InvalidTokenException($tokens, $it, $symbol);
            }
        }
    }

    function __anonymous_load_file(string $fileName)
    {
        require_once $fileName;
    }
