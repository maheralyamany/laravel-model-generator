<?php

namespace ModelGenerator\CodeGenerator\Model\Traits;

/**
 * Trait AccessModifierTrait
 * @package ModelGenerator\CodeGenerator\Model\Traits
 */
trait AccessModifierTrait
{
    /**
     * @var string
     */
    protected $access;

    /**
     * @return string
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * @param string $access
     *
     * @return self
     */
    public function setAccess($access): self
    {
        if (!in_array($access, ['private', 'protected', 'public'])) {
            $access = 'public';
        }

        $this->access = $access;

        return $this;
    }
}
