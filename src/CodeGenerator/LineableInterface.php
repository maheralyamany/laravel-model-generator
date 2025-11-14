<?php

namespace MaherAlyamany\CodeGenerator;

/**
 * Interface LineableInterface
 * @package MaherAlyamany\CodeGenerator
 */
interface LineableInterface
{
    /**
     * @return string|string[]
     */
    public function toLines();
}
