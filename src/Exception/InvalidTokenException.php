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

        public function __construct(array $tokens, int $it, $type, Throwable $previous = NULL)
        {
            $this->tokens = $tokens;
            $this->it;
            $this->type = $type;

            parent::__construct(sprintf("Unknown token: %s expected: %s", token_name($this->tokens[$it][0]),
                                        token_name($type)), 0, $previous);
        }
    }
