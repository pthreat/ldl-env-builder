<?php

declare(strict_types=1);

namespace LDL\Env\Compiler\Line\Interfaces;

use Symfony\Component\String\UnicodeString;

interface EnvEmptyLineCompilerInterface extends EnvLineCompilerInterface
{
    public function getType() : string;
    public function getValue() : UnicodeString;
}