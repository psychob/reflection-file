<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile\Exception;

    use Throwable;

    class FunctionNotFoundException extends ReflectionFileException
    {
        /** @var string */
        protected $function;

        /**
         * FunctionNotFoundException constructor.
         *
         * @param string         $function
         * @param Throwable|null $previous
         */
        public function __construct(string $function, ?Throwable $previous = NULL)
        {
            $this->function = $function;

            parent::__construct("Can not load function: {$function}", 0, $previous);
        }

        /**
         * @return string
         */
        public function getFunction(): string
        {
            return $this->function;
        }
    }
