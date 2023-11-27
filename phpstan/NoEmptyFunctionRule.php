<?php

namespace Unleash\Client\PhpstanRules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ResourceType;

final readonly class NoEmptyFunctionRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\Empty_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\Empty_);

        $hint = '';
        $expression = $node->expr;
        if ($expression instanceof Node\Expr\Variable) {
            if (is_string($expression->name)) {
                $type = $scope->getVariableType($expression->name);
                if ($type->isArray()->yes()) {
                    $hint = "You're probably looking for `!count(\${$expression->name})`.";
                } elseif ($type->isBoolean()->yes()) {
                    $hint = "You're probably looking for `!\${$expression->name}`.";
                } elseif ($type->isFloat()->yes()) {
                    $hint = "You're probably looking for `\${$expression->name} === 0.0 || \${$expression->name} === -0.0` or simply `!\${$expression->name}`.";
                } elseif ($type->isInteger()->yes()) {
                    $hint = "You're probably looking for `\${$expression->name} === 0` or simply `!\${$expression->name}`.";
                } elseif ($type->isString()->yes()) {
                    $hint = "You're probably looking for `\${$expression->name} === '' || \${$expression->name} === '0'` or simply `!\${$expression->name}`.";
                } elseif ($type->isNull()->yes()) {
                    $hint = "You're probably looking for '\${$expression->name} === null` or simply `!\${$expression->name}`.";
                } elseif ($type->isObject()->yes()) {
                    $hint = "Checking for empty on an object always returns false (except for some internal objects, but you shouldn't rely on this behavior).";
                } elseif ($type instanceof ResourceType) {
                    $hint = 'Checking for empty on a resource always returns false.';
                }
            }
        }

        $message = 'Never use empty(), always check specifically for what you want.';
        if ($hint) {
            $message .= " {$hint}";
        }

        return [
            RuleErrorBuilder::message($message)->build(),
        ];
    }
}
