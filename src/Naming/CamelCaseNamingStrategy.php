<?php

declare(strict_types=1);

namespace Zp\Supple\Naming;

use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use Zp\Supple\NamingStrategyInterface;

final class CamelCaseNamingStrategy implements NamingStrategyInterface
{
    /** @var string */
    private $separator;

    /** @var bool */
    private $lowerCase;

    public function __construct(string $separator = '_', bool $lowerCase = true)
    {
        $this->separator = $separator;
        $this->lowerCase = $lowerCase;
    }

    public function translate(string $name): string
    {
        $name = (string)preg_replace('/[A-Z]+/', $this->separator . '\\0', $name);
        return $this->lowerCase ? strtolower($name) : ucfirst($name);
    }

    public function getSerializerNameStrategy(): SerializedNameAnnotationStrategy
    {
        return new SerializedNameAnnotationStrategy(
            new \JMS\Serializer\Naming\CamelCaseNamingStrategy($this->separator, $this->lowerCase)
        );
    }
}
