<div id="flow-nodes">
    @if(request("_ispreview"))
        <div class="form-horizontal">
            <div class="form-group">
                <label for="" class="control-label col-sm-2">标题：</label>
                <div class="col-sm-4">
                    <input type="text" style="background: transparent;" value="{{ $title }}" readonly=""
                           class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label for="" class="control-label col-sm-2">知会人：</label>
                <div class="col-sm-10" style="padding-top:6px;">
                    @foreach($notify_users as $user)
                        <span class="label label-info">{{ $user }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    <ul class="list-group">
        @foreach($data as $row)
            <li class="list-group-item">
                <table
                    class="table table-condensed {{ $row->current?'text-bold':'' }}"
                    style="margin-bottom: 0">
                    <tr title="{{ $row->step }}/{{ $row->id }}">
                        <td class="col-sm-2">
                            @if($row->type>0)
                                <span class="label label-success">{{ $row->typeText }}</span>
                            @endif
                            {{ $row->operator->name ?? null }}
                            / {{ $row->operator->name ?? null }}</td>
                        <td class="col-sm-2">{{ $row->name ?: $row->op_name }}</td>
                        <td class="col-sm-2">{{ $row->resultText }}</td>
                        <td class="col-sm-2">{{ $row->updated_at }}</td>
                        <td>
                            <button class="btn btn-info btn-xs add-comment" data-id="{{ $row->id }}">留言</button>
                            @if(!request("_ispreview") && $row->is_current && $row->userFlow->status=='0')
                                <button class="btn btn-warning btn-xs notice-btn" data-id="{{ $row->id }}">提醒</button>
                            @endif
                        </td>
                    </tr>
                </table>
                @if($row->comments->count()>0)
                    <div class="col-sm-offset-1">
                        <table class="table" style="margin-bottom: 0;">
                            @foreach($row->comments as $comment)
                                <tr>
                                    <td>{{ $comment->typeText }}:</td>
                                    <td>{{ $comment->content }}</td>
                                    <td>{{ $comment->type > 0 ? ($comment->user->name ?? null) : null }}</td>
                                    <td>{{ $comment->type > 0 ? $comment->created_at : null }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
            @endif
        @endforeach
    </ul>
    <div class="modal fade" id="add-comment-dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/admin/userflows/comment"
                      method="POST"
                      onsubmit="$('#message-button').attr('disabled','disabled')">
                    <div class="modal-header">
                        留言
                    </div>
                    <div class="modal-body">
                        <input type="text" name="content" class="form-control">
                    </div>
                    <div class="modal-footer">
                        {{ csrf_field() }}
                        <input type="hidden" name="id" id="userflow-id">
                        <button type="submit" class="btn btn-default" id="message-button">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        $(".add-comment").click(function () {
            var id = $(event.target).data("id");
            if (!id) {
                return;
            }
            $("#userflow-id").val(id);
            $("#add-comment-dialog").modal();
        });

        $(".notice-btn").click(function () {
            var id = $(event.target).data("id");
            if (!id) {
                return;
            }

            swal({
                title: "流程提醒",
                text: "发送企业微信提醒，失败后发送邮件提醒",
                showCancelButton: true,
            }).then(function (data) {
                if (data.dismiss) {
                    return Promise.reject()
                }

                return new Promise(function (resolve) {
                    $.post("/admin/userflow/notice", {nodeId: id,_token:LA.token}, resolve)
                })
            }).then(function (data) {
                toastr.info(data.message)
            }).catch(function (ex) {
                console.log(ex);
            })
        });
    })
</script>
