<?php declare(strict_types=1);

namespace Rector\CodingStyle\Application;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use Rector\CodingStyle\Naming\ClassNaming;
use Rector\Contract\PhpParser\Node\CommanderInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;

final class UseAddingCommander implements CommanderInterface
{
    /**
     * @var string[][]
     */
    private $useImportsInFilePath = [];

    /**
     * @var string[][]
     */
    private $functionUseImportsInFilePath = [];

    /**
     * @var UseImportsAdder
     */
    private $useImportsAdder;

    /**
     * @var ClassNaming
     */
    private $classNaming;

    public function __construct(UseImportsAdder $useImportsAdder, ClassNaming $classNaming)
    {
        $this->useImportsAdder = $useImportsAdder;
        $this->classNaming = $classNaming;
    }

    public function addUseImport(Node $node, string $useImport): void
    {
        /** @var SmartFileInfo $fileInfo */
        $fileInfo = $node->getAttribute(AttributeKey::FILE_INFO);
        $this->useImportsInFilePath[$fileInfo->getRealPath()][] = $useImport;
    }

    public function addFunctionUseImport(Node $node, string $functionUseImport): void
    {
        /** @var SmartFileInfo $fileInfo */
        $fileInfo = $node->getAttribute(AttributeKey::FILE_INFO);
        $this->functionUseImportsInFilePath[$fileInfo->getRealPath()][] = $functionUseImport;
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public function traverseNodes(array $nodes): array
    {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($this->createNodeVisitor());

        return $nodeTraverser->traverse($nodes);
    }

    public function isActive(): bool
    {
        return count($this->useImportsInFilePath) > 0 || count($this->functionUseImportsInFilePath) > 0;
    }

    public function isShortImported(Node $node, string $fullyQualifiedName): bool
    {
        $filePath = $this->getRealPathFromNode($node);
        $shortName = $this->classNaming->getShortName($fullyQualifiedName);

        if (isset($this->useImportsInFilePath[$filePath][$shortName])) {
            return true;
        }

        return isset($this->functionUseImportsInFilePath[$filePath][$shortName]);
    }

    public function isImportShortable(Node $node, string $fullyQualifiedName): bool
    {
        $filePath = $this->getRealPathFromNode($node);
        $shortName = $this->classNaming->getShortName($fullyQualifiedName);

        if ($fullyQualifiedName === $this->useImportsInFilePath[$filePath][$shortName]) {
            return true;
        }

        return $fullyQualifiedName === $this->functionUseImportsInFilePath[$filePath][$shortName];
    }

    private function createNodeVisitor(): NodeVisitor
    {
        return new class($this->useImportsAdder, $this->useImportsInFilePath, $this->functionUseImportsInFilePath) extends NodeVisitorAbstract {
            /**
             * @var string[][]
             */
            private $useImportsInFilePath = [];

            /**
             * @var string[][]
             */
            private $functionUseImportInFilePath = [];

            /**
             * @var UseImportsAdder
             */
            private $useImportsAdder;

            /**
             * @param string[][] $useInFilePath
             * @param string[][] $useFunctionInFilePath
             */
            public function __construct(
                UseImportsAdder $useImportsAdder,
                array $useInFilePath,
                array $useFunctionInFilePath
            ) {
                $this->useImportsAdder = $useImportsAdder;
                $this->useImportsInFilePath = $useInFilePath;
                $this->functionUseImportInFilePath = $useFunctionInFilePath;
            }

            public function enterNode(Node $node): ?Node
            {
                // @todo only those in current file path
                // $this->useInFilePath, $this->useFunctionInFilePath

                if ($node instanceof Namespace_) {
                    /** @var SmartFileInfo $fileInfo */
                    $fileInfo = $node->getAttribute(AttributeKey::FILE_INFO);
                    $filePath = $fileInfo->getRealPath();

                    $useImports = $this->useImportsInFilePath[$filePath] ?? [];
                    $functionUseImports = $this->functionUseImportInFilePath[$filePath] ?? [];

                    $this->useImportsAdder->addImportsToNamespace($node, $useImports, $functionUseImports);
                }

                return $node;
            }
        };
    }

    private function getRealPathFromNode(Node $node): string
    {
        /** @var SmartFileInfo $fileInfo */
        $fileInfo = $node->getAttribute(AttributeKey::FILE_INFO);

        return $fileInfo->getRealPath();
    }
}
