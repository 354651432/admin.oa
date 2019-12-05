<div class="box box-info">
    <div class="box-header">

    </div>
    <form action="/admin/flow/preapply" class="form-horizontal" method="POST">
        {{ csrf_field() }}
        <input type="hidden" name="_nodes" value="{{ $nodes }}">
        <input type="hidden" name="_forms" value="{{ json_encode($forms) }}">
        @foreach($forms as $form)
            {!! $form->html !!}
            <input type="hidden" name="_validate[{{ $form->name }}]" value="{{ $form->validate??'' }}">
        @endforeach
        <div class="clearfix"></div>
        <div class="form-group">
            <div class="col-sm-2">{!! $users !!}</div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-primary col-sm-offset-3" name="_ispreview" value="1">
                    模拟审核
                </button>
            </div>
        </div>
        <br>
    </form>
</div>
