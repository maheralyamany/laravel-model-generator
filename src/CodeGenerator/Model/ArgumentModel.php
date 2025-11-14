<?php

namespace MaherAlyamany\CodeGenerator\Model;

use MaherAlyamany\CodeGenerator\RenderableModel;

/**
 * Class Argument
 * @package MaherAlyamany\CodeGenerator\Model
 */
class ArgumentModel extends RenderableModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $default;

    /**
     * ArgumentModel constructor.
     * @param string $name
     * @param string|null $type
     * @param mixed|null $default
     */
    public function __construct($name, $type = null, $default = null)
    {
        $this->setName($name)
            ->setType($type)
            ->setDefault($default);
    }

    /**
     * {@inheritDoc}
     */
    public function toLines()
    {
        $args = '';
        if ($this->type !== null) {
            $args = $this->type . ' $' . $this->name;
        } else {
            $args = '$' . $this->name;
        }
        if ($this->default !== null && !empty($this->default)) {
            $args .= " =" . $this->default;
        }
        return $args;
    }

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

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }
}
