<?php declare(strict_types=1);

namespace Rector\CodingStyle\Application;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;

final class UseImportsAdder
{
    /**
     * @param Node[] $stmts
     * @param string[] $useImports
     * @param string[] $functionUseImports
     */
    public function addImportsToStmts(array $stmts, array $useImports, array $functionUseImports): void
    {
        // @todo for namespace-less
    }

    /**
     * @param string[] $useImports
     * @param string[] $functionUseImports
     */
    public function addImportsToNamespace(Namespace_ $namespace, array $useImports, array $functionUseImports): void
    {
        if ($useImports === [] && $functionUseImports === []) {
            return;
        }

        $namespaceName = $this->getNamespaceName($namespace);

        $newUses = [];
        $useImports = array_unique($useImports);

        foreach ($useImports as $useImport) {
            if ($this->isCurrentNamespace($namespaceName, $useImport)) {
                continue;
            }

            // already imported in previous cycle
            $useUse = new UseUse(new Name($useImport));
            $newUses[] = new Use_([$useUse]);

//            $this->importsInClassCollection->addImport($useImport);
        }

        foreach ($functionUseImports as $functionUseImport) {
            if ($this->isCurrentNamespace($namespaceName, $functionUseImport)) {
                continue;
            }

            // already imported in previous cycle
            $useUse = new UseUse(new Name($functionUseImport), null, Use_::TYPE_FUNCTION);
            $newUses[] = new Use_([$useUse]);

//            $this->importsInClassCollection->addImport($functionUseImport);
        }

        $namespace->stmts = array_merge($newUses, $namespace->stmts);
    }

    private function getNamespaceName(Namespace_ $namespace): ?string
    {
        if ($namespace->name === null) {
            return null;
        }

        return $namespace->name->toString();
    }

    private function isCurrentNamespace(?string $namespaceName, string $useImports): bool
    {
        if ($namespaceName === null) {
            return false;
        }

        $afterCurrentNamespace = Strings::after($useImports, $namespaceName . '\\');
        if (! $afterCurrentNamespace) {
            return false;
        }

        return ! Strings::contains($afterCurrentNamespace, '\\');
    }
}
