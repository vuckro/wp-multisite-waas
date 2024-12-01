<?php

/*
 * This file is part of phpDocumentor.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  @link      http://phpdoc.org
 *
 */
declare (strict_types=1);
namespace WP_Ultimo\Dependencies\phpDocumentor\Reflection\PseudoTypes;

use WP_Ultimo\Dependencies\phpDocumentor\Reflection\PseudoType;
use WP_Ultimo\Dependencies\phpDocumentor\Reflection\Type;
use WP_Ultimo\Dependencies\phpDocumentor\Reflection\Types\Integer;
/** @psalm-immutable */
final class IntegerValue implements PseudoType
{
    private int $value;
    public function __construct(int $value)
    {
        $this->value = $value;
    }
    public function getValue() : int
    {
        return $this->value;
    }
    public function underlyingType() : Type
    {
        return new Integer();
    }
    public function __toString() : string
    {
        return (string) $this->value;
    }
}
