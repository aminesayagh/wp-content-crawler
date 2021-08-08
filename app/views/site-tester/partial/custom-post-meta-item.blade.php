<?php

/** @var array $item */
$content = array_get($item, "data");

?>

<div class="post-meta-item">
    {{-- META KEY --}}
    <div class="post-meta-key">
        <span class="name">{{ _wpcc("Meta key") }}</span>
        <span>{{ array_get($item, "meta_key") }}</span>
    </div>

    {{-- MULTIPLE --}}
    <div class="post-meta-multiple">
        <span class="name">{{ _wpcc("Multiple") }}</span>
        <span class="dashicons dashicons-{{ array_get($item, "multiple") ? 'yes' : 'no' }}"></span>
    </div>

    {{-- META CONTENT --}}
    <div class="post-meta-content">
        <span class="name">{{ _wpcc("Content") }}</span>
        @if(is_array($content))
            <div>
                <ol>
                    @foreach($content as $value)
                        <li>{{ $value }}</li>
                    @endforeach
                </ol>
            </div>
        @else
            <span>{{ $content }}</span>
        @endif
    </div>
</div>