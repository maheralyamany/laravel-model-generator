<?php

namespace ModelGenerator\CodeGenerator\Model\Traits;

use ModelGenerator\CodeGenerator\Model\DocBlockModel;

/**
 * Trait DocBlockTrait
 * @package ModelGenerator\CodeGenerator\Model\Traits
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
