@props([
    'value' => '',
    'label' => '',
])

<textarea
    {{ $attributes->merge([
        'aria-label' => $label ?? '',
        'class' => 'form-input overflow-hidden'
    ]) }}
    rows="1"
    x-data="{
        baseHeight: 0,
        resize() {
            $el.style.height = this.baseHeight + 'px';

            if ($el.scrollHeight > this.baseHeight) {
                $el.style.height = $el.scrollHeight + 'px';
            }
        }
    }"
    x-init="
        baseHeight = $el.offsetHeight;
        $nextTick(() => resize());
    "
    x-on:input="resize()"
    x-on:reset.window="$nextTick(() => resize())"
    x-on:form-reset.window="$nextTick(() => resize())"
    style="resize: none;"
>
    {!! $value ?? '' !!}
</textarea>
