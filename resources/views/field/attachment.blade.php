<div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">
    <label for="{{$id}}" class="{{$viewClass['label']}} control-label">{{$label}}</label>
    <div class="{{$viewClass['field']}}">
        @include('admin::form.error')
        @if($onlyShow)
            <input type="hidden" name="name" value="{{ $name }}">
            <table class="table">
                <tr>
                    <th>文件</th>
                    <th>上传时间</th>
                    <th>上传者</th>
                </tr>
                @foreach($data as $item)
                    <tr>
                        <td>
                            <a href="{{ $item['url']??null }}" target="_blank">{{ $item['name']??null }}</a>
                        </td>
                        <td>{{ $item['time'] ??null }}</td>
                        <td>{{ $item['user'] ??null }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
        <table class="table attachment">
            <tr>
                <td>
                    <div class="col-sm-10">
                        <input type="file" class="form-control" name="{{$name}}[]"/>
                    </div>
                    <div class="col-sm-2">
                        <button class="btn btn-danger btn-sm" type="button" onclick="attachmentDel(this)">删除
                        </button>
                    </div>
                </td>
            </tr>
        </table>
        @include('admin::form.help-block')
        <br/>
        <br/>
        <br/>
        <button type="button" class="btn btn-success" onclick="attachmentAdd()">增加</button>
        @if($onlyShow)
            <button type="button" class="btn btn-info" onclick="upload(this)">上传</button>
        @endif
    </div>
</div>

<script>
    function attachmentAdd() {
        var tr = $("table.attachment tr").get(0);
        tr = $(tr).clone();
        tr.find("input").val("");
        $("table.attachment").append(tr);
    }

    function attachmentDel(ev) {
        if ($("table.attachment tr").length > 1) {
            $(ev).parents("tr").remove();
        }
    }

    function upload(ev) {
        $(ev)
            .parents("form")
            .attr("action", "/admin/userflows/{{ $userflow_id }}/attachment")
            .attr("enctype", "multipart/form-data")
            .submit();
    }
</script>
