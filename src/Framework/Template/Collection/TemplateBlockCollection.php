<?php

namespace Os\Framework\Template\Collection;

use Os\Framework\Template\Struct\TemplateBlockStruct;

/**
 * @method TemplateBlockStruct[] getElements()
 * @method string|int add(TemplateBlockStruct $value, string $key = null)
 * @method TemplateBlockStruct get(string|int $keyOrIndex)
 * @method TemplateBlockCollection remove(string|int $keyOrIndex)
 */
class TemplateBlockCollection extends \Os\Framework\Kernel\Data\Collection\Collection
{
}