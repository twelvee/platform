<button class="{{ $class }}"
        data-toggle="dropdown"
        aria-expanded="false"
>
    <i class="{{ $icon ?? '' }} m-r-xs"></i>
    {{ $name ?? '' }}
</button>

<div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow bg-white"
     x-placement="bottom-end"
>
    @foreach($list as $item)
        {!!  $item->build($source) !!}
    @endforeach
</div>
