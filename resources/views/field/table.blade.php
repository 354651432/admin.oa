<div class="form-group vue-table-{{ $name }}">
    <label for="" class="control-label col-xs-2 {{ $labelClass??null }} noweight">{{ $label }}</label>
    <div class="col-xs-10">
        @section("desc")
        @show
        <table class="table table-condensed">
            <thead>
                <tr>
                    @foreach($titles as $title)
                        <th>{{ $title }}</th>
                    @endforeach
                    @if(!$onlyShow)
                        <th>操作</th>
                    @endif
                </tr>
            </thead>
            <tbody id="main-table-{{$id}}">
                @foreach($controls as $tr)
                    <tr>
                        @foreach($tr as $td)
                            <td>{!! $td !!}</td>
                        @endforeach
                        @if(!$onlyShow)
                            <td>
                                <button type="button" class="btn btn-danger btn-sm table-delete"
                                        onclick="table_del(this)">
                                    删除
                                </button>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            <tbody>
                @section("ext-tr")
                @show
            </tbody>
        </table>
        @if(!$onlyShow)
            <button type="button" class="btn btn-success table-add"
                    onclick="table_add('#main-table-{{$id}}')">
                添加
            </button>
        @section("ext-btn")
        @show
        @endif
    </div>
</div>

<script src="/js/vue.js"></script>
<script>
    var scripts = {!!  json_encode($scripts) !!};
    var controls = {!! json_encode($controls) !!};
    var titles = {!! json_encode($titles) !!};
    var onlyShow = '{!! $onlyShow !!}';
</script>

<script>
    function table_add(tbId) {
        var td = $(tbId).find("tr").first().clone();
        td.find("input").val('');
        td.find('select').val('');
        td.find("span.select2").remove();
        $(tbId).append(td);

        for (var id in scripts) {
            eval(scripts[id])
        }

        if ({}.toString.call(window.update) == '[object Function]') {
            window.update();
        }
    }

    function table_del(ev) {
        $(ev).parents("tr").remove()
    }

</script>

<script>
    ~function () {
        return;
        var vue = new Vue({
            'el': '.vue-table-{{ $name }}',
            data: {
                titles: [],
                controls: [],
                show: true,
            },
            mounted: function () {
                if (titles) {
                    this.titles = titles;
                }
                if (controls) {
                    this.controls = controls;
                }
            },
            methods: {
                add: function () {
                    if (onlyShow) {
                        return;
                    }

                    var first = {};
                    for (var key in this.controls[0]) {
                        first[key] = this.controls[0][key];
                    }
                    this.controls.push(first);

                    this.$nextTick(function () {
                        try {
                            for (var key in scripts) {
                                if (scripts[key]) {
                                    eval(scripts[key]);
                                }
                            }

                            if (window.update) {
                                window.update();
                            }
                        } catch (err) {
                            console.log(err);
                        }
                    })
                },
                del: function (id, ev) {
                    if (onlyShow) {
                        return;
                    }

                    // 最后一个结点不可以删除
                    if (this.controls.length <= 1) {
                        return;
                    }

                    ev.target.parentNode.parentNode.remove();
                },
            }
        })
    }();

</script>

@section("script")
@show
