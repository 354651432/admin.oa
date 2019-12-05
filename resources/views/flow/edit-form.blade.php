<div class="form-horizontal" id="main">

    <div class="box box-warning ">
        <div class="box-header">
            <div class="col-sm-2">新增控件</div>
            <div class="col-sm-4">
                <label for="select-control" class="control-label col-sm-4">已保存的控件</label>
                <div class="col-sm-8">
                    <select2 style="width:100%;" class="form-control" @change="loadControl($event)">
                        <option></option>
                        @foreach($controls  as $id => $control)
                            <option value="{{ $id }}">{{ $control }}</option>
                        @endforeach
                    </select2>
                </div>
            </div>
            <div class="col-sm-4">
                <button type="button" v-if="!edit_name" @click="add" class="btn btn-info btn-sm col-sm-offset-4">
                    添加
                </button>
                <button type="button" v-if="edit_name" @click="edit_complete"
                        class="btn btn-primary btn-sm col-sm-offset-4">
                    完成修改
                </button>
                <button type="button" @click="save" class="btn btn-success btn-sm">
                    保存控件
                </button>
            </div>
            <button class="btn btn-box-tool pull-right" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
        <div class="box-body with-border">
            <div class="form-group">
                <label for="" class="control-label col-sm-2 asterisk">名称</label>
                <div class="col-sm-8">
                    <input type="text" v-model="form.label" :disabled="disable" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label for="" class="control-label col-sm-2 asterisk">表单name</label>
                <div class="col-sm-8">
                    <input type="text" v-model="form.name" :disabled="disable" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label for="select-type" class="control-label col-sm-2 asterisk">类型</label>
                <div class="col-sm-8">
                    <select2 class="form-control form-type" :disabled="disable" style="width:100%;" v-model="form.type">
                        @foreach($controlTyps as $ctrl)
                            <option value="{{ $ctrl }}">{{ $ctrl }}</option>
                        @endforeach
                    </select2>
                </div>
            </div>
            <div class="form-group">
                <label for="" class="control-label col-sm-2">默认值</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" :disabled="disable" v-model="form.value">
                </div>
            </div>
            <div class="form-group">
                <label for="" class="control-label col-sm-2">提示信息</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" :disabled="disable" v-model="form.help_text">
                </div>
            </div>
            <div class="form-group">
                <label for="" class="control-label col-sm-2">校验规则</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" :disabled="disable" v-model="form.validate">
                    <span class="help-block">
                        <a target="_blank" href="https://learnku.com/docs/laravel/5.5/validation/1302">https://learnku.com/docs/laravel/5.5/validation/1302</a>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label for="" class="control-label col-sm-2">数据源类型</label>
                <div class="col-sm-8">
                    <label>
                        <input type="radio" value="0" v-model="form.datasource_type">
                        JSON
                    </label>
                    <label>
                        <input type="radio" value="1" v-model="form.datasource_type">
                        SQL
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label for="datasource" class="control-label col-sm-2">数据源</label>
                <div class="col-sm-8">
                    <textarea name="" :disabled="disable" class="form-control" id="datasource"
                              v-model="form.datasource"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-warning">
        <div class="box-header"></div>
        <div class="box-body with-border">
            <div v-for="(item,name,key) in forms" :class="item.type">
                <div v-html="item.html" class="col-sm-10"></div>
                <div class="col-sm-2">
                    <button type="button" class="btn btn-xs btn-success" @click="up(item.name)">上移</button>
                    <button type="button" class="btn btn-xs btn-success" @click="down(item.name)">下移</button>
                    <button type="button" class="btn btn-xs btn-warning" @click="del(item.name)">删除</button>
                    <button type="button" class="btn btn-xs btn-primary" @click="edit(item)">修改</button>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <div class="form-group">
        <form method="POST" action="/admin/flow/{{$flow_id}}/edit-form" class="col-sm-2">
            {{ csrf_field() }}
            <input type="hidden" name="forms" :value="JSON.stringify(forms)">
            <button type="submit" class="btn btn-primary">提交</button>
        </form>
    </div>
    <div class="modal fade" id="save-control-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    保存控件
                </div>
                <div class="modal-body">
                    <input type="text" placeholder="名称" v-model="form.title" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" @click="saveControl" class="btn btn-sm btn-default">保存</button>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href='/vendor/laravel-admin/AdminLTE/plugins/select2/select2.min.css'/>
<script src='/vendor/laravel-admin/AdminLTE/plugins/select2/select2.full.min.js'></script>
<script src="/js/vue.js"></script>
<script src="/js/axios.min.js"></script>
<script src="/js/Select2.js"></script>
<style>
    .table-hide {
        display: none;
    }
</style>
<script>
    window.forms = {!! json_encode($forms,JSON_FORCE_OBJECT) !!};
</script>
<script>
    function clone(obj) {
        var str = JSON.stringify(obj);
        return JSON.parse(str);
    }

    var vue = new Vue({
        name: "FlowForm",
        el: "#main",
        props: ["controls", "controltypes"],
        data: function () {
            return {
                controliId: '',
                form: {
                    type: "Text",
                    datasource_type: 0,
                },
                forms: {},
                edit_name: null,
                disable: false,
            }
        },
        created: function () {
            if (window.forms) {
                this.forms = window.forms;
            }
        },
        methods: {
            add: function (id = 0) {
                if (!this.form.name) {
                    this.info("缺少控件name", "warning");
                    return
                }

                if (!this.form.label) {
                    this.info("缺少控件名称", "warning");
                    return
                }

                var form = clone(this.form);
                if (!form.html) {
                    vue.disable = true;
                    axios.post('/admin/flow/render', this.form).then(function (p) {
                        vue.disable = false;

                        form.html = p.data.html;
                        form.options = p.data.options;
                        form.js = p.data.js;
                        form.css = p.data.css;
                        form.script = p.data.script;

                        vue.addForm(form, id);
                    })
                } else {
                    this.addForm(form, id);
                }
            },
            save: function () {
                $("#save-control-modal").modal();
            },
            saveControl: function () {
                vue.disable = true;
                axios.post('/admin/flow/saveControl', this.form).then(function (p) {
                    vue.disable = false;
                    vue.info(p.data.message)
                }).catch(function (err) {
                    vue.info(err.response.data.message, "warning")
                })
            },
            del: function (key) {
                delete this.forms[key];
                this.$forceUpdate();
            },
            up: function (key) {
                this.down(key, -1);
            },
            down: function (key, step = null) {

                step = step || 1;
                var keys = Object.getOwnPropertyNames(this.forms);
                var id = keys.indexOf(key);
                keys.splice(id, 1);
                keys.splice(id + step, 0, key);

                var forms = {};
                var scripts = [];
                for (var i in keys) {
                    var key1 = keys[i];
                    if (key1 === '__ob__') {
                        continue;
                    }

                    var form = clone(this.forms[keys[i]]);
                    forms[keys[i]] = form;

                    if (form.script) {
                        scripts.push(form.script);
                    }
                }

                this.forms = forms;
                for (var i in scripts) {
                    ~function (script) {
                        vue.$nextTick(function () {
                            try {
                                eval(script)
                            } catch (err) {

                            }
                        })
                    }(scripts[i]);
                }
            },
            addForm: function (form) {
                this.forms[form.name] = form;
                this.form.name = '';
                this.form.label = '';
                this.form.value = '';
                this.form.validate = '';
                this.form.datasource = '';
                this.form.help_text = '';
                this.$forceUpdate();
                if (form.js) {
                    for (var key in form.js) {
                        var src = form.js[key];
                        var js = document.createElement('script');
                        js.src = src;
                        document.head.appendChild(js)
                    }
                }

                if (form.css) {
                    for (var key in form.css) {
                        var href = form.css[key];
                        var css = document.createElement('link');
                        css.rel = "stylesheet";
                        css.href = href;
                        document.head.appendChild(css)
                    }
                }

                if (form.script) {
                    this.$nextTick(function () {
                        try {
                            eval(form.script);
                        } catch (err) {
                        }
                    });
                }
            },
            loadControl: function (id) {
                if (!id) {
                    return;
                }

                vue.disable = true;
                axios.get("/admin/flow/control/" + id).then(function (p) {
                    vue.disable = false;
                    vue.form = p.data;
                })
            },
            info: function (msg, type = "success") {
                swal({
                    title: msg,
                    type: type,
                })
            },
            edit: function (form) {
                this.edit_name = form.name;
                this.form = form;
            },
            edit_complete: function () {
                if (!this.form.name) {
                    this.info("缺少控件name", "warning");
                    return
                }

                if (!this.form.label) {
                    this.info("缺少控件名称", "warning");
                    return
                }
                var name = this.edit_name;
                this.edit_name = null;

                vue.disable = true;
                axios.post('/admin/flow/render', this.form).then(function (p) {
                    vue.disable = false;
                    this.forms[name].html = p.data.html;
                });

                this.form = {
                    type: "Text",
                    datasource_type: 0,
                };
            }
        },
        components: {
            select2: new Select2,
        }
    })
</script>
