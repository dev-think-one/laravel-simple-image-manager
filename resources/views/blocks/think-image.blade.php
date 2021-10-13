<img
    class="{{$class??''}}"
    @if($defaultSrc || !$lazy)
    src="{{$defaultSrc??''}}"
    @endif
    {{$lazy?'data-':''}}srcset="{{$srcset??''}}"
    onload="window.requestAnimationFrame(function(){if(!(size=getBoundingClientRect().width))return;onload=null;sizes=Math.ceil(size/window.innerWidth*100)+'vw';});"
    sizes="1px"
    {{$lazy?'data-':''}}src="{{$src??''}}"
    {!! $attributes??'' !!}
>
