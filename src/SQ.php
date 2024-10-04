<?php

namespace Saidqb\LaravelSupport;

class SQ
{
    use Concerns\HasFile;
    use Concerns\HasResponse;
    use Concerns\HasQuery;
    use Concerns\HasHelper;

    /**
     * Make instance of class
     *
     * @param string $class
     * @return object
     * Avilable: QueryFilter
     */
    static function make($class = null)
    {
        $make = "Saidqb\\LaravelSupport\\Make\\$class";
        return new $make;
    }
}
