<html>
<head>
    <title> {{config('app.name')}} </title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.7.14/vue.min.js" integrity="sha512-BAMfk70VjqBkBIyo9UTRLl3TBJ3M0c6uyy2VMUrq370bWs7kchLNN9j1WiJQus9JAJVqcriIUX859JOm12LWtw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://unpkg.com/vue-select@3.0.0"></script>
    <link rel="stylesheet" href="https://unpkg.com/vue-select@3.0.0/dist/vue-select.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.2/axios.min.js" integrity="sha512-VZ6m0F78+yo3sbu48gElK4irv2dzPoep8oo9LEjxviigcnnnNvnTOJRSrIhuFk68FMLOpiNz+T77nNY89rnWDg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link type="text/css" rel="stylesheet" href="//unpkg.com/bootstrap@4.6.0/dist/css/bootstrap.min.css" />
    <link type="text/css" rel="stylesheet" href="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.css" />
    <script src="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.js"></script>

    <!-- Load the following for BootstrapVueIcons support -->
    <script src="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue-icons.min.js"></script>
    <style>
        .b-toast{
            z-index:999999 !important;
        }
        .float{
	        position:fixed;
            width:25px;
            height:28px;
            bottom:15px;
            right:15px;
            background-color:#0C9;
            color:#FFF;
            border-radius:28px;
            text-align:center;
            box-shadow: 2px 2px 3px #999;
        }
    </style>
@verbatim
</head>
<body>
<div>
    <div id="app">
        <b-modal v-model="showModal" size="xl" :title="bodyArray.length+ ' rows. '+'Pastikan TANPA rumus dan ENTER pada semua cell '+(selectedTable&&selectedTable.is_view?' [CUSTOM UPLOAD]':'')" hide-footer>
            <div>
                <v-select 
                    placeholder="Pilih table yang akan diupload"
                    :options="tablesComplete" 
                    label="model"
                    v-model="selectedTable"
                    @input="tableSelected"
                    style="margin-left: auto;margin-right: auto;width:60%;margin-bottom:5px;">
                </v-select>
                
                <div class="text-center">
                    <textarea rows="8"  class='form-input' style="width:80%;font-size:11px;resize: true;" v-model="excelValue" placeholder="paste excel here" @input="processExcel"></textarea>
                </div>
                <div class="text-center">
                    <b-overlay
                    :show="isLoading"
                    rounded
                    opacity="0.6"
                    spinner-small
                    spinner-variant="primary"
                    class="d-inline-block"
                    >
                        <button class="bg-info"
                            style="margin-left: auto;margin-right: auto;margin-top:5px;" @click="apiLengkapi" :disabled="isLoading">
                            Lengkapi (Alt+Q)
                        </button>
                    </b-overlay>
                    <button class="bg-warning"
                        style="margin-left: auto;margin-right: auto;margin-top:5px;" :disabled="isLoading" @click="apiUploadTest">
                        Test Upload! (Alt+T)
                    </button>
                    <button class="bg-success"
                        style="margin-left: auto;margin-right: auto;margin-top:5px;" :disabled="isLoading" @click="apiUploadReal">
                        Final Upload (Ctrl+S)!
                    </button>
                    <button class="bg-success"
                        style="margin-left: auto;margin-right: auto;margin-top:5px;" :disabled="isLoading" @click="apiUploadWithCreate">
                        Upload ke Temp Table(Alt+P)!
                    </button>
                    
                    <span
                        style="margin-left: auto;margin-right: auto;margin-top:5px;" disabled>
                        Copy to Excel! (Alt+C)
                    </span>
                </div>
            </div>
        </b-modal>
        <div>
            <b-overlay :show="isLoading" rounded="sm">
                <table class="table-auto" style="border: 0.2px solid black;margin-top:10px;padding-right:5px;font-size:11px;" >
                    <thead>
                    <th style="width:3.5em;position:fixed;left:0px;">
                    </th>
                    <th v-for="(item, index) in headersQuery" style="border: 1px solid black;" class="bg-dark">
                        <input class='form-input' type='text' placeholder='queryAll' v-model="headersQuery[index]" @input="query(index)">
                    </th>
                    </thead>
                    <thead>
                    <th style="width:3.5em;position:fixed;left:0px;">
                    </th>
                    <th v-for="(item, index) in headers" style="border: 1px solid black;" :class="headersRequired.includes(item)?'bg-danger':(headersOriginal.includes(item)?'bg-success':'')">
                        {{item}}
                    </th>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in bodyArray" v-if="index <= 99">
                            <td style="width:5em;">
                                {{index+1}}
                            </td>
                            <td v-for="(itemChild, indexChild) in item" style="border: 1px solid black;" :key="key">
                                {{itemChild}}
                                <!-- <input class='form-input' type='text' dalue="itemChild" v-model="bodyArray[index][indexChild]" @input="bodyJson[index][headers[indexChild]]=bodyArray[index][indexChild]"> -->
                            </td>
                        </tr>
                        <tr style="margin-top:10px;" v-if="bodyArray.length>98">
                            <td style="width:5em;">
                                ...{{bodyArray.length-1}}
                            </td>
                            <td v-for="(itemChild, indexChild) in bodyArray[bodyArray.length-1]" style="border: 1px solid black;" :key="key">
                                {{itemChild}}
                                <!-- <input class='form-input' type='text' dalue="itemChild" v-model="bodyArray[index][indexChild]" @input="bodyJson[index][headers[indexChild]]=bodyArray[index][indexChild]"> -->
                            </td>
                        </tr>
                    </tbody>
                </table>
            </b-overlay>
        </div>
        <button href="#" class="float" @click="showModal=true;" v-if="!showModal">
            +
        </button>
    </div>
</div>
@endverbatim

<script>
Vue.component('v-select', VueSelect.VueSelect);
var app = new Vue({
    el: '#app',
    watch: {},
    data: {
        showModal:true,
        isLoading:false,
        key:0,
        port: '8080',
        excelValue:"",
        headers : [],
        headersOriginal : [],
        headersRequired:[],
        headersUnion :[],
        headersQuery : [],
        bodyJson:[],
        bodyArray:[],
        tablesComplete:[],
        selectedTable:null,
        tempTable: 'temp_uploaders'
    },
    created(){       
        let me = this;
        window.addEventListener("keydown", e => {
            if(e.altKey && e.code=='KeyA'){
                me.showModal=true;
                e.preventDefault();
                return false;
            }else if(e.altKey && e.code=='KeyC'){
                me.copyToClipboard();
                e.preventDefault();
                return false;
            }else if(e.altKey && e.code=='KeyQ'){
                me.apiLengkapi();
                e.preventDefault();
                return false;
            }else if(e.altKey && e.code=='KeyT'){
                me.apiUploadTest();
                e.preventDefault();
                return false;
            }else if(e.ctrlKey && e.code=='KeyS'){
                me.apiUploadReal();
                e.preventDefault();
                return false;
            }else if(e.altKey && e.code=='KeyP'){
                me.apiUploadWithCreate();
                e.preventDefault();
                return false;
            }
            
        });
        me.tablesComplete = @json($schema);

    },
    methods: {
        copyToClipboard(){
            let str = "";
            str+=this.headers.join("\t");
            str+="\n";
            this.bodyArray.forEach(dt=>{
                str+=dt.join("\t");
                str+="\n";
            })
            let container = this.$refs.container
            this.$copyText(str, container)
            // const el = document.createElement('textarea');
            // el.value = str;
            // document.body.appendChild(el);
            // el.select();
            // document.execCommand('copy');
            // document.body.removeChild(el);
            this.$bvToast.toast('Ready to Paste to Excel', {
                title: 'Copy OK',
                toaster:'b-toaster-bottom-center',
                variant: 'warning',
                solid: true
            })
        },
        async apiUploadWithCreate(){     
            let me = this;       
            var tableTempName = await prompt('Nama table temporary:', me.tempTable);
            if( !tableTempName ){
                return
            }
            me.tempTable = tableTempName
            me.submitApi({
                url  : "{{url('laradev/uploadwithcreate')}}",
                data : {
                    data:me.bodyJson,
                    table: tableTempName
                }
            },function(response){
                me.$bvToast.toast( response.data, {
                    'auto-hide-delay':15000,
                    title: "INSERT SUKSES",
                    toaster:'b-toaster-top-full',
                    variant: 'success',
                    solid: true
                })
            },function(response){
                me.$bvToast.toast( "INSERT GAGAL!", {
                    'auto-hide-delay':15000,
                    title: "INSERT GAGAL",
                    toaster:'b-toaster-top-full',
                    variant: 'danger',
                    solid: true
                })
            })
        },
        apiLengkapi(){
            let me = this;
            me.submitApi({
                url  : "{{url('laradev/uploadlengkapi')}}",
                data : {
                    data:me.bodyArray
                }
            },function(response){
                me.bodyArray = response.data;
                let newBodyJson = [];
                me.bodyArray.forEach(dt=>{
                    let newData = {};
                    dt.forEach( (dtCol,i) =>{
                        newData[ me.headers[i] ] = dtCol;
                    })
                    newBodyJson.push(newData);
                });
                me.key++;
                me.bodyJson = newBodyJson;
            })
        },
        apiUploadReal(){
            var konfirmasi = confirm('Data Akan Benar-Benar di Upload?');
            if(konfirmasi){
                this.apiUploadTest(true);
            }
        },
        apiUploadTest(final=false){
            const me = this;
            if(!me.selectedTable){
                me.$bvToast.toast("Silahkan pilih table dahulu", {
                    'auto-hide-delay':3000,
                    title: `Error`,
                    toaster:'b-toaster-top-full',
                    variant: 'danger',
                    solid: true
                })
            }
            var url = "{{url('laradev/uploadtest')}}";
            if(me.selectedTable.is_view){
                url = "{{url('public/')}}/"+me.selectedTable.model+"/upload"
            }
            me.submitApi({
                url  : url,
                data : {
                    data:me.bodyJson,
                    table:me.selectedTable.model,
                    columns:me.selectedTable.columns,
                    final:final
                }
            },function(response){
                me.$bvToast.toast( final?"INSERT SUKSES!":"TESTING SUKSES SILAHKAN FINAL UPLOAD!" , {
                    'auto-hide-delay':15000,
                    title: final?"INSERT SUKSES!":"TESTING SUKSES",
                    toaster:'b-toaster-top-full',
                    variant: final?'success':'info',
                    solid: true
                })
            },function(response){
                if(typeof(response)==='string'){
                    me.$bvToast.toast(response, {
                        'auto-hide-delay':15000,
                        title: `Error`,
                        toaster:'b-toaster-top-full',
                        variant: 'danger',
                        solid: true
                    })
                }else{
                    me.$bvToast.toast(response.error, {
                        'auto-hide-delay':15000,
                        title: `Baris ${response.index}`,
                        toaster:'b-toaster-bottom-full',
                        variant: 'danger',
                        solid: true
                    })
                }

            })
        },
        query(i){
            let val = this.headersQuery[i];
            let valOriginal = val;
            let value =val;
            let increment = 0;
            for(let index in this.bodyArray){
                if(valOriginal.includes("<") && valOriginal.includes(">")){
                    value = valOriginal;
                    for(let indexJson in this.bodyJson[index]){
                        let reg = `<${indexJson}>`;
                        value = value.replace(new RegExp(reg,'g'),this.bodyJson[index][indexJson]);
                    }
                    if(valOriginal.includes("<_seq>")){
                        value = value.replace(new RegExp("<_seq>",'g'),++increment);
                    }
                }
                this.bodyArray[index][i] = value;
                this.bodyJson[index][this.headers[i]] = value;
            }
        },
        tableSelected(table){
            if(!table) return;
            this.headers = table.columns.filter(dt=>{
                return !["created_at","updated_at"].includes(dt);
            });
            this.headersOriginal=this.headers;
            this.headersRequired = table.config.required;
            this.bodyJson=[];
            this.bodyArray=[];
            this.headersQuery=[];
            this.headers.forEach(dt=>{
                this.headersQuery.push("");
            });
            this.processExcel();
        },
        processExcel(){
            let me = this;
            this.bodyArray=[];
            let val = this.excelValue;
            let arrayBaris = val.split("\n");
            let headers = arrayBaris[0].split("\t");
            headers.forEach( (dt,i)=>{
                try{
                    headers[i] = headers[i].toLowerCase();
                }catch(e){}
            })
            this.headers = headers;
            let body = [];
            arrayBaris.shift();
            arrayBaris.forEach(dt=>{
                let dataJson = {};
                let bodyArray = dt.split("\t");
                headers.forEach( (head,index)=>{
                    dataJson[head] = bodyArray[index];
                });
                if(bodyArray.length==headers.length){
                    body.push(dataJson);
                    me.bodyArray.push(bodyArray);
                }
            });
            let indexTambahan = 0;
            let keyTambahan = [];
            for( let i in this.headersOriginal){
                if(!this.headers.includes(this.headersOriginal[i])){
                    this.headers.unshift(this.headersOriginal[i]);
                    indexTambahan++;
                    keyTambahan.push(this.headersOriginal[i]);
                }
            }
            if(indexTambahan>0){
                me.bodyArray = me.bodyArray.map(dt=>{
                    let tambahanArray = [];
                    for(let i=1;i<=indexTambahan;i++){
                        dt.unshift("");
                    }
                    return dt;
                })
                body = body.map(dt=>{
                    for(let i=0;i<indexTambahan;i++){
                        dt[keyTambahan[i]]=null;
                    }
                    return dt;
                })
            }
            this.headersQuery=[];
            this.headers.forEach(dt=>{
                this.headersQuery.push("");
            })
            this.bodyJson = body;
        },
        submitApi(data,callback=function(response){},error=function(error){}){
            let me = this;
            var $options   =
            {
                url         : data.url,
                credentials : true,
                method      : 'POST',
                data        : data.data,
                headers     : {
                    laradev:"{{config('editor.password')}}"
                }
            }
            me.isLoading=true;
            axios($options).then(response => {
                callback(response);
            }).catch(errors => {
                error(errors.response.data);
            }).then(function () {
                me.isLoading=false;
            });  ;
        }
    },
})
</script>
</body>
</html>