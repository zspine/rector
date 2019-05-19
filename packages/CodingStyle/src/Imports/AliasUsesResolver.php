<?php declare(strict_types=1);

namespace Rector\CodingStyle\Imports;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\UseUse;

final class AliasUsesResolver
{
    /**
     * @var UseImportsTraverser
     */
    private $useImportsTraverser;

    /**
     * @var string[]
     */
    private $aliasedUses = [];

    public function __construct(UseImportsTraverser $useImportsTraverser)
    {
        $this->useImportsTraverser = $useImportsTraverser;
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
        $this->aliasedUses = [];

        $this->useImportsTraverser->traverserStmts($node->stmts, function (UseUse $useUse, string $name): void {
            if ($useUse->alias === null) {
                return;
            }

            $this->aliasedUses[] = $name;
        });

        return $this->aliasedUses;
    }
}
