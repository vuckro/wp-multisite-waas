<?php

/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2023 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */
namespace WP_Ultimo\Dependencies\setasign\Fpdi\PdfParser\Filter;

use WP_Ultimo\Dependencies\setasign\Fpdi\PdfParser\PdfParserException;
/**
 * Exception for filters
 */
class FilterException extends PdfParserException
{
    const UNSUPPORTED_FILTER = 0x201;
    const NOT_IMPLEMENTED = 0x202;
}
