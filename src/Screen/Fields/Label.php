<?php

declare(strict_types=1);

namespace Orchid\Screen\Fields;

use Orchid\Screen\Field;

/**
 * Class Label.
 *
 * @method self name(string $value = null)
 * @method self popover(string $value = null)
 */
class Label extends Field
{
    /**
     * @var string
     */
    protected $view = 'platform::fields.label';

    protected $attributes = [
        'id'    => null,
        'value' => null,
    ];

    protected $inlineAttributes = [
        'class',
    ];

    /**
     * @param string|null $name
     *
     * @return Label
     */
    public static function make(string $name = null): self
    {
        return (new static())->name($name);
    }
}
