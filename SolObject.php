<?php

/**
 * IPP - PHP Project Student
 *
 * @file SolObject.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student;

use IPP\Student\ClassDefinition;
use IPP\Student\Exception\InterpretDnuException;
use IPP\Student\Exception\InterpretTypeException;

/*
In SOL25 everything is an object.
It seems easy to create a base class that will be the parent of all other classes in SOL25.
*/

class SolObject
{
    protected ?ClassDefinition $classDef;

    /** @var SolObject[] */
    protected array $attributes = [];

    public function __construct(?ClassDefinition $classDef = null)
    {
        $this->classDef = $classDef;
    }

    /**
     * @param SolObject[] $args
     */
    public function callMethod(string $selector, array $args, Interpreter $intr): SolObject
    {
        // --- 0) Built-in Object methods
        switch ($selector) {
            case 'identicalTo:':
                // argument is another object
                return ($this === $args[0])
                    ? SolTrue::getInstance()
                    : SolFalse::getInstance();

            case 'equalTo:':
                // if the object has no user-defined attributes – compare simply by identicalTo:
                if (empty($this->attributes) && empty($args[0]->attributes)) {
                    return ($this === $args[0])
                        ? SolTrue::getInstance()
                        : SolFalse::getInstance();
                }
                // otherwise – compare all attributes by key and value equality
                $other = $args[0];
                if (count($this->attributes) !== count($other->attributes)) {
                    return SolFalse::getInstance();
                }
                foreach ($this->attributes as $k => $v) {
                    if (!array_key_exists($k, $other->attributes)) {
                        return SolFalse::getInstance();
                    }
                    $res = $v->callMethod('equalTo:', [ $other->attributes[$k] ], $intr);
                    if ($res !== SolTrue::getInstance()) {
                        return SolFalse::getInstance();
                    }
                }
                return SolTrue::getInstance();

            case 'asString':
                // for the base Object – an empty string
                return new SolString('');
            case 'isNumber':
            case 'isString':
            case 'isBlock':
            case 'isNil':
                return SolFalse::getInstance();
        }

        // 1) AST-methods
        if ($this->classDef) {
            $methodDef = $this->classDef->getMethod($selector);
            if ($methodDef) {
                return $methodDef->execute($this, $args, $intr);
            }
        }
        // 2) setter attribute
        if (substr($selector, -1) === ':' && count($args) === 1) {
            $attrName = rtrim($selector, ':');
            $this->attributes[$attrName] = $args[0];
            return $this;
        }
        // 3) getter attribute
        if (strpos($selector, ':') === false && count($args) === 0) {
            if (array_key_exists($selector, $this->attributes)) {
                return $this->attributes[$selector];
            }
        }
        // 4) nothing matched
        throw new InterpretDnuException($selector);
    }

    public function setAttr(string $name, SolObject $value): SolObject
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    //getAttr — get the attribute if it exists.
    // If it does not exist, throw error 51.
    public function getAttr(string $name): SolObject
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new InterpretTypeException("Attribute '{$name}' not found");
        }
        return $this->attributes[$name];
    }

    public function getClassDef(): ?ClassDefinition
    {
        return $this->classDef;
    }

    public function hasAttr(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }
}
