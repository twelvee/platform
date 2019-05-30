<?php

declare(strict_types=1);

namespace Orchid\Screen\Fields;

use Orchid\Screen\Field;
use Orchid\Support\Assert;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Relation.
 *
 * @method self accesskey($value = true)
 * @method self autofocus($value = true)
 * @method self disabled($value = true)
 * @method self form($value = true)
 * @method self name(string $value = null)
 * @method self required(bool $value = true)
 * @method self size($value = true)
 * @method self tabindex($value = true)
 * @method self help(string $value = null)
 * @method self placeholder(string $placeholder = null)
 * @method self popover(string $value = null)
 */
class Relation extends Field
{
    /**
     * @var string
     */
    public $view = 'platform::fields.relation';

    /**
     * Default attributes value.
     *
     * @var array
     */
    public $attributes = [
        'class'          => 'form-control',
        'value'          => [],
        'relationScope'  => null,
        'relationAppend' => null,
    ];

    /**
     * @var array
     */
    public $required = [
        'name',
        'relationModel',
        'relationName',
        'relationKey',
        'relationScope',
        'relationAppend',
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    public $inlineAttributes = [
        'accesskey',
        'autofocus',
        'disabled',
        'form',
        'multiple',
        'placeholder',
        'name',
        'required',
        'size',
        'tabindex',
    ];

    /**
     * @param string|null $name
     *
     * @return self
     */
    public static function make(string $name = null): self
    {
        return (new static())->name($name);
    }

    /**
     * @return self
     */
    public function multiple(): self
    {
        $this->attributes['multiple'] = 'multiple';

        return $this;
    }

    /**
     * @param string|Model $model
     * @param string       $name
     * @param string|null  $key
     *
     * @return self
     */
    public function fromModel(string $model, string $name, string $key = null): self
    {
        $key = $key ?? (new $model())->getModel()->getKeyName();

        $this->set('relationModel', Crypt::encryptString($model));
        $this->set('relationName', Crypt::encryptString($name));
        $this->set('relationKey', Crypt::encryptString($key));

        $this->addBeforeRender(function () use ($model, $name, $key) {

            $append = $this->get('relationAppend');

            if (is_string($append)) {
                $append = Crypt::decryptString($append);
            }

            $text = $append ?? $name;

            $value = $this->get('value');

            if (! is_iterable($value)) {
                $value = Arr::wrap($value);
            }

            if (Assert::isIntArray($value)) {
                $value = $model::whereIn($key, $value)->get();
            }

            $value = collect($value)
                ->map(function ($item) use ($text, $key) {
                    return [
                        'id'   => $item->$key,
                        'text' => $item->$text,
                    ];
                })->toJson();

            $this->set('value', $value);
        });

        return $this;
    }

    /**
     * @param string $scope
     *
     * @return $this
     */
    public function applyScope(string $scope): self
    {
        $scope = lcfirst($scope);

        $this->set('relationScope', Crypt::encryptString($scope));

        return $this;
    }

    /**
     * @param string $append
     *
     * @return Relation
     */
    public function displayAppend(string $append): self
    {
        $this->set('relationAppend', Crypt::encryptString($append));

        return $this;
    }
}
