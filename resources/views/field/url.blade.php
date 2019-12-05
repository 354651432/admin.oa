<div class="form-group">
    <label for="" class="control-label {{ $viewClass["label"] }}">{{ $label }}</label>
    <div class="{{ $viewClass["field"] }}">
        @if($value)
            <a href="{{ $value }}" class="btn btn-link" target="_blank">{{ $value }}</a>
        @else
            <div class="form-control" style="visibility: hidden;"></div>
        @endif
        @if($help)
            <span class="help-block">
                <i class="fa {{ $help["icon"] }}"></i>
                {{ $help["text"] }}
            </span>
        @endif
    </div>
</div>
