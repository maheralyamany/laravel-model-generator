<?php

namespace MaherAlyamany\CodeGenerator\Model;

use MaherAlyamany\CodeGenerator\RenderableModel;

/**
 * Class BaseProperty
 * @package MaherAlyamany\CodeGenerator\Model
 */
abstract class BasePropertyModel extends RenderableModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
