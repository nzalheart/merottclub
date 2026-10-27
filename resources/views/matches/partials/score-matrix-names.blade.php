<div class="tt-score-matrix__names">
    @foreach(['side1' => $side1, 'side2' => $side2] as $side)
        <div class="tt-score-matrix__player" title="{{ trim(($side['title'] ?? '').(empty($side['status']) ? '' : ' — '.$side['status'])) }}">
            <div class="tt-score-matrix__identity">
                @if(! empty($side['avatars']))
                    <span class="tt-score-matrix__avatars">
                        @foreach($side['avatars'] as $avatar)
                            @if(! empty($avatar['url']))
                                <img src="{{ $avatar['url'] }}" alt="" class="tt-score-matrix__avatar" title="{{ $avatar['name'] ?? '' }}">
                            @else
                                <span class="tt-score-matrix__avatar tt-score-matrix__avatar--fallback" title="{{ $avatar['name'] ?? '' }}">{{ $avatar['initials'] ?? '?' }}</span>
                            @endif
                        @endforeach
                    </span>
                @endif
                <span class="tt-score-matrix__text min-w-0">
                    <span class="tt-score-matrix__name">{{ $side['name'] }}</span>
                    @if(! empty($side['status']))
                        <span class="tt-score-matrix__nick">{{ $side['status'] }}</span>
                    @elseif(! empty($side['nickname']))
                        <span class="tt-score-matrix__nick">{{ $side['nickname'] }}</span>
                    @endif
                </span>
            </div>
        </div>
    @endforeach
</div>
