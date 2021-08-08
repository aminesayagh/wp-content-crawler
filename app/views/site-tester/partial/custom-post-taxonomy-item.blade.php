<?php

/** @var array $item */
$content = array_get($item, "data");

?>

<div class="post-taxonomy-item">
    {{-- TAXONOMY NAME --}}
    <div class="post-taxonomy-name">
        <span class="name">{{ _wpcc("Taxonomy") }}</span>
        <span>{{ array_get($item, "taxonomy") }}</span>
    </div>

    {{-- APPEND --}}
    <div class="post-meta-append">
        <span class="name">{{ _wpcc("Append") }}</span>
        <span class="dashicons dashicons-{{ array_get($item, "append") ? 'yes' : 'no' }}"></span>
    </div>

    {{-- TAXONOMY VALUE --}}
    <div class="post-taxonomy-value">
        <span class="name">{{ _wpcc("Value") }}</span>
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