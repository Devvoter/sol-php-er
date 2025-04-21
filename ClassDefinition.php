<?php

/**
 * IPP - PHP Project Student
 *
 * @file ClassDefinition.php
 * @author Denys Pylypenko (xpylypd00)
 *
 */

namespace IPP\Student;

use DOMElement;

class ClassDefinition
{
    private string $name;

    private string $parentName;
    private ?ClassDefinition $parentDef = null;

    /** @var MethodDefinition[] */
    private array $methods = [];

    public function __construct(DOMElement $elem)
    {
        $this->name = $elem->getAttribute('name');
        $this->parentName = $elem->getAttribute('parent');
        foreach ($elem->getElementsByTagName('method') as $m) {
            $this->methods[] = new MethodDefinition($m);
        }
    }

    public function getParentDef(): ?self
    {
        return $this->parentDef;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentName(): string
    {
        return $this->parentName;
    }

    public function setParentDef(ClassDefinition $p): void
    {
        $this->parentDef = $p;
    }

    public function hasAncestor(string $anc): bool
    {
        // if this is the class itself
        if ($this->name === $anc) {
            return true;
        }
        // if the immediate parent is the sought class
        if ($this->parentName === $anc) {
            return true;
        }
        // otherwise, follow the chain if parentDef is assigned
        return $this->parentDef !== null && $this->parentDef->hasAncestor($anc);
    }

    public function getMethod(string $selector): ?MethodDefinition
    {
        // 1) own methods
        foreach ($this->methods as $m) {
            if ($m->getSelector() === $selector) {
                return $m;
            }
        }
        // 2) parent methods
        return $this->parentDef ? $this->parentDef->getMethod($selector) : null;
    }
}
