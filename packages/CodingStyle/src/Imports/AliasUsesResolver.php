<?php declare(strict_types=1);

namespace Rector\CodingStyle\Imports;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use Rector\Exception\ShouldNotHappenException;
use Rector\PhpParser\Node\Resolver\NameResolver;
use Rector\PhpParser\NodeTraverser\CallableNodeTraverser;

final class AliasUsesResolver
{
    /**
     * @var NameResolver
     */
    private $nameResolver;

    /**
     * @var CallableNodeTraverser
     */
    private $callableNodeTraverser;

    public function __construct(NameResolver $nameResolver, CallableNodeTraverser $callableNodeTraverser)
    {
        $this->nameResolver = $nameResolver;
        $this->callableNodeTraverser = $callableNodeTraverser;
    }

    /**
     * @return string[]
     */
    public function resolveForNode(Node $node): array
    {
        if ($node instanceof Namespace_) {
            return $this->resolveForNamespace($node);
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function resolveForNamespace(Namespace_ $node): array
    {
        $aliasedUses = [];

        $this->callableNodeTraverser->traverseNodesWithCallable($node->stmts, function (Node $node) {
            if (! $node instanceof Use_) {
                return null;
            }

            // only import uses
            if ($node->type !== Use_::TYPE_NORMAL) {
                return null;
            }

            foreach ($node->uses as $useUse) {
                $name = $this->nameResolver->resolve($useUse);
                if ($name === null) {
                    throw new ShouldNotHappenException();
                }

                if ($useUse->alias !== null) {
                    // alias workaround
                    $aliasedUses[] = $name;
                }
            }
        });

        return $aliasedUses;
    }
}
