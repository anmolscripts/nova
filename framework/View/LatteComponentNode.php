<?php

declare(strict_types=1);

namespace Nova\View;

use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * Compiles Nova component tags in Latte templates.
 */
final class LatteComponentNode extends StatementNode
{
    private ArrayNode $arguments;

    public static function create(Tag $tag): self
    {
        $tag->expectArguments();

        $node = new self();
        $node->arguments = $tag->parser->parseArguments();

        return $node;
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'echo $this->global->componentEngine->renderFromArguments(%node) %line;',
            $this->arguments,
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->arguments;
    }
}
