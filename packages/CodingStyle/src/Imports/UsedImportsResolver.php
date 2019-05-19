<?php declare(strict_types=1);

namespace Rector\CodingStyle\Imports;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use Rector\Exception\ShouldNotHappenException;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Node\Resolver\NameResolver;
use Rector\PhpParser\NodeTraverser\CallableNodeTraverser;

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
     * @var CallableNodeTraverser
     */
    private $callableNodeTraverser;

    /**
     * @var string[]
     */
    private $usedImports = [];

    public function __construct(
        BetterNodeFinder $betterNodeFinder,
        NameResolver $nameResolver,
        CallableNodeTraverser $callableNodeTraverser
    ) {
        $this->betterNodeFinder = $betterNodeFinder;
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

                $this->usedImports[] = $name;
            }
        });

        return $this->usedImports;
    }
}
