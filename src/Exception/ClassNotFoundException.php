<?php
    //
    //  psychob/reflection-file
    //  (c) 2019 - 2025 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile\Exception;

    use Throwable;

    class ClassNotFoundException extends ReflectionFileException
    {
        /**
         * ClassNotFoundException constructor.
         *
         * @param string $class
         * @param Throwable|null $previous
         */
        public function __construct(private readonly string $class, ?Throwable $previous = NULL)
        {
            parent::__construct(sprintf("Can't load class: {$class}"), 0, $previous);
        }

        /**
         * @return string
         */
        public function getClass(): string
        {
            return $this->class;
        }
    }
