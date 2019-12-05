<div
    class="{{$viewClass['form-group']}} {!! ($errors->has($errorKey['start'].'start') || $errors->has($errorKey['end'].'end')) ? 'has-error' : ''  !!}">
    <label for="{{$id}}_start" class="{{$viewClass['label']}} control-label">{{$label}}</label>
    @include('admin::form.error')
    <div class="col-sm-8">
        <div class="col-xs-6" style="padding-left:0">
            <input id="{{$id}}_start"
                   type="text"
                   name="{{$name['start']}}"
                   value="{{ old($column['start'], $value['start']??null) }}"
                   class="form-control" {!! $attributes !!} />
        </div>

        <div class="col-xs-6" style="padding-right: 0;">
            <input id="{{$id}}_end"
                   type="text"
                   name="{{$name['end']}}"
                   value="{{ old($column['end'], $value['end']??null) }}"
                   class="form-control" {!! $attributes !!} />
        </div>
    </div>
    @include('admin::form.help-block')
</div>
