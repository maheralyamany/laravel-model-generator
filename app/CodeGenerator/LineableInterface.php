<?php

namespace App\CodeGenerator;

/**
 * Interface LineableInterface
 * @package App\CodeGenerator
 */
interface LineableInterface
{
    /**
     * @return string|string[]
     */
    public function toLines();
}
