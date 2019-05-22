<?php declare(strict_types=1);

namespace Rector\CodingStyle\Rector\Namespace_;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use Rector\CodingStyle\Application\UseAddingCommander;
use Rector\CodingStyle\Imports\AliasUsesResolver;
use Rector\CodingStyle\Imports\ImportsInClassCollection;
use Rector\CodingStyle\Imports\UsedImportsResolver;
use Rector\CodingStyle\Naming\ClassNaming;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\PhpDoc\NodeAnalyzer\DocBlockManipulator;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

final class ImportFullyQualifiedNamesRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $alreadyUsedShortNames = [];

    /**
     * @var string[]
     */
    private $aliasedUses = [];

    /**
     * @var DocBlockManipulator
     */
    private $docBlockManipulator;

    /**
     * @var ImportsInClassCollection
     */
    private $importsInClassCollection;

    /**
     * @var ClassNaming
     */
    private $classNaming;

    /**
     * @var bool
     */
    private $shouldImportDocBlocks = true;

    /**
     * @var UsedImportsResolver
     */
    private $usedImportsResolver;

    /**
     * @var AliasUsesResolver
     */
    private $aliasUsesResolver;

    /**
     * @var UseAddingCommander
     */
    private $useAddingCommander;

    public function __construct(
        DocBlockManipulator $docBlockManipulator,
        ImportsInClassCollection $importsInClassCollection,
        ClassNaming $classNaming,
        UsedImportsResolver $usedImportsResolver,
        AliasUsesResolver $aliasUsesResolver,
        UseAddingCommander $useAddingCommander,
        bool $shouldImportDocBlocks = true
    ) {
        $this->docBlockManipulator = $docBlockManipulator;
        $this->importsInClassCollection = $importsInClassCollection;
        $this->classNaming = $classNaming;
        $this->usedImportsResolver = $usedImportsResolver;
        $this->shouldImportDocBlocks = $shouldImportDocBlocks;
        $this->useAddingCommander = $useAddingCommander;
        $this->aliasUsesResolver = $aliasUsesResolver;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Import fully qualified names to use statements', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function create()
    {
          return SomeAnother\AnotherClass;
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
use SomeAnother\AnotherClass;

class SomeClass
{
    public function create()
    {
          return AnotherClass;
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Name::class, Node::class];
    }

    /**
     * @param Name $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Name) {
            $this->resetCollectedNames();

            $this->resolveAlreadyImportedUses($node);

            // "new X" or "X::static()"
            $this->resolveAlreadyUsedShortNames($node);

            return $this->importNamesAndCollectNewUseStatements($node);
        }

        // process every doc block node
        if ($this->shouldImportDocBlocks) {
            $useImports = $this->docBlockManipulator->importNames($node);
            foreach ($useImports as $useImport) {
                $this->useAddingCommander->addUseImport($node, $useImport);
            }

            return $node;
        }

        return null;
    }

    private function resolveAlreadyImportedUses(Node $node): void
    {
        $usedImports = $this->usedImportsResolver->resolveForNode($node);

        foreach ($usedImports as $usedImport) {
            $this->importsInClassCollection->addImport($usedImport);
        }

        $this->aliasedUses = $this->aliasUsesResolver->resolveForNode($node);
    }

    /**
     * @return string[]
     */
    private function importNamesAndCollectNewUseStatements(Name $name): ?Name
    {
        $name = $name->getAttribute('originalName');

        if ($name instanceof Name) {
            // already short
            if (! Strings::contains($name->toString(), '\\')) {
                return null;
            }
        } else {
            return null;
        }

        // the short name is already used, skip it
        $shortName = $this->classNaming->getShortName($name->toString());
        if ($this->isShortNameAlreadyUsedForDifferentFqn($name, $shortName)) {
            return null;
        }

        // @todo always true?
        if ($this->getName($name) === $name->toString()) {
            $fullyQualifiedName = $this->getName($name);

            // the similar end is already imported → skip
            if ($this->shouldSkipName($fullyQualifiedName)) {
                return null;
            }

            $shortName = $this->classNaming->getShortName($fullyQualifiedName);

            $this->useAddingCommander->addUseImport($name, $shortName);

            if ($this->useAddingCommander->isShortImported($name, $fullyQualifiedName)) {
                if ($this->useAddingCommander->isImportShortable($name, $fullyQualifiedName)) {
                    return new Name($shortName);
                }

                return null;
            }

            if (! $this->importsInClassCollection->hasImport($fullyQualifiedName)) {
                if ($name->getAttribute(AttributeKey::PARENT_NODE) instanceof FuncCall) {
                    $this->useAddingCommander->addFunctionUseImport($name, $fullyQualifiedName);
                } else {
                    $this->useAddingCommander->addUseImport($name, $fullyQualifiedName);
                }
            }

            // possibly aliased
            if (in_array($fullyQualifiedName, $this->aliasedUses, true)) {
                return null;
            }

            $this->importsInClassCollection->addImport($fullyQualifiedName);

            return new Name($shortName);
        }

        return null;
    }

    // 1. name is fully qualified → import it
    private function shouldSkipName(string $fullyQualifiedName): bool
    {
        // not namespaced class
        if (! Strings::contains($fullyQualifiedName, '\\')) {
            return true;
        }

        $shortName = $this->classNaming->getShortName($fullyQualifiedName);

        // nothing to change
        if ($shortName === $fullyQualifiedName) {
            return true;
        }

        return $this->importsInClassCollection->canImportBeAdded($fullyQualifiedName);
    }

    /**
     * @param Name|Identifier $node
     */
    private function resolveAlreadyUsedShortNames(Node $node): void
    {
        $namespace = $node->getAttribute(AttributeKey::NAMESPACE_NODE);
        if ($namespace instanceof Namespace_ && $namespace->name instanceof Name) {
            $this->alreadyUsedShortNames[$namespace->name->toString()] = $namespace->name->toString();
        }

        $name = $node->getAttribute('originalName');
        if (! $name instanceof Name) {
            return;
        }

        // already short
        if (Strings::contains($name->toString(), '\\')) {
            return;
        }

        $this->alreadyUsedShortNames[$name->toString()] = $node->toString();
    }

    private function resetCollectedNames(): void
    {
        $this->alreadyUsedShortNames = [];
        $this->aliasedUses = [];
        $this->importsInClassCollection->reset();
        $this->docBlockManipulator->resetImportedNames();
    }

    // is already used
    private function isShortNameAlreadyUsedForDifferentFqn(Name $name, string $shortName): bool
    {
        if (! isset($this->alreadyUsedShortNames[$shortName])) {
            return false;
        }

        return $this->alreadyUsedShortNames[$shortName] !== $this->getName($name);
    }
}
