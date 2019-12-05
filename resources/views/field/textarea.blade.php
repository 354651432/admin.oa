<div class="col-xs-6">
    <div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">
        <label for="{{$id}}" class="{{$viewClass['label']}} control-label">{{$label}}</label>
        <div class="{{$viewClass['field']}}">
            @include('admin::form.error')
            <div class="input-group">
                <textarea
                    {!! $attributes !!}
                    id="{{ $id }}"
                    name="{{$id}}"
                    class="form-control">{{ $value }}</textarea>
            </div>
            @include('admin::form.help-block')
        </div>
    </div>
</div>
