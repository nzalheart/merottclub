<div class="tt-score-matrix__names">
    @foreach(['side1' => $side1, 'side2' => $side2] as $side)
        <div class="tt-score-matrix__player" title="{{ trim(($side['title'] ?? '').(empty($side['status']) ? '' : ' — '.$side['status'])) }}">
            <span class="tt-score-matrix__name">{{ $side['name'] }}</span>
            @if(! empty($side['status']))
                <span class="tt-score-matrix__nick">{{ $side['status'] }}</span>
            @elseif(! empty($side['nickname']))
                <span class="tt-score-matrix__nick">{{ $side['nickname'] }}</span>
            @endif
        </div>
    @endforeach
</div>
