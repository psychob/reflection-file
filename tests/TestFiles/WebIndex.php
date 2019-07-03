<?php
    //
    // ja
    // (c) 2019 RGB Lighthouse <https://rgblighthouse.pl>
    // (c) 2019 Andrzej Budzanowski <kontakt@andrzej.budzanowski.pl>
    //

    use PsychoB\PPP\Application\Kernel;

    require_once __DIR__ . '/../vendor/autoload.php';

    return Kernel::boot(function (ApplicationInterface $app) {
        return $app->run();
    }, [WebDriver::class], realpath(__DIR__.'/..'));
