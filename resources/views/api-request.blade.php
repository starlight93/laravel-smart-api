<html>
    <head>
        <title>API Test</title>
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
                /* background-color: rgb(42, 42, 42);
                color: rgb(131, 246, 73); */
                /* box-shadow: 5px 5px grey; */
            }
            .button{
                background-color: forestgreen;
                color:white;
                margin-left: 5px;
                margin-top:3px;
                border-radius: 5px;
                cursor: pointer;
                display: inline-block;
                /* padding: 15px 32px; */
                text-align: center;
            }
            /* #code{
                width:50%
            } */
            #codemirror{
                right:10px;
                top:50px;
                position:fixed;
                width:50%
            }

            .CodeMirror{
                /* width:98%; */
                font-size: 11px;
                box-shadow: 5px 5px grey;
            }
        </style>
    </head>
    <body>
<input type=hidden id="urlCurrent" value="{{url('/')}}"">
<div id="codemirror">
    <p class="judul" id="judul">REQUEST</p>
    <p class="endpoint" id="url"></p>
    <textarea id="code">
    </textarea>
    </p><a href="javascript:void(0)" class="button" id="run">Run on Console!</a></p>
</div>

<div>
    <p class="title">LOGIN</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/login",
    "method"    : "POST",
    "headers"   :{
    },
    "body"  : {
        "email" : "trial@trial.trial",
        "password" : "trial"
    }
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
</div>

<div>
    <p class="title">GET CURRENT USER</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/user",
    "method"    : "GET",
    "headers"   :{
        "authorization"  : "token setelah login",
        "Cache-Control" : "no-cache"
    },
    "body"  : {}
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
</div>

<div>
    <p class="title">CREATE NORMAL</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/{{config('api.route_prefix')}}/model",
    "method"    : "POST",
    "headers"   :{
        "authorization"  : "token setelah login",
        "Cache-Control" : "no-cache"
    },
    "body": {
		"color": "datakolom1",
		"draft_no": "datakolom2",
		"inv_tra_material_transfer_d_item": [
			{
				"no_pendaftaran": "hal12o",
				"kolomdetail2": "halo",
				"inv_tra_material_transfer_d_item_d_other": [
					{
						"color": "22",
						"kolomsubdetail2": 2
					}
				]
			}
		]
	}
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
</div>

<div>
    <p class="title">CREATE MASS</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/{{config('api.route_prefix')}}/model",
    "method"    : "POST",
    "headers"   :{
        "authorization"  : "token setelah login",
        "Cache-Control" : "no-cache"
    },
    "body": 
        [{
        "kolom1": "datakolom1",
        "kolomh2": "datakolom2",
        "modeldetail_1": [
                {
                "kolomdetail1":"halo",
                "kolomdetail2":"halo",
                "modelsubdetail_1":[
                    {
                    "kolomsubdetail1":1,
                    "kolomsubdetail2":2
                    }  
                ]
                }        
            ],
        "modeldetail2_":[
                {
                "kolomdetail2":"data"
                }
            ]       
     
    }]
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
</div>

<div>
    <p class="title">CREATE DETAILS</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/{{config('api.route_prefix')}}/model/1/modeldetail",
    "method"    : "POST",
    "headers"   :{
        "authorization"  : "token setelah login",
        "Cache-Control" : "no-cache"
    },
    "body": {
      "kolom1": "datakolom1",
      "kolomh2": "datakolom2",
      "modeldetail_1": [
            {
              "kolomdetail1":"halo",
              "kolomdetail2":"halo",
              "modelsubdetail_1":[
                {
                  "kolomsubdetail1":1,
                  "kolomsubdetail2":2
                }  
              ]
            }        
          ],
      "modeldetail2_":[
            {
              "kolomdetail2":"data"
            }
          ]
    }
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
</div>

<div>
    <p class="title">UPDATE</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/{{config('api.route_prefix')}}/model/1",
    "method"    : "PUT",
    "headers"   :{
        "authorization"  : "token setelah login",
        "Cache-Control" : "no-cache"
    },
    "body": {
		"color": "datakolom1",
		"draft_no": "datakolom2",
		"inv_tra_material_transfer_d_item": [
			{
				"no_pendaftaran": "hal12o",
				"kolomdetail2": "halo",
				"inv_tra_material_transfer_d_item_d_other": [
					{
						"color": "22",
						"kolomsubdetail2": 2
					}
				]
			}
		]
	}
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
</div>

<div>
    <p class="title">DELETE</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/{{config('api.route_prefix')}}/model/1",
    "method"    : "DELETE",
    "headers"   :{
        "authorization"  : "token setelah login",
        "Cache-Control" : "no-cache"
    },
    "body": {
    }
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
</div>


<div>
    <p class="title">READ</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/{{config('api.route_prefix')}}/model",
    "method"    : "GET",
    "headers"   :{
        "authorization"  : "token setelah login",
        "Cache-Control" : "no-cache"
    },
    "body": {
        "paginate"    : 25,
        "order_by"     : "id",
        "order_type"   : "asc"
    }
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
</div>

<div>
    <p class="title">CUSTOM FUNC GET</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/custom/model/nama_function",
    "method"    : "GET",
    "headers"   :{
        "authorization"  : "token setelah login",
        "Cache-Control" : "no-cache"
    },
    "body": {
        "params1"    : "ini param",
        "params2"    : "ini param"
    }
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
</div>

<div>
    <p class="title">CUSTOM FUNC POST</p>
    <textarea class="samplecode" readonly>
{
    "url"       : "/custom/model/nama_function",
    "method"    : "POST",
    "headers"   :{
        "authorization"  : "token setelah login",
        "Cache-Control" : "no-cache"
    },
    "body": {
        "params1"    : "ini param",
        "params2"    : "ini param"
    }
}
    </textarea>
    </p><button href="javascript:void(0)">Copy to Editor</button></p>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/autosize.js/3.0.20/autosize.min.js" integrity="sha512-EAEoidLzhKrfVg7qX8xZFEAebhmBMsXrIcI0h7VPx2CyAyFHuDvOAUs9CEATB2Ou2/kuWEDtluEVrQcjXBy9yw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        var submitApi = (data)=>{
            var $options   =
            {
                url         : data.url,
                credentials : true,
                method      : data.method,
                data        : data.body,
                headers     : data.headers
            }
            if(data.method.toLowerCase() == "get"){
                $options["params"] = data.body;
            }
            axios($options).then(response => {
                console.log( response.data );
                if( response.data.access_token ){
                    localStorage.token = response.data.token_type+" "+response.data.access_token;
                }
            }).catch(error => {
                console.log(error.response);
            });
        }

        document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('button').forEach((elem) => {
            elem.addEventListener("click",function(e){
                e.preventDefault();
                try{
                    var x = JSON.parse(elem.parentElement.getElementsByTagName("textarea")[0].value);
                    if(x.headers.authorization !=null && localStorage.token!=undefined){
                        x.headers.authorization = localStorage.token;
                    }
                }catch(e){
                    alert("format JSON salah!\nPastikan semua dikasih petik ganda");
                    console.log(e.message);
                    return false;
                }
                codemirror.setValue(JSON.stringify(x,null,"\t"));
                document.getElementById("judul").innerHTML= elem.parentElement.getElementsByTagName("p")[0].innerText;        
                document.getElementById("url").innerHTML = document.getElementById("urlCurrent").value +x.url;
            });
        });
        autosize(document.querySelectorAll('textarea'));
        document.getElementById("run").addEventListener("click",function(e){e.preventDefault();
            try{
                let value = codemirror.getValue();
                var x = JSON.parse(value);
                x.url = document.getElementById("urlCurrent").value+x.url;
            }catch(e){
                alert("format JSON salah!\nPastikan semua dikasih petik ganda");
                console.log(e.message);
                return false;
            }      
            //   console.log(x);
            console.clear();
            try{
                submitApi(x);
            }catch(e){
                throw(e.message);
            }
        });
        });
        var codemirror = CodeMirror.fromTextArea(document.getElementById("code"), {
            lineNumbers: true,
            mode: "javascript",
            viewportMargin: Infinity,
            theme:"monokai",
            keyMap:"sublime",
            matchBrackets: true,
            continueComments: "Enter",
            lint: true
        });
        setTimeout(function(){
            alert("Buka browser console untuk melihat response.");
        },500)
        
    </script>
    </body>
</html>