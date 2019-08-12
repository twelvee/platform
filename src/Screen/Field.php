<?php

declare(strict_types=1);

namespace Orchid\Screen;

use Closure;
use Throwable;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Contracts\View\Factory;
use Orchid\Screen\Contracts\FieldContract;
use Orchid\Screen\Exceptions\FieldRequiredAttributeException;

/**
 * Class Field.
 *
 * @method self accesskey($value = true)
 * @method self type($value = true)
 * @method self class($value = true)
 * @method self contenteditable($value = true)
 * @method self contextmenu($value = true)
 * @method self dir($value = true)
 * @method self hidden($value = true)
 * @method self id($value = true)
 * @method self lang($value = true)
 * @method self spellcheck($value = true)
 * @method self style($value = true)
 * @method self tabindex($value = true)
 * @method self title(string $value = null)
 * @method self options($value = true)
 * @method self autocomplete($value = true)
 */
class Field implements FieldContract
{
    use CanSee;

    /**
     * A set of closure functions
     * that must be executed before data is displayed.
     *
     * @var Closure[]
     */
    private $beforeRender = [];

    /**
     * View template show.
     *
     * @var string
     */
    protected $view;

    /**
     * All attributes that are available to the field.
     *
     * @var array
     */
    protected $attributes = [
        'value' => null,
    ];

    /**
     * Required Attributes.
     *
     * @var array
     */
    protected $required = [
        'name',
    ];

    /**
     * Vertical or Horizontal
     * bootstrap forms.
     *
     * @var string|null
     */
    protected $typeForm;

    /**
     * A set of attributes for the assignment
     * of which will automatically translate them.
     *
     * @var array
     */
    protected $translations = [
        'title',
        'placeholder',
        'help',
    ];

    /**
     * Universal attributes are applied to almost all tags,
     * so they are allocated to a separate group so that they do not repeat for all tags.
     *
     * @var array
     */
    protected $universalAttributes = [
        'accesskey',
        'class',
        'contenteditable',
        'contextmenu',
        'dir',
        'hidden',
        'id',
        'lang',
        'spellcheck',
        'style',
        'tabindex',
        'title',
        'xml:lang',
        'autocomplete',
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [];

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return self
     */
    public function __call(string  $name, array $arguments): self
    {
        $arguments = collect($arguments)->map(function ($argument) {
            return $argument instanceof Closure ? $argument() : $argument;
        });

        if (method_exists($this, $name)) {
            $this->$name($arguments);
        }

        return $this->set($name, $arguments->first() ?? true);
    }

    /**
     * @param mixed $value
     *
     * @return self
     */
    public function value($value): self
    {
        return $this->set('value', $value);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return self
     */
    public function set(string $key, $value = true) : self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * @throws Throwable
     *
     * @return Field
     */
    protected function checkRequired()
    {
        foreach ($this->required as $attribute) {
            throw_if(! collect($this->attributes)->offsetExists($attribute),
                FieldRequiredAttributeException::class, $attribute);
        }

        return $this;
    }

    /**
     *@throws Throwable
     *
     * @return Factory|View|mixed
     */
    public function render()
    {
        if (! $this->isSee()) {
            return;
        }

        $this->runBeforeRender();
        $this->checkRequired();
        $this->translate();
        $this->checkError();

        $id = $this->getId();
        $this->set('id', $id);

        $this->modifyName();
        $this->modifyValue();

        return view($this->view, array_merge($this->getAttributes(), [
            'attributes' => $this->getAllowAttributes(),
            'id'         => $id,
            'old'        => $this->getOldValue(),
            'slug'       => $this->getSlug(),
            'oldName'    => $this->getOldName(),
            'typeForm'   => $this->typeForm ?? $this->vertical()->typeForm,
        ]))
            ->withErrors(session()->get('errors', app(ViewErrorBag::class)));
    }

    /**
     * Localization of fields.
     *
     * @return $this
     */
    private function translate(): self
    {
        $lang = $this->get('lang');

        collect($this->attributes)
            ->intersectByKeys(array_flip($this->translations))
            ->each(function ($value, $key) use ($lang) {
                $this->set($key, __($value, [], $lang));
            });

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return Collection
     */
    protected function getAllowAttributes(): Collection
    {
        $allow = array_merge($this->universalAttributes, $this->inlineAttributes);

        return collect($this->getAttributes())->only($allow);
    }

    /**
     * @return string
     */
    protected function getId(): string
    {
        $lang = $this->get('lang');
        $slug = $this->get('name');
        $hash = sha1(json_encode($this->getAttributes()));

        return Str::slug("field-$lang-$slug-$hash");
    }

    /**
     * @param string     $key
     * @param mixed|null $value
     *
     * @return $this|mixed|null
     */
    public function get($key, $value = null)
    {
        if (! isset($this->attributes[$key])) {
            return $value;
        }

        return $this->attributes[$key];
    }

    /**
     * @return string
     */
    protected function getSlug(): string
    {
        return Str::slug($this->get('name'));
    }

    /**
     * @return mixed
     */
    protected function getOldValue()
    {
        return old($this->getOldName());
    }

    /**
     * @return string
     */
    protected function getOldName(): string
    {
        $name = str_ireplace(['][', '['], '.', $this->get('name'));
        $name = str_ireplace([']'], '', $name);

        return $name;
    }

    /**
     * Checking for errors and filling css class.
     */
    private function checkError()
    {
        if (! $this->hasError()) {
            return;
        }

        $class = $this->get('class');

        if (is_null($class)) {
            $this->set('class', ' is-invalid');

            return;
        }

        $this->set('class', $class.' is-invalid');
    }

    /**
     * @return bool
     */
    private function hasError(): bool
    {
        return optional(session('errors'))->has($this->getOldName()) ?? false;
    }

    /**
     * @return $this
     */
    protected function modifyName()
    {
        $name = $this->get('name');
        $prefix = $this->get('prefix');
        $lang = $this->get('lang');

        if (! is_null($prefix)) {
            $this->set('name', $prefix.$name);
        }

        if (is_null($prefix) && ! is_null($lang)) {
            $this->set('name', $lang.$name);
        }

        if (! is_null($prefix) && ! is_null($lang)) {
            $this->set('name', $prefix.'['.$lang.']'.$name);
        }

        if ($name instanceof Closure) {
            $this->set('name', $name($this->attributes));
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function modifyValue()
    {
        $value = $this->getOldValue() ?: $this->get('value');

        $this->set('value', $value);

        if ($value instanceof Closure) {
            $this->set('value', $value($this->attributes));
        }

        return $this;
    }

    /**
     * Create a group of the fields.
     *
     * @param Closure|array $group
     *
     * @return mixed
     */
    public static function group($group)
    {
        if (! is_array($group)) {
            return $group();
        }

        return $group;
    }

    /**
     * Use vertical layout for the field.
     *
     * @return $this
     */
    public function vertical(): self
    {
        $this->typeForm = 'platform::partials.fields.vertical';

        return $this;
    }

    /**
     * Use horizontal layout for the field.
     *
     * @return $this
     */
    public function horizontal(): self
    {
        $this->typeForm = 'platform::partials.fields.horizontal';

        return $this;
    }

    /**
     * Create separate line after the field.
     *
     * @return $this
     */
    public function hr(): self
    {
        $this->set('hr');

        return $this;
    }

    /**
     * @param Closure $closure
     *
     * @return mixed|self
     */
    public function addBeforeRender(Closure $closure): self
    {
        $this->beforeRender[] = $closure;

        return $this;
    }

    /**
     * Alternately performs all tasks.
     */
    public function runBeforeRender()
    {
        foreach ($this->beforeRender as $before) {
            $before->call($this);
        }
    }
}
