<?php

namespace ModelGenerator\CodeGenerator;

/**
 * Interface LineableInterface
 * @package ModelGenerator\CodeGenerator
 */
interface LineableInterface
{
    /**
     * @return string|string[]
     */
    public function toLines();
}
