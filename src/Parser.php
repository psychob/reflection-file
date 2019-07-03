<?php
    //
    // psychob/reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile;

    use PsychoB\ReflectionFile\Exception\InvalidTokenException;

    class Parser
    {
        /** @var string */
        protected $fileName;

        protected $class = [];
        protected $object = [];
        protected $interface = [];
        protected $namespace = [];
        protected $abstractClass = [];
        protected $functions = [];
        protected $traits = [];

        public function __construct(string $fileName)
        {
            $this->fileName = $fileName;
        }

        public function parse()
        {
            $content = file_get_contents($this->fileName);
            $tokens = token_get_all($content, TOKEN_PARSE);

            $this->parseTokens($tokens);

            return [$this->class, $this->object, $this->interface, $this->namespace, $this->abstractClass,
                    $this->functions, $this->traits];
        }

        private function parseTokens(array $tokens): void
        {
            $tokenCount = count($tokens);
            for ($it = 0; $it < $tokenCount; ++$it) {
                $this->skipToToken($tokens, $it, $tokenCount, T_OPEN_TAG);

                if ($it < $tokenCount) {
                    $this->phpContext($tokens, $it, $tokenCount);
                }
            }
        }

        private function skipToToken(array $tokens, int &$it, int $tokenCount, $skipUpTo): void
        {
            if (!is_array($skipUpTo)) {
                $skipUpTo = [$skipUpTo];
            }

            for (; $it < $tokenCount; ++$it) {
                if (is_array($tokens[$it]) && in_array($tokens[$it][0], $skipUpTo)) {
                    break;
                }
            }
        }

        private function skipToSymbol(array $tokens, int &$it, int $tokenCount, $skipUpTo): void
        {
            if (!is_array($skipUpTo)) {
                $skipUpTo = [$skipUpTo];
            }

            for (; $it < $tokenCount; ++$it) {
                if (!is_array($tokens[$it]) && in_array($tokens[$it], $skipUpTo)) {
                    break;
                }
            }
        }

        private function assertToken(array $tokens, int $it, int $token): void
        {
            if (!(is_array($tokens[$it]) && $tokens[$it][0] === $token)) {
                throw new InvalidTokenException($tokens, $it, $token);
            }
        }

        private function assertSymbol(array $tokens, int $it, string $token): void
        {
            if (!(!is_array($tokens[$it]) && $tokens[$it] === $token)) {
                throw new InvalidTokenException($tokens, $it, $token);
            }
        }

        private function phpContext(array $tokens, int &$it, int $tokenCount, string $currentNs = '', bool $subExpression = false): void
        {
            if ($subExpression) {
                $this->assertSymbol($tokens, $it, '{');
            } else {
                $this->assertToken($tokens, $it, T_OPEN_TAG);
            }
            $it++;

            for (; $it < $tokenCount; ++$it) {
                if (is_array($tokens[$it])) {
                    switch ($tokens[$it][0]) {
                        case T_WHITESPACE:
                        case T_COMMENT:
                            continue;

                        case T_CLOSE_TAG:
                            if (!$subExpression) {
                                return;
                            }
                            break;

                        case T_FUNCTION:
                            $this->fetchFunction($tokens, $it, $tokenCount, $currentNs);
                            break;

                        case T_USE:
                            $this->skipStatement($tokens, $it, $tokenCount);
                            break;

                        case T_NAMESPACE:
                            $currentNs = $this->fetchNamespace($tokens, $it, $tokenCount);
                            break;

                        case T_ABSTRACT:
                            $this->fetchAbstractClass($tokens, $it, $tokenCount, $currentNs);
                            break;

                        case T_FINAL:
                            $this->fetchFinalClass($tokens, $it, $tokenCount, $currentNs);
                            break;

                        case T_CLASS:
                            $this->fetchClass($tokens, $it, $tokenCount, $currentNs);
                            break;

                        case T_TRAIT:
                            $this->fetchTrait($tokens, $it, $tokenCount, $currentNs);
                            break;

                        case T_INTERFACE:
                            $this->fetchInterface($tokens, $it, $tokenCount, $currentNs);
                            break;

                        default:
                            dump($tokens[$it], token_name($tokens[$it][0]));
                            continue;
                    }
                } else {
                    if ($subExpression && $tokens[$it] === '}') {
                        return;
                    }

                    continue;
                }
            }
        }

        private function fetchFunction(array $tokens, int &$it, int $tokenCount, string $ns): void
        {
            $this->assertToken($tokens, $it, T_FUNCTION);
            $it++;

            $name = '';

            for (; $it < $tokenCount; ++$it) {
                if (is_array($tokens[$it])) {
                    switch ($tokens[$it][0]) {
                        case T_STRING:
                            $name = $tokens[$it][1];
                            break;
                    }
                } else if (!is_array($tokens[$it]) && in_array($tokens[$it], ['{', '('])) {
                    break;
                }
            }

            if (!empty($name)) {
                if ($ns) {
                    $this->functions[] = $ns . '\\' . $name;
                } else {
                    $this->functions[] = $name;
                }
            }

            $this->skipToSymbol($tokens, $it, $tokenCount, '{');
            $this->balanceBrackets($tokens, $it, $tokenCount);
        }

        private function balanceBrackets(array $tokens, int &$it, int $tokenCount): void
        {
            $this->assertSymbol($tokens, $it, '{');
            $it++;

            for (; $it < $tokenCount; ++$it) {
                $this->skipToSymbol($tokens, $it, $tokenCount, ['{', '}']);

                if ($tokens[$it] === '}') {
                    break;
                } else {
                    $this->balanceBrackets($tokens, $it, $tokenCount);
                }
            }
        }

        private function skipStatement(array $tokens, int &$it, int $tokenCount): void
        {
            $it++;
            $this->skipToSymbol($tokens, $it, $tokenCount, ';');
        }

        private function fetchNamespace(array $tokens, int &$it, int $tokenCount): string
        {
            $this->assertToken($tokens, $it, T_NAMESPACE);
            $it += 2;

            $ns = '';

            for (; $it < $tokenCount; ++$it) {
                if (!is_array($tokens[$it]) && $tokens[$it] === ';') {
                    $this->namespace[] = $ns;
                    return $ns;
                } else if (!is_array($tokens[$it]) && $tokens[$it] === '{') {
                    $this->namespace[] = $ns;
                    $this->phpContext($tokens, $it, $tokenCount, $ns, true);
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

        private function fetchClass(array $tokens, int &$it, int $tokenCount, string $ns): void
        {
            $this->assertToken($tokens, $it, T_CLASS);
            $it += 2;

            $this->class[] = $this->classViscera($tokens, $it, $tokenCount, $ns);
        }

        private function fetchTrait(array $tokens, int &$it, int $tokenCount, string $ns): void
        {
            $this->assertToken($tokens, $it, T_TRAIT);
            $it += 2;

            $this->traits[] = $this->classViscera($tokens, $it, $tokenCount, $ns);
        }

        private function fetchInterface(array $tokens, int &$it, int $tokenCount, string $ns): void
        {
            $this->assertToken($tokens, $it, T_INTERFACE);
            $it += 2;

            $this->interface[] = $this->classViscera($tokens, $it, $tokenCount, $ns);
        }

        private function fetchFinalClass(array $tokens, int &$it, int $tokenCount, string $ns): void
        {
            $this->assertToken($tokens, $it, T_FINAL);
            $it += 4;

            $this->class[] = $this->classViscera($tokens, $it, $tokenCount, $ns);
        }

        private function fetchAbstractClass(array $tokens, int &$it, int $tokenCount, string $ns): void
        {
            $this->assertToken($tokens, $it, T_ABSTRACT);
            $it += 4;

            $this->abstractClass[] = $this->classViscera($tokens, $it, $tokenCount, $ns);
        }

        private function classViscera(array $tokens, int &$it, int $tokenCount, string $namespace): string
        {
            if (empty($namespace)) {
                $name = $tokens[$it][1];
            } else {
                $name = $namespace . '\\' . $tokens[$it][1];
            }

            $this->object[] = $name;

            // skip over prologue of function
            $this->skipToSymbol($tokens, $it, $tokenCount, '{');

            // now we balance brackets
            $this->balanceBrackets($tokens, $it, $tokenCount);

            return $name;
        }
    }
