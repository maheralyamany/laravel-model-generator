<?php

namespace MaherAlyamany\CodeGenerator\Model;

use MaherAlyamany\CodeGenerator\RenderableModel;

/**
 * Class BaseMethodModel
 * @package MaherAlyamany\CodeGenerator\Model
 */
abstract class BaseMethodModel extends RenderableModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var ArgumentModel[]
     */
    protected $arguments = [];

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
     * @return self
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return ArgumentModel[]
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param ArgumentModel $argument
     *
     * @return $this
     */
    public function addArgument(ArgumentModel $argument)
    {
        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * @return string
     */
    protected function renderArguments()
    {
        $result = '';
        if ($this->arguments) {
            $arguments = [];
            foreach ($this->arguments as $argument) {
                $arguments[] = $argument->render();
            }

            $result .= implode(', ', $arguments);
        }

        return $result;
    }
}
