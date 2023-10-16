<html style="scroll-behavior: smooth;">
    <head>
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="Pragma" content="no-cache" />
        <meta http-equiv="Expires" content="0" />
        <title>API DOC</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/codemirror.min.css" integrity="sha512-uf06llspW44/LZpHzHT6qBOIVODjWtv4MxCricRxkzvopAlSWnTf6hpZTFxuuZcuNE9CBQhqE0Seu1CoRk84nQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/theme/monokai.min.css" integrity="sha512-R6PH4vSzF2Yxjdvb2p2FA06yWul+U0PDDav4b/od/oXf9Iw37zl10plvwOXelrjV2Ai7Eo3vyHeyFUjhXdBCVQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/lint/lint.min.css" integrity="sha512-jP1+o6s94WQS9boYeDP3Azh8ihI5BxGgBZNjkABhx05MqH5WuDzfzSnoPxGxVzWi/gxxVHZHvWkdRM6SMy5B0Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <style>
            .title{
                margin-bottom: 3px;
                    font-weight: bold
                }

                .judul{
                    text-shadow: 1px 1px grey;
                    font-weight: bold
                }

                .samplecode{
                    width:45%;
                    font-size:10.5px;
                }
                .button{
                    background-color: forestgreen;
                    color:white;
                    margin-left: 5px;
                    margin-top:3px;
                    border-radius: 5px;
                    cursor: pointer;
                    display: inline-block;
                    text-align: center;
                }
                #codemirror{
                    transition:all 0.25s ease-in;
                    right:10px;
                    top:25px;
                    position:fixed;
                    width:50%;
                }

                .CodeMirror{
                    height: 560px !important;
                    font-size: 10.5px;
                    box-shadow: 5px 5px grey;
                    overflow-x:hidden !important;
                    resize: vertical;
                    overflow: auto !important;
                }
                .CodeMirror-gutter-wrapper{
                    
                    left: -50 !important;
                }
        </style>
    </head>
    <body>
        <p><span style="padding:5 5px 5 5px;position:fixed;right:1%;top:0px;font-weight:bold;background-color:green;color:white" id="modelSelected"></span>
            
        </p>
        <div id="codemirror">
            <textarea id="code" style="display:none"></textarea>
            <p><button style="position:fixed;right:10px;bottom:40px; background-color:red;color:white;z-index:99" id="toggle">Hide!</button>
            <!-- <a href="javascript:void(0)" class="button" id="run" style="margin-left:75% !important">Run on Console!</a> -->
            </p>
        </div>
        <div>
            <table border="1">
                <thead>
                    <th>Resources</th>
                    <th colspan="4">Actions</th>
                </thead>
                <tbody>
                    <tr>
                        <td id="model_login">
                            Authorization
                        </td>
                        <td colspan="4" align="center">
                            <button id="login_login" href="javascript:void(0)">LOGIN</button>
                        </td>
                    </tr>
                    @foreach($schema as $key => $model)              
                        <tr>
                            <td style="padding:0 5 0 5;transition: all 0.2s ease-in" id="model_{{$model->model}}" class="model-name">{{$model->model}}</td>
                            <td><button id="btn_read_{{$model->model}}" class="read" href="javascript:void(0)" style="font-size:10px" index={{$key}}>READ</button></td>
                            <td><button id="btn_create_{{$model->model}}" class="create" href="javascript:void(0)" style="font-size:10px" index={{$key}}>CREATE</button></td>
                            <td><button id="btn_update_{{$model->model}}" class="update" href="javascript:void(0)" style="font-size:10px" index={{$key}}>UPDATE</button></td>
                            <!-- <td><button id="btn_notice_{{$model->model}}" class="notice" href="javascript:void(0)" style="font-size:10px" index={{$key}}>NOTICE</button></td> -->
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.2/axios.min.js" integrity="sha512-VZ6m0F78+yo3sbu48gElK4irv2dzPoep8oo9LEjxviigcnnnNvnTOJRSrIhuFk68FMLOpiNz+T77nNY89rnWDg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/codemirror.min.js" integrity="sha512-2359y3bpxFfJ9xZw1r2IHM0WlZjZLI8gjLuhTGOVtRPzro3dOFy4AyEEl9ECwVbQ/riLXMeCNy0h6HMt2WUtYw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/mode/loadmode.min.js" integrity="sha512-h/UMHULKoaDZbSQFARIySNdVmLtXWpXjMoFoinjVrSPSj+oLtVbS9B0/UqyM9wAj2Lwhc72EleGOShBP4XK8CA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/fold/foldcode.min.js" integrity="sha512-Q2qfEJEU257Qlqc4/5g6iKuJNnn5L0xu2D48p8WHe9YC/kLj2UfkdGD01qfxWk+XIcHsZngcA8WuKcizF8MAHA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/fold/brace-fold.min.js" integrity="sha512-5MuaB1PVXvhsYVG0Ozb0bwauN7/D1VU4P8dwo5E/xiB9SXY+VSEhIyxt1ggYk2xaB/RKqKL7rPXpm1o1IlTQDA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/fold/comment-fold.min.js" integrity="sha512-POq5oizlc/SrDJVaPG9eRo020t5igLlyXnOEPl854IgtRDnRCi9D3RAdOS1dhZ3Y0SmcyDVwyt2z2uFj3WYcbg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/keymap/sublime.min.js" integrity="sha512-SV3qeFFtzcmGtUQPLM7HLy/7GKJ/x3c2PdiF5GZQnbHzIlI2q7r77y0IgLLbBDeHiNfCSBYDQt898Xp0tcZOeA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/lint/json-lint.min.js" integrity="sha512-40xVcCik6TlUiZadnRc6ZM0BN65s7F+C3K7eBqGRf8dmjKApjzoiT/GB1GJmdICOZbXjJCiTBbVlsIvFs8A/+Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/addon/hint/javascript-hint.min.js" integrity="sha512-omIxBxPdObb7b3giwJtPBiB86Mey/ds7qyKFcRiaLQgDxoSR+UgCYEFO7jRZzPOCZAICabGCraEhOSa71U1zFA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/javascript/javascript.min.js" integrity="sha512-Cbz+kvn+l5pi5HfXsEB/FYgZVKjGIhOgYNBwj4W2IHP2y8r3AdyDCQRnEUqIQ+6aJjygKPTyaNT2eIihaykJlw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            var codeMirrorElement = document.getElementById("codemirror");
            function submitApi(data,callback=function(response){},error=function(error){}){
                let me = this;
                var $options   =
                {
                    url         : data.url,
                    credentials : true,
                    method      : 'POST',
                    data        : data.data,
                    headers     : {
                        laradev:"frontend-only",
                        "Content-Type" :"Application/json"
                    }
                }
                axios($options).then(response => {
                    callback(response);
                }).catch(errors => {
                    error(errors.response.data);
                }).then(function () {
                    
                });  ;
            }
            var data = @json($schema);
            var codemirror = CodeMirror.fromTextArea(document.getElementById("code"), {
                lineNumbers: false,
                mode: "javascript",
                viewportMargin: Infinity,
                theme:"monokai",
                keyMap:"sublime",
                matchBrackets: true,
                continueComments: "Enter",
                lint: true
                });
            
            var isToggled=false;
            document.getElementById("toggle").onclick = function(){
                if(isToggled){
                    codeMirrorElement.style.transform="translateX(150%)"
                    codeMirrorElement.style.display = "block";
                    setTimeout(function(){
                        codeMirrorElement.style.transform="translateX(0%)"
                    },500)
                    document.getElementById("modelSelected").style.display = "block";
                    isToggled = false;
                }else{
                    codeMirrorElement.style.display = "none";
                    document.getElementById("modelSelected").style.display = "none";
                    isToggled = true;
                }
            };
            var buttonLogin = document.getElementById('login_login');
            buttonLogin.addEventListener('click',function(){
                window.location.hash=`login_login`
                codemirror.setValue("");
                var newData = {
                    url: "/login",
                    method: "POST",
                    headers: {
                        "Cache-Control": "no-cache"
                    },
                    body      : {
                        "email":"email or username",
                        "password":"password"
                    },
                    response:{
                        "access_token": {
                            "id": "xxxxx",
                            "user_id": 0,
                            "client_id": 0,
                            "name": "user name",
                            "scopes": [],
                            "revoked": false,
                            "expires_at": "YYYY-MM-DD HH:MM:ss"
                        },
                        "token": "secret_token",
                        "token_type": "Bearer",
                        "data": {
                            "id": 0,
                            "name": "User Name",
                            "email": "email",
                            "username": "username"
                        }
                    }
                };
        
                codemirror.setValue(JSON.stringify(newData,null,"\t"));
                document.getElementById("modelSelected").innerText="Login";
                codeMirrorElement.style.transform="translateX(150%)"
                codeMirrorElement.style.display = "block";
                setTimeout(function(){
                    codeMirrorElement.style.transform="translateX(0%)"
                })
                document.getElementById("modelSelected").style.display = "block";
            });
            var classname = document.getElementsByClassName("read");
            Array.from(classname).forEach(function(element) {
                element.addEventListener("click",function(){
                    codemirror.setValue("");
                    let index = element.getAttribute("index");
                    let arrayData = data[index];
                    window.location.hash=`read_${arrayData.model}`
                    var newData = {
                        url: "/{{config('api.route_prefix')}}/"+(arrayData.model.includes(".")?arrayData.model.split(".")[1]:arrayData.model),
                        method: "GET",
                        headers: {
                            "authorization": "BearerToken",
                            "Cache-Control": "no-cache"
                        },
                        parameters_for_list_and_single      : {
                            selectfield : "column_name1,column_name2,column3,dst",
                            join        : true,
                            joinmax     : 0,
                            transform   : true,
                            casts       : "column_name1:array,column_name2:datetime:d-m-Y",
                            api_version : "2"
                        },
                        parameters_read_list : {
                            page        : 1,
                            paginate    : 100,
                            order_by     : "column_name",
                            order_type   : "ASC",
                            order_by_raw  : "column_name1 ASC,column_name2 DESC",
                            scopes        : "scope1,scope2",
                            filter_column : "searchText",
                            filter_column_another : "searchText OTHER",
                            filter_operator : "~*",
                            if_column   : "filterText",
                            if_column_another  : "> 200",
                            search      : "keyword",
                            searchfield : "column_name1,column_name2",
                            notin       : "column_name:12,13,99",
                            addselect   : "column_name1,sum(column) as sumfield",
                            group_by    : "column_name1,column_name2,column3",
                            query_name  : "Query Name di /docs/frontend-params",
                            where       : "column_name1='kata' AND column_name2 ~* 'caridata'",
                        },
                        parameters_read_single: {
                            single      : false,
                            simplest    : false
                        },
                        basic_response  : arrayData.columns,
                        real_response  : "Silahkan dicoba di [{{url('docs/api-request')}}] atau POSTMAN"
                    };
                
                    codemirror.setValue(JSON.stringify(newData,null,"\t"));
                    document.getElementById("modelSelected").innerText=arrayData.model;
                    codeMirrorElement.style.transform="translateX(150%)"
                    codeMirrorElement.style.display = "block";
                    setTimeout(function(){
                        codeMirrorElement.style.transform="translateX(0%)"
                    },500)
                    document.getElementById("modelSelected").style.display = "block";
                });
            });
            var classname = document.getElementsByClassName("create");
            Array.from(classname).forEach(function(element) {
                element.addEventListener("click",function(){
                    codemirror.setValue("");
                    let index = element.getAttribute("index");
                    let arrayData = data[index];
                    window.location.hash=`create_${arrayData.model}`
                    var newData = {
                        payloadFields : arrayData.config.createable,
                    };
                    let fullColumns = arrayData.fullColumns.filter(dt=>!['creator_id','editor_id','approver_id'].includes(dt));
                    var newForm = {};
                    for(let i=0; i<fullColumns.length;i++){
                        if( arrayData.config.createable.includes(fullColumns[i].name) && ( !fullColumns[i].comment || (fullColumns[i].comment && !(fullColumns[i].comment).includes('fk')) ) ){
                            var susunan = "";
                            susunan += fullColumns[i].nullable?"{required}-[":"{optional}-[";
                            susunan += (fullColumns[i].type).replace("\\","")+"]" ;
                            susunan += (!fullColumns[i].comment?"-<data:input>":"-<data:"+fullColumns[i].comment+">") ;
                            newForm[fullColumns[i].name] = susunan;
                        }
                    }
                    
                    (arrayData.details).forEach(dt=>{
                        let arrayDataDetail = data.find(dtl=>{
                            return dtl.model == dt;
                        });
                        let fullColumnsDetail = arrayDataDetail.fullColumns;
                        let detailsPayload = {};
                        for(let i=0; i<fullColumnsDetail.length;i++){
                            if( arrayDataDetail.config.createable.includes(fullColumnsDetail[i].name) && (!fullColumnsDetail[i].comment || (fullColumnsDetail[i].comment && !(fullColumnsDetail[i].comment).includes('fk'))) ){
                                var susunan = "";
                                susunan += fullColumnsDetail[i].nullable?"{required}-[":"{optional}-[";
                                susunan += (fullColumnsDetail[i].type).replace("\\","")+"]" ;
                                susunan += (!fullColumnsDetail[i].comment?"-<data:input>":"-<data:"+fullColumnsDetail[i].comment+">") ;
                                detailsPayload[fullColumnsDetail[i].name] = susunan;
                            }
                            (arrayDataDetail.details).forEach(subdt=>{
                                let arrayDataDetailDetail = data.find(subdtl=>{
                                    return subdtl.model == subdt;
                                });
                                let fullColumnsDetailHeirs = arrayDataDetailDetail.fullColumns;
                                let detailsPayloadHeirs = {};
                                for(let i=0; i<fullColumnsDetailHeirs.length;i++){
                                    if( arrayDataDetailDetail.config.createable.includes(fullColumnsDetailHeirs[i].name) && (!fullColumnsDetailHeirs[i].comment || (fullColumnsDetailHeirs[i].comment && !(fullColumnsDetailHeirs[i].comment).includes('fk')) ) ){
                                        var susunan = "";
                                        susunan += fullColumnsDetailHeirs[i].nullable?"{required}-[":"{optional}-[";
                                        susunan += (fullColumnsDetailHeirs[i].type).replace("\\","")+"]" ;
                                        susunan += (!fullColumnsDetailHeirs[i].comment?"-<data:input>":"-<data:"+fullColumnsDetailHeirs[i].comment+">") ;
                                        detailsPayloadHeirs[fullColumnsDetailHeirs[i].name] = susunan;
                                    }
                                    (arrayDataDetailDetail.details).forEach(sub_subdt=>{
                                        let arrayDataDetailDetailSub = data.find(sub_subdtl=>{
                                            return sub_subdtl.model == sub_subdt;
                                        });
                                        let columnsNestedDetails = arrayDataDetailDetailSub.fullColumns;
                                        let detailsPayloadHeirsOfHeirs = {};
                                        for(let i=0; i<columnsNestedDetails.length;i++){
                                            if( arrayDataDetailDetailSub.config.createable.includes(columnsNestedDetails[i].name) && (!columnsNestedDetails[i].comment || (columnsNestedDetails[i].comment && !(columnsNestedDetails[i].comment).includes('fk'))) ){
                                                var susunanSub = "";
                                                susunanSub += columnsNestedDetails[i].nullable?"{required}-[":"{optional}-[";
                                                susunanSub += (columnsNestedDetails[i].type).replace("\\","")+"]" ;
                                                susunanSub += (!columnsNestedDetails[i].comment?"-<data:input>":"-<data:"+columnsNestedDetails[i].comment+">") ;
                                                detailsPayloadHeirsOfHeirs[columnsNestedDetails[i].name] = susunanSub;
                                            }
                                            detailsPayloadHeirs[ sub_subdt.includes(".") ? sub_subdt.split(".")[1]:sub_subdt ] = [detailsPayloadHeirsOfHeirs];
                                        }
                                    });
                                    detailsPayload[subdt.includes(".") ? subdt.split(".")[1]:subdt] = [detailsPayloadHeirs];
                                }
                            });
                            newForm[dt.includes(".") ? dt.split(".")[1]:dt] = [detailsPayload];
                        }
                    });
                
                    codemirror.setValue(JSON.stringify({
                        url:"/{{config('api.route_prefix')}}/"+(arrayData.model.includes(".")?arrayData.model.split(".")[1]:arrayData.model),
                        method: "POST",
                        body:newForm
                    },null,"\t"));
                    document.getElementById("modelSelected").innerText=arrayData.model;
                    codeMirrorElement.style.transform="translateX(150%)"
                    codeMirrorElement.style.display = "block";
                    setTimeout(function(){
                        codeMirrorElement.style.transform="translateX(0%)"
                    },500)
                    document.getElementById("modelSelected").style.display = "block";
                });
            });
            var classname = document.getElementsByClassName("update");
            Array.from(classname).forEach(function(element) {
                element.addEventListener("click",function(){
                    codemirror.setValue("");
                    let index = element.getAttribute("index");
                    let arrayData = data[index];
                    window.location.hash=`update_${arrayData.model}`
                    var newData = {
                        payloadFields : arrayData.config.createable,
                    };
                    let fullColumns = arrayData.fullColumns.filter(dt=>!['creator_id','editor_id','approver_id'].includes(dt));
                    var newForm = {};
                    for(let i=0; i<fullColumns.length;i++){
                        if( arrayData.config.createable.includes(fullColumns[i].name) ){
                            var susunan = "";
                            susunan += fullColumns[i].nullable?"{required}-[":"{optional}-[";
                            susunan += (fullColumns[i].type).replace("\\","")+"]" ;
                            susunan += (!fullColumns[i].comment?"-<data:input>":"-<data:"+fullColumns[i].comment+">") ;
                            newForm[fullColumns[i].name] = susunan;
                        }
                    }
                    
                    (arrayData.details).forEach(dt=>{
                        let arrayDataDetail = data.find(dtl=>{
                            return dtl.model == dt;
                        });
                        let fullColumnsDetail = arrayDataDetail.fullColumns;
                        let detailsPayload = {};
                        for(let i=0; i<fullColumnsDetail.length;i++){
                            if( arrayDataDetail.config.createable.includes(fullColumnsDetail[i].name)  ){
                                var susunan = "";
                                susunan += fullColumnsDetail[i].nullable?"{required}-[":"{optional}-[";
                                susunan += (fullColumnsDetail[i].type).replace("\\","")+"]" ;
                                susunan += (!fullColumnsDetail[i].comment?"-<data:input>":"-<data:"+fullColumnsDetail[i].comment+">") ;
                                detailsPayload[fullColumnsDetail[i].name] = susunan;
                            }
                            (arrayDataDetail.details).forEach(subdt=>{
                                let arrayDataDetailDetail = data.find(subdtl=>{
                                    return subdtl.model == subdt;
                                });
                                let fullColumnsDetailHeirs = arrayDataDetailDetail.fullColumns;
                                let detailsPayloadHeirs = {};
                                for(let i=0; i<fullColumnsDetailHeirs.length;i++){
                                    if( arrayDataDetailDetail.config.createable.includes(fullColumnsDetailHeirs[i].name) ){
                                        var susunan = "";
                                        susunan += fullColumnsDetailHeirs[i].nullable?"{required}-[":"{optional}-[";
                                        susunan += (fullColumnsDetailHeirs[i].type).replace("\\","")+"]" ;
                                        susunan += (!fullColumnsDetailHeirs[i].comment?"-<data:input>":"-<data:"+fullColumnsDetailHeirs[i].comment+">") ;
                                        detailsPayloadHeirs[fullColumnsDetailHeirs[i].name] = susunan;
                                    }
                                    (arrayDataDetailDetail.details).forEach(sub_subdt=>{
                                        let arrayDataDetailDetailSub = data.find(sub_subdtl=>{
                                            return sub_subdtl.model == sub_subdt;
                                        });
                                        let columnsNestedDetails = arrayDataDetailDetailSub.fullColumns;
                                        let detailsPayloadHeirsOfHeirs = {};
                                        for(let i=0; i<columnsNestedDetails.length;i++){
                                            if( arrayDataDetailDetailSub.config.createable.includes(columnsNestedDetails[i].name) ){
                                                var susunanSub = "";
                                                susunanSub += columnsNestedDetails[i].nullable?"{required}-[":"{optional}-[";
                                                susunanSub += (columnsNestedDetails[i].type).replace("\\","")+"]" ;
                                                susunanSub += (!columnsNestedDetails[i].comment?"-<data:input>":"-<data:"+columnsNestedDetails[i].comment+">") ;
                                                detailsPayloadHeirsOfHeirs[columnsNestedDetails[i].name] = susunanSub;
                                            }
                                            detailsPayloadHeirs[sub_subdt.includes(".") ? sub_subdt.split(".")[1]:sub_subdt] = [detailsPayloadHeirsOfHeirs];
                                        }
                                    });
                                    detailsPayload[subdt.includes(".") ? subdt.split(".")[1]:subdt] = [detailsPayloadHeirs];
                                }
                            });
                            newForm[dt.includes(".") ? dt.split(".")[1]:dt] = [detailsPayload];
                        }
                    });
                
                    codemirror.setValue(JSON.stringify({
                        url:"/{{config('api.route_prefix')}}/"+(arrayData.model.includes(".")?arrayData.model.split(".")[1]:arrayData.model)+"/{id}",
                        method: "PUT",
                        body:newForm
                    },null,"\t"));
                    document.getElementById("modelSelected").innerText=arrayData.model;
                    codeMirrorElement.style.transform="translateX(150%)"
                    codeMirrorElement.style.display = "block";
                    setTimeout(function(){
                        codeMirrorElement.style.transform="translateX(0%)"
                    },500)
                    document.getElementById("modelSelected").style.display = "block";
                });
            });
            var classname = document.getElementsByClassName("notice");
            Array.from(classname).forEach(function(element) {
                element.addEventListener("click",function(){
                    codemirror.setValue("");
                    let index = element.getAttribute("index");
                    let arrayData = data[index];
                    let model = arrayData.model;                        
                    submitApi({
                        url  : "{{url('laradev/getnotice')}}",
                        data : {
                            data:model
                        }
                    },function(response){
                        document.getElementById("modelSelected").innerText=model+" [NOTICE]";
                        codeMirrorElement.style.transform="translateX(150%)"
                        codeMirrorElement.style.display = "block";
                        setTimeout(function(){
                            codeMirrorElement.style.transform="translateX(0%)"
                        })
                        document.getElementById("modelSelected").style.display = "block";
                        codemirror.setValue( (response.data).replace("\t","") );
                    },function(errors){
                        console.log(errors)
                    });
                })
            });
            var first=true
            const onHasChange = ()=>{
                if( window.location.hash && (window.location.hash).includes("btn") ){
                    return
                }
                var classname = document.getElementsByClassName("model-name");
                Array.from(classname).forEach(function(element) {
                    element.style.backgroundColor="white"
                    element.style.color="black"
                })
                document.getElementById('model_login').style.backgroundColor="white"
                document.getElementById('model_login').style.color="black"
                if(window.location.hash){
                    const mode = window.location.hash.split("_")[0];
                    const model = window.location.hash.replace(`${mode}_`,'');
                    const elem = document.getElementById(`model_${model}`)
                    if(!elem) return
                    if(first){
                        const tombol = document.getElementById(window.location.hash.replace("#","btn_"))
                        tombol.click()
                    }
                    elem.style.backgroundColor="green"
                    elem.style.color="white"
                    var classname = document.getElementsByClassName("notice");
                }
                first=false;
            };
            window.onhashchange = onHasChange
            onHasChange()
        </script>
    </body>
</html>