<?php declare(strict_types=1);

namespace Rector\CodingStyle\Imports;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\UseUse;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Node\Resolver\NameResolver;

final class UsedImportsResolver
{
    /**
     * @var BetterNodeFinder
     */
    private $betterNodeFinder;

    /**
     * @var NameResolver
     */
    private $nameResolver;

    /**
     * @var string[]
     */
    private $usedImports = [];

    /**
     * @var UseImportsTraverser
     */
    private $useImportsTraverser;

    public function __construct(
        BetterNodeFinder $betterNodeFinder,
        NameResolver $nameResolver,
        UseImportsTraverser $useImportsTraverser
    ) {
        $this->betterNodeFinder = $betterNodeFinder;
        $this->nameResolver = $nameResolver;
        $this->useImportsTraverser = $useImportsTraverser;
    }

    /**
     * @return string[]
     */
    public function resolveForNode(Node $node): array
    {
        $namespace = $node->getAttribute(AttributeKey::NAMESPACE_NODE);
        if ($namespace instanceof Namespace_) {
            return $this->resolveForNamespace($namespace);
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function resolveForNamespace(Namespace_ $node): array
    {
        $this->usedImports = [];

        // @todo should be for all classes in the file - test
        /** @var Class_|null $class */
        $class = $this->betterNodeFinder->findFirstInstanceOf($node->stmts, Class_::class);

        // add class itself
        if ($class !== null) {
            $className = $this->nameResolver->resolve($class);
            if ($className !== null) {
                $this->usedImports[] = $className;
            }
        }

        $this->useImportsTraverser->traverserStmts($node->stmts, function (UseUse $useUse, string $name): void {
            $this->usedImports[] = $name;
        });

        return $this->usedImports;
    }
}
