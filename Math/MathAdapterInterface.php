<?php

declare (strict_types = 1);

namespace Tdn\PhpTypes\Math;

/**
 * Interface MathLibraryAdapterInterface.
 */
interface MathAdapterInterface extends MathInterface
{
    /**
     * @param number $number
     *
     * @return int
     */
    public function getPrecision($number) : int;
}
