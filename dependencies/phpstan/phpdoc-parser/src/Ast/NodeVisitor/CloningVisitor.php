<?php

declare (strict_types=1);
namespace WP_Ultimo\Dependencies\PHPStan\PhpDocParser\Ast\NodeVisitor;

use WP_Ultimo\Dependencies\PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use WP_Ultimo\Dependencies\PHPStan\PhpDocParser\Ast\Attribute;
use WP_Ultimo\Dependencies\PHPStan\PhpDocParser\Ast\Node;
final class CloningVisitor extends AbstractNodeVisitor
{
    public function enterNode(Node $originalNode)
    {
        $node = clone $originalNode;
        $node->setAttribute(Attribute::ORIGINAL_NODE, $originalNode);
        return $node;
    }
}
