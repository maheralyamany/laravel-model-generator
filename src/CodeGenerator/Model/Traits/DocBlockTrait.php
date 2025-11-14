<?php

namespace MaherAlyamany\CodeGenerator\Model\Traits;

use MaherAlyamany\CodeGenerator\Model\DocBlockModel;

/**
 * Trait DocBlockTrait
 * @package MaherAlyamany\CodeGenerator\Model\Traits
 */
trait DocBlockTrait
{
    /**
     * @var DocBlockModel
     */
    protected $docBlock;

    /**
     * @return DocBlockModel
     */
    public function getDocBlock()
    {
        return $this->docBlock;
    }

    /**
     * @param DocBlockModel $docBlock
     *
     * @return $this
     */
    public function setDocBlock($docBlock)
    {
        $this->docBlock = $docBlock;

        return $this;
    }
}
