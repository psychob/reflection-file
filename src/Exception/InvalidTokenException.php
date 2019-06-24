<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile\Exception;

    use Throwable;

    class InvalidTokenException extends ReflectionFileException
    {
        protected $tokens;
        protected $it;
        protected $type;

        public function __construct(array $tokens, int $it, $type = NULL, Throwable $previous = NULL)
        {
            $this->tokens = $tokens;
            $this->it = $it;
            $this->type = $type;

            $message = $this->calculateMessage();
            $message .= $this->calculateLine();

            parent::__construct($message, 0, $previous);

            $this->calculateMessage($tokens, $it, $type, $previous);
        }

        private function calculateMessage(): string
        {
            if ($this->type === NULL) {
                if (is_array($this->tokens[$this->it])) {
                    return sprintf("Unknown token: %s", token_name($this->tokens[$this->it][0]));
                } else {
                    return sprintf("Unknown token: %s", $this->tokens[$this->it]);
                }
            } else {
                if (!is_array($this->type)) {
                    $type = [];
                } else {
                    $type = $this->type;
                }

                $types = [];
                foreach ($type as $element) {
                    if (is_integer($element)) {
                        $types[] = token_name($element);
                    } else {
                        $types[] = $element;
                    }
                }

                return sprintf("Unknown token: %s, expected: %s", token_name($this->tokens[$this->it][0]),
                               implode(', ', $types));
            }
        }

        public function calculateLine(): string
        {
            $lineExtract = [];

            if (!is_array($this->tokens[$this->it])) {
                for ($it = $this->it; $it > 0; --$it) {
                    if (is_array($this->tokens[$it])) {
                        $lineExtract[] = $this->tokens[$it][2];
                        break;
                    }
                }

                for ($it = $this->it; $it < count($this->tokens); ++$it) {
                    if (is_array($this->tokens[$it])) {
                        $lineExtract[] = $this->tokens[$it][2];
                        break;
                    }
                }
            } else {
                $lineExtract = [$this->tokens[$this->it][2]];
            }

            if (min($lineExtract) === max($lineExtract)) {
                // we need to only extract one line
                $lineExtract = [min($lineExtract)];
            } else {
                $tmp = [];

                for ($it = min($lineExtract); $it <= max($lineExtract); ++$it) {
                    $tmp[] = $it;
                }

                $lineExtract = $tmp;
            }

            $lines = [];
            $lastLine = 0;

            foreach ($this->tokens as $tok) {
                // this is inefficient
                if (is_array($tok)) {
                    if (in_array($tok[2], $lineExtract)) {
                        if (!array_key_exists($tok[2], $lines)) {
                            $lines[$tok[2]] = '';
                        }

                        $lines[$tok[2]] .= $tok[1];
                    }

                    $lastLine = $tok[2];
                } else {
                    if (in_array($lastLine, $lineExtract)) {
                        if (!array_key_exists($lastLine, $lines)) {
                            $lines[$lastLine] = '';
                        }

                        $lines[$lastLine] .= $tok;
                    }
                }
            }

            $ret = PHP_EOL;

            foreach ($lines as $no => $line) {
                $ret .= 'LINE (' . $no . '): ' . trim($line) . PHP_EOL;
            }

            return $ret;
        }
    }
