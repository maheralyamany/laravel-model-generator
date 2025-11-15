<?php

namespace MaherAlyamany\ModelGenerator\CodeGenerator\Model;

use MaherAlyamany\ModelGenerator\CodeGenerator\RenderableModel;

/**
 * Class BaseProperty
 * @package MaherAlyamany\ModelGenerator\CodeGenerator\Model
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
