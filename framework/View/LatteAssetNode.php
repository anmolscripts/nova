<?php

declare(strict_types=1);

namespace Nova\View;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * Compiles Nova asset tags in Latte templates.
 */
final class LatteAssetNode extends StatementNode
{
    private ExpressionNode $entry;

    public static function create(Tag $tag): self
    {
        $tag->expectArguments();

        $node = new self();
        $node->entry = $tag->parser->parseUnquotedStringOrExpression();

        return $node;
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'echo $this->global->asset->tags(%node) %line;',
            $this->entry,
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->entry;
    }
}
