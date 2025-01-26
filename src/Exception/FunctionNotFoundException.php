<?php
    //
    //  psychob/reflection-file
    //  (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile\Exception;

    use Throwable;

    class FunctionNotFoundException extends ReflectionFileException
    {
        /**
         * FunctionNotFoundException constructor.
         *
         * @param string $function
         * @param Throwable|null $previous
         */
        public function __construct(private readonly string $function, ?Throwable $previous = NULL)
        {
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
