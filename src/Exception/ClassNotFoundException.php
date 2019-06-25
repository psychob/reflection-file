<?php
    //
    // reflection-file
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    namespace PsychoB\ReflectionFile\Exception;

    use Throwable;

    class ClassNotFoundException extends ReflectionFileException
    {
        protected $class;

        /**
         * ClassNotFoundException constructor.
         *
         * @param string          $class
         * @param Throwable|null $previous
         */
        public function __construct(string $class, ?Throwable $previous = NULL)
        {
            $this->class = $class;

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
