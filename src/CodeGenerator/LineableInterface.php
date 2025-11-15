<?php

namespace MaherAlyamany\ModelGenerator\CodeGenerator;

/**
 * Interface LineableInterface
 * @package MaherAlyamany\ModelGenerator\CodeGenerator
 */
interface LineableInterface
{
    /**
     * @return string|string[]
     */
    public function toLines();
}
