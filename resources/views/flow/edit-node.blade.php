<div class="box">
    <div class="box-body">
        <div id="main" class="form-horizontal">
            <div class="form-group" v-for="node in nodes" ref="nodes" v-if="show">
                <div class="col-sm-1">
                <span class="label" :class="labelClasses[node.step % labelClasses.length]">
                    &nbsp;&nbsp;&nbsp;@{{ node.step }}&nbsp;&nbsp;&nbsp;
                </span>
                </div>
                <label for="" class="control-label col-sm-1">结点名称</label>
                <div class="col-sm-2">
                    <input type="text" class="form-control" v-model="node.name">
                </div>
                <label for="" class="control-label col-sm-1">操作人</label>
                <div class="col-sm-2">
                    <select2 class="form-control" v-model="node.op" :disabled="node.step==1">
                        @foreach($operators as $key => $op)
                            <option value="{{ $key }}">{{ $op }}</option>
                        @endforeach
                    </select2>
                </div>
                <div class="col-sm-4">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                            添加结点 <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="#" @click.prevent="addNode(node)">后续结点</a></li>
                            <li><a href="#" v-if="node.conditions.length>0"
                                   @click.prevent="addNode(node,{conditions:node.conditions})">相同条件后续结点</a>
                            </li>
                            <li role="separator" class.prevent="divider"></li>
                            <li><a href="#" @click.prevent="addNode(node,{type:1})">AND分支</a></li>
                            <li><a href="#" @click.prevent="addNode(node,{type:2})">OR分支</a></li>
                            <li><a href="#" v-if="node.type==1"
                                   @click.prevent="addNode(node,{type:1,step:node.step})">后续AND分支</a>
                            </li>
                            <li><a href="#" v-if="node.type==2"
                                   @click.prevent="addNode(node,{type:2,step:node.step})">后续OR分支</a>
                            </li>
                        </ul>
                    </div>
                    <button class="btn btn-sm btn-info" v-if="node.step>1"
                            @click.prevent="showAddConditionDialog(node)">添加条件
                    </button>
                    <button class="btn btn-sm btn-danger" v-if="nodes.length>1&&node.step>1"
                            @click.prevent="delNode(node)">删除结点
                    </button>
                    <span class="icheck">
                        <label class="radio-inline">
                            限制撤回
                            <input type="checkbox" v-model="node.is_lock" style="position: relative;top:2px">
                        </label>
                    </span>
                </div>
                <div class="col-sm-1">
                    <span class="label label-info" v-if="node.type==1">AND结点</span>
                    <span class="label label-info" v-if="node.type==2">OR结点</span>
                    <span class="label label-success" v-if="node.conditions.length>0"
                          @click="triggerCondShow(node)">
                        条件结点
                        <i class="fa"
                           :class="{'fa-angle-double-up':true,'fa-angle-double-down':node.hideCond}"></i>
                    </span>
                </div>
                <div class="col-sm-5 pull-right" v-show="!node.hideCond">
                    <ul class="list-group" style="margin-bottom: 0">
                        <li class="list-group-item" v-for="cond in node.conditions">
                            @{{ cond|cond2text }}
                        </li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="form-group">
                <form action="/admin/flow/{{ $flow_id }}/edit-node" method="POST" class="col-sm-3 pull-left">
                    {{ csrf_field() }}
                    <input type="hidden" name="nodes" :value="JSON.stringify(nodes)">
                    <button class="btn btn-primary">提交</button>
                </form>
                <button class="btn btn-info pull-left" @click="preview">预览</button>
            </div>

            <div class="modal" id="conditionDialog">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">编辑条件</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <div class="col-sm-3">
                                    <select class="form-control" v-model="conditionObj.name" @change="changeCondition">
                                        <option :value="name" v-for="(form,name,key) in forms">@{{ form.label }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <select v-model="conditionObj.op" class="form-control" @change="changeCondition">
                                        <option :value="key" v-for="(val,key) in conditions" v-html="val"></option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <div v-show="['between','in'].indexOf(conditionObj.op)===-1">
                                        <input type="text" class="form-control" v-model="conditionObj.value">
                                    </div>
                                    <div v-show="conditionObj.op=='between'">
                                        <div class="input-group">
                                            <span class="input-group-addon" id="basic-addon1">从</span>
                                            <input type="text" class="form-control" v-model="conditionObj.value1">
                                            <span class="input-group-addon" id="basic-addon1">到</span>
                                            <input type="text" class="form-control" v-model="conditionObj.value2">
                                        </div>
                                    </div>
                                    <div v-if="conditionObj.op=='in' || conditionObj.op=='not_in'">
                                        <select2 multiple class="form-control" v-model="conditionObj.value3">
                                            <option :value="key" v-for="(value,key) in datasource">
                                                @{{ value }}
                                            </option>
                                        </select2>
                                    </div>
                                </div>
                            </div>
                            <ul class="list-group">
                                <li class="list-group-item" v-for="(item,key) in currentNode.conditions">
                                    @{{ item|cond2text }}
                                    <button class="btn btn-xs btn-danger pull-right" @click="delCond(key)">删除</button>
                                </li>
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" @click="addCond" class="btn btn-info btn-sm">添加</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" name="nodes" :value="JSON.stringify(nodes)">
<link rel="stylesheet" href='/vendor/laravel-admin/AdminLTE/plugins/select2/select2.min.css'/>
<script src='/vendor/laravel-admin/AdminLTE/plugins/select2/select2.full.min.js'></script>
<script src="/js/vue.js"></script>
<script src="/js/axios.min.js"></script>
<script src="/js/Select2.js"></script>
<script>
    window.nodes = {!! json_encode($nodes) !!};
    window.forms = {!! json_encode($forms,JSON_FORCE_OBJECT) !!};
</script>

<script>
    function clone(obj) {
        var str = JSON.stringify(obj);
        return JSON.parse(str);
    }

    var conditions = {
        "eq": "等于",
        "ne": "不等于",
        "gt": "大于",
        "ge": "大于等于",
        "lt": "小于",
        "le": "小于等于",
        "between": "区间",
        "in": "包含",
        "not_in": "不包含",
    };
    var vue = new Vue({
        el: "#main",
        data: {
            nodes: [
                {
                    step: 1,
                    next: 0,
                    name: "",
                    op: '_self',
                    type: 0,
                    conditions: [],
                }
            ],
            conditionObj: {},
            currentNode: {},
            forms: {},

            datasource: {},
            labelClasses: [
                "label-primary", "label-success", "label-info", "label-warning", "label-danger"
            ],
            show: true,
            conditions: conditions,
        },
        created: function () {
            if (window.forms) {
                this.forms = window.forms;
            }

            if (window.nodes && window.nodes.length > 0) {
                this.nodes = window.nodes;
            }
        },
        computed: {
            maxStep: function () {
                var ret = 2;
                for (var key in this.nodes) {
                    if (parseInt(this.nodes[key].step) > ret) {
                        ret = this.nodes[key].step;
                    }
                }

                return ret;
            }
        },
        methods: {
            // 结点操作
            addNode: function (thisNode, config) {
                config = config || {};
                var key = this.nodes.indexOf(thisNode);
                if (key === -1) {
                    this.warning("结点错误 ");
                    return;
                }

                // and or 结点 在最后一个结点后面操作
                while (this.nodes[key + 1] && this.nodes[key].step === this.nodes[key + 1].step) {
                    key++;
                }

                var node = {};
                node.conditions = (config.conditions || []);
                node.op = '_self';
                node.step = config.step || parseInt(this.maxStep) + 1;
                node.type = config.type || 0;

                this.nodes.splice(key + 1, 0, node);

                this.show = false;
                this.$nextTick(function () {
                    vue.show = true;
                })
            },
            delNode: function (thisNode) {
                var key = this.nodes.indexOf(thisNode);
                this.show = false;
                this.nodes.splice(key, 1);
                this.$nextTick(function () {
                    vue.show = true;
                });
            },
            showAddConditionDialog: function (node) {
                this.currentNode = node;
                this.conditionObj = {
                    op: "eq",
                    value3: []
                };
                $("#conditionDialog").modal();
            },
            addCond: function () {
                if (!(this.conditionObj.name && this.conditionObj.op)) {
                    return;
                }
                var obj = clone(this.conditionObj);
                this.currentNode.conditions.push(obj);
            },

            changeCondition: function () {
                if (this.conditionObj.op === 'in' || this.conditionObj.op === 'not_in') {
                    this.show = false;
                    if (this.forms[this.conditionObj.name]) {
                        this.datasource = this.forms[this.conditionObj.name].data;
                    }
                    this.$nextTick(function () {
                        vue.show = true;
                    })
                }
            },
            changeConditionName: function () {
                // todo 控件变量类型判断
                // let form = this.forms[conditionObj.name];
                // if (form.type === 'array') {
                //     this.conditionObj.op = 'in';
                // }
            },
            delCond: function (key) {
                this.currentNode.conditions.splice(key, 1);
            },
            preview: function () {
                console.log("preview");
                var form = document.createElement('form');
                form.action = '/admin/flow/preview';
                form.method = 'POST';
                form.target = '_blank';
                var forms = document.createElement("input");
                forms.type = "hidden";
                forms.name = "forms";

                var formValue = [];
                for (var key in this.forms) {
                    if (!this.forms[key].type) {
                        continue;
                    }
                    formValue.push({
                        name: this.forms[key].name,
                        type: this.forms[key].type,
                        value: this.forms[key].value,
                        datasource: this.forms[key].datasource,
                        datasource_type: this.forms[key].datasource_type,
                        validate: this.forms[key].validate,
                        label: this.forms[key].label,
                        help_text: this.forms[key].help_text,
                    })
                }
                forms.value = JSON.stringify(formValue);
                var nodes = document.createElement("input");
                nodes.type = "hidden";
                nodes.name = "nodes";
                nodes.value = JSON.stringify(this.nodes);

                form.appendChild(nodes);
                form.appendChild(forms);

                document.body.appendChild(form);
                form.submit();
            },
            triggerCondShow: function (node) {
                Vue.set(node, "hideCond", !node.hideCond);
            },
        },
        components: {
            select2: new Select2,
        },
        filters: {
            cond2text: function (obj) {
                var form = forms[obj.name];
                if (!form) {
                    return '';
                }

                if (obj.op === 'between') {
                    return form.label + ' 属于区间 [ ' + obj.value1 + ' , ' + obj.value2 + ')';
                }
                if (obj.op === 'in') {
                    var arr = [];
                    for (var item in obj.value3) {
                        arr.push(form.data[obj.value3[item]]);
                    }
                    return form.label + ' 包含 ' + JSON.stringify(arr)
                }
                if (obj.op === 'not_in') {
                    var arr = [];
                    for (var item in obj.value3) {
                        arr.push(form.data[obj.value3[item]]);
                    }
                    return form.label + ' 不包含 ' + JSON.stringify(arr)
                }


                return form.label + ' ' + conditions[obj.op] + ' ' + obj.value
            }
        }
    })
</script>
