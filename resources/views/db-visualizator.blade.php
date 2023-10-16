<!DOCTYPE html>
<html>
<head>
  <link rel="icon" href="favicon.ico">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="https://unpkg.com/konva@7.0.5/konva.min.js"></script>
    <script src="https://unpkg.com/windicss-runtime-dom"></script>
    <title>Database Models</title>
    <style>
      body {
        background-size: 20px 20px;
        background-image:
          linear-gradient(to right, rgb(204, 201, 201) 1px, transparent 1px),
          linear-gradient(to bottom, rgb(204, 201, 201) 1px, transparent 1px);
        margin: 0;
        padding: 0;
        overflow: hidden;
        background-color: #ffffff;
      }
      ul {  
        list-style-type: none;  
        font-size: 12.3px;  
      }
      li:hover{
        color: blue;
      }
    </style>
</head>
<body class="block" hidden>
    <script>
      var isListHidden = false
      function onListClick(){
        const classList = document.getElementById('list').classList
        if(isListHidden){
          classList.remove('max-w-0')
          classList.add('max-w-1000')
        }else{
          classList.remove('max-w-1000')
          classList.add('max-w-0')
        }
        isListHidden = !isListHidden
      }

      setTimeout(()=>{
          document.getElementById('list').style.display=''
      }, 1000)
    </script>
    <div 
      style="position:fixed;user-select:none;right:0px;top:0px;z-index:91;height: 100vh;overflow-y:auto;display:none;" 
      id="list" class="transition-all duration-400 max-w-1000 border-l bg-white bg-opacity-70 border-opacity-70 hover:bg-opacity-100 border-gray-500">
    </div>
    <div id="container" style="cursor: default;">
      <div class="konvajs-content" role="presentation" id="konvajscontent" style="position: relative; user-select: none; width: 100vh; height: 100vh;">
        <canvas id="konvacanvas" width="100vh" height="100vh" style="padding: 0px; margin: 0px; border: 0px; background: white; position: absolute; top: 0px; left: 0px; width: 1366px; height: 667px; display: block;">
        </canvas>
      </div>
    </div>
    <button onclick="onListClick()" class="rounded-full fixed z-100 bottom-1 right-1 bg-blue-500 hover:bg-blue-600 text-white w-8 h-8 text-sm cursor-pointer">
      DB
    </button>
<script>
  var terpilih;
  var newScale=1;
  function downloadURI(uri, name) {
    var link = document.createElement('a');
    link.download = name;
    link.href = uri;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    delete link;
  }
  var sembunyi=false;
  var gridSembunyi=false;
  window.addEventListener('keydown', function(event){
    if(event.altKey===true && event.key=='Enter'){
        document.getElementById('list').style.display=sembunyi?'block':'none';
        sembunyi=!sembunyi;
    };
    if(event.altKey===true && ['s','S'].includes(event.key) ){
      var ratio = prompt("info: 1 pxRatio = 500x500, masukkan JUDUL:ratio", "database.png:2");
      if (ratio == null || ratio == "" || !ratio.includes(':') ) {
      }else{
        let ratioArray = ratio.split(':');
        if(ratioArray.length!=2){
          alert('format judul dan ratio salah');
          return true;
        }
        let dataURL = stage.toDataURL({ pixelRatio: ratioArray[1] });
        downloadURI(dataURL, ratioArray[0]);                              
      }
    }
    if(event.altKey===true && ['a','A'].includes(event.key) ){
      document.body.style.backgroundSize=gridSembunyi?document.body.style.backgroundSize=`${20*newScale}px ${20*newScale}px`:`0px`;
      gridSembunyi=!gridSembunyi;
    }
    if(event.key=='Delete'){
      try{
        document.getElementById(terpilih.getId()).checked=false;;
        terpilih.destroy();
      }catch(e){}
      stage.find('Transformer').destroy();
      layer.draw();
    }

  });
var json = null
//===========================================WAJIB
var stage = new Konva.Stage({
    container: 'container',
    width: window.innerWidth,
    height: window.innerHeight,
    draggable:true
});
var layer = new Konva.Layer();
//=========================================GROUPING

var tableX  = 20;
var tableY  = 50;
var headerHeight    = 30;
var belongs = [];
var childs = [];
var referencings = [];
function randomValue(max) {
  return Math.floor(Math.random() * (max + 1));
}

// Fungsi untuk menghasilkan kode warna hex dengan komponen warna gelap
function randomDarkHexColor() {
  const maxDarkValue = 210; // Maksimum nilai untuk komponen warna gelap
  const red = randomValue(maxDarkValue).toString(16).padStart(2, '0');
  const green = randomValue(maxDarkValue).toString(16).padStart(2, '0');
  const blue = randomValue(maxDarkValue).toString(16).padStart(2, '0');
  return `#${red}${green}${blue}`;
}

function createTable(data, isReferenced=false, caller=null){
  data['table'] = data['model'];
  let lebar = data.table.length*5.4;
  if( stage.find("#"+data.table).length >0 ){
    return false;
  }
    var Table = new Konva.Group({
        x: tableX,
        y: tableY,
        draggable: true,
        name:"table",
        id: data.table
    });
// kolom
    var bodyTable = new Konva.Group({
        y: headerHeight,
        // draggable: true,
        name:"bodyTable"
    });

    var col1 = new Konva.Group({
        name:"col1"
    });
    var longText = 0;
    var lastY=0;
//===============================================PRIMARYKEY
    if(lastY ==0){
        lastY = col1.getPosition().y;
    }
    var columnText = new Konva.Text({
        y: lastY,
        fontSize: 10,
        fontFamily: 'Arial',
        fontStyle:"bold", 
        text: "id",
        fill: "gray",
        padding: 8,
        align:"right",
        name:"kolom",
        id: data.table+"_id"
    });
//==========================================================
    col1.add(columnText);
    lastY += (columnText.height()-10);
    if(longText<columnText.width()){
        longText=columnText.width();
    }
    data['columns'] = data.fullColumns;
    for(let i=1; i<data.columns.length;i++){
        let comment={};let required=true;
        if(data.columns[i].comment){
          comment = JSON.parse(data.columns[i].comment);
        }
        if(comment['src']!==undefined){
          let srcArray = comment['src'].split(".");
          data.columns[i].table_reference = srcArray.length==2?srcArray[0]:srcArray[0]+"."+srcArray[1];
          data.columns[i].column_reference =  srcArray.length==2?srcArray[1]:srcArray[2];
          data.columns[i].connection = 'SRC';
        }
        if(comment['fk']!==undefined && comment['fk']!="false"){
          let fkArray = comment['fk'].split(".");
          data.columns[i].table_reference =  fkArray.length==2? fkArray[0]:fkArray[0]+"."+fkArray[1];
          data.columns[i].column_reference =  fkArray.length==2? fkArray[1]:fkArray[2];
          data.columns[i].connection = 'FK';
        }
        if( data.columns[i].nullable===false ) {required=false;}
        if(comment['required']!==undefined && comment['required']=="false"){
          required=false;
        }
        if(lastY ==0){
            lastY = col1.getPosition().y;
        }
//=============================================================NAMA KOLOM
        var columnText = new Konva.Text({
            y: lastY,
            fontSize: 11,
            fontFamily: 'Arial',
            text: data.columns[i].name,
            // fontStyle:"bold",
            // textDecoration:"underline",
            fill: 'black',//data.config.createable.includes(data.columns[i].name)?'black':'black',
            padding: 8,
            align:"right",
            name: (data.columns[i].table_reference!==undefined)?"kolomForeign":"kolom",
            id:   (data.columns[i].table_reference!==undefined)? data.columns[i].table_reference+"_"+data.table+"_"+data.columns[i].name 
                : data.table+"_"+data.columns[i].name
        });
        if(data.columns[i].table_reference!==undefined){
            // referencings.push({table_reference:data.columns[i].table_reference, column_reference:data.columns[i].column_reference,table_caller:data.table, id:data.columns[i].table_reference+"_"+data.table+"_"+data.columns[i].name});
            belongs.push( { color: randomDarkHexColor() ,
                id: data.columns[i].table_reference+"_"+data.table+"_"+data.columns[i].name, parent: data.columns[i].table_reference, position:i+1, 
                yAxis: lastY+(columnText.height()+10)/2
            });
            childs[data.columns[i].table_reference]=childs[data.columns[i].table_reference]===undefined?[]:childs[data.columns[i].table_reference];
            childs[data.columns[i].table_reference].push({id: data.columns[i].table_reference+"_"+data.table+"_"+data.columns[i].name, 
                parent:data.columns[i].table_reference, child: data.table, position:i+1}) ;
        }
        col1.add(columnText);

        var reqText = new Konva.Text({
            y: lastY,
            x: columnText.width()-15.5,
            fontSize: 10,
            fontFamily: 'Arial',
            fontStyle:"bold", 
            text: (data.config.required.includes(data.columns[i].name)?"*":""), // reqq
            fill: "red",
            padding: 8,
            align:"right",
            name:"req_attr"
        })

        col1.add(reqText);

        lastY += (columnText.height()-10);
        if(longText<(columnText.width()+2)){
            longText=(columnText.width()+2);
        }

    }
    var temp = longText;

    var col2 = new Konva.Group({
        x: col1.getPosition().x+temp+10,
        name:"col2"
    });

    longText=0;
    lastY=0;
  // ============================================tulisan PK
    if(lastY ==0){
          lastY = col2.getPosition().y;
      }
      var columnText = new Konva.Text({
          // x: col1.getPosition().x+temp,
          y: lastY,
          fontSize: 11,
          fontFamily: 'Courier New',
          text: "BigInt [PK]",
          fontStyle: 'bold',
          // fill: "red",
          // fill: 'black',
          padding: 8,
          align:"right",
          name:"kolom2"
      });
      col2.add(columnText);
      lastY += (columnText.height()-10);
      if(longText<columnText.width()){
          longText=columnText.width();
      }
  // ===============================================datatype
    for(let i=1; i<data.columns.length;i++){
        if(lastY ==0){
            lastY = col2.getPosition().y;
        }
        
        let dType = (data.columns[i].type).replace('\\',"");
        if( [ 'decimal','float','numeric','number','double' ].includes(dType.trim().toLowerCase()) ){
          dType += (data.columns[i].precision?`,${data.columns[i].precision}`:'')
                +(data.columns[i].scale?`,${data.columns[i].scale}`:'')
        }else if( !dType.trim().toLowerCase().includes('date') ){
          dType += (data.columns[i].length?`,${data.columns[i].length}`:'')
        }

        let color = '#6b6860';//data.config.createable.includes(data.columns[i].name)?'gray':'gray';
        // if(){
        //   color ='#c20606';
        // }
        var columnText = new Konva.Text({
            // x: col1.getPosition().x+temp,
            y: lastY,
            fontSize: 11,
            fontStyle: 'italic',
            fontFamily: 'Courier New',
            text: dType+" "+(data.columns[i].table_reference!==undefined?"["+data.columns[i].connection+"]":""),
            color: '#01040a',
            fill: (data.columns[i].table_reference!==undefined?"orange":color),
            padding: 8,
            align:"right",
            name:"kolom2"
        });
        if(data.columns[i].table_reference){
          columnText.on('mouseover', function (e) {
            stage.container().style.cursor = 'pointer';
            if(tercheck.includes(data.columns[i].table_reference)){
                return
            }
            e.currentTarget.setText( data.columns[i].table_reference )
            // e.currentTarget.setFill("#01040a")
            // e.currentTarget.setFontStyle("bold")
            e.currentTarget.setFontSize(12);
            layer.draw()
          })
          columnText.on('mouseout', function (e) {
            stage.container().style.cursor = 'default';
            e.currentTarget.setFontSize(11);
            e.currentTarget.setFill((data.columns[i].table_reference?"orange":color))
            e.currentTarget.setText( (data.columns[i].type).replace('\\',"")+" "+(data.columns[i].table_reference?"["+data.columns[i].connection+"]":"") )
            layer.draw()
          })
          columnText.on('click tap', function (e) {
              document.getElementById(data.columns[i].table_reference).click();
          })
        }
        col2.add(columnText);
        lastY += (columnText.height()-10);
        if(longText<columnText.width()){
            longText=columnText.width();
        }
    }
    bodyTable.add(col1).add(col2);

// jugacolumns
    var headerText = new Konva.Text({
        fontSize: 11,
        // width: lebar,
        fill:"white",
        height: headerHeight ,
        fontFamily: 'Arial',
        text: data.table,
        wrap:"char",
        // fill: 'black',
        lineHeight:2,
        padding: 4,
        align: 'center'
    });
    var color ="#0b79b5";
    lebar = lebar<(temp+longText+5)?(temp+longText+5):lebar;
    var headerRect = new Konva.Rect({
        stroke: '#555',
        // strokeWidth: 5,
        fill: color,
        width: lebar+5 ,
        height: headerText.height() ,
        // shadowColor: 'black',
        // shadowBlur: 10,
        // shadowOffset: [10, 10],
        // shadowOpacity: 0.2,
        name:"headerRect"
        // cornerRadius: 10
    });

    var bodyRect = new Konva.Rect({
        // x:bodyText.getPosition().x,
        y: bodyTable.getPosition().y ,
        stroke: '#555',
        // strokeWidth: 5,
        fill: "#ffff",
        width: lebar+5 ,
        height: lastY+6,
        // shadowColor: 'black',
        // shadowBlur: 10,
        // shadowOffset: [10, 10],
        // shadowOpacity: 1,
        name: "bodyTable"
        // cornerRadius: 10
    });
    //=========================================

    Table.add(headerRect);
    Table.add(headerText);
    Table.add(bodyRect);
    // Table.add(bodyText);
    Table.add(bodyTable);
    layer.add(Table);
    stage.draw();

    tableX = tableX + stage.find(".bodyTable")[ stage.find(".bodyTable").length-1 ].width()+80;
}

stage.add(layer);
let tercheck = [];

function jsonGet(json){
  json = json;
  var doc = document.getElementById("list");
  let list = "";
  json.sort((a, b) => a.model.localeCompare(b.model));
  json.forEach(function(dt){
    list+=`<li><input type='checkbox'  style='cursor:pointer' class='tables' id='${dt.model}'><label style='cursor:pointer' for='${dt.model}'>${dt.model}</label></li>`;
  });
  doc.innerHTML=`<ul class="pr-2 pl-1">${list}</ul>`;
  
  Array.from(document.getElementsByClassName("tables")).forEach(function(table){
    table.addEventListener("change",function(e){
      let el = e.target.getAttribute("id");
      let checked = localStorage.checked!==undefined?JSON.parse(localStorage.checked):[];
      if(e.target.checked){
        if(!checked.includes(el)){
          checked.push(el);
          tercheck.push(el);
          localStorage.checked=JSON.stringify(checked);
        }
        createTable(json.find(dt=>dt.model==el));
        doactionPerEl(stage.find("#"+el));
      }else{
        checked = checked.filter(dt=>dt!==el);
        tercheck = checked;
        localStorage.checked=JSON.stringify(checked);
        let tbl = stage.find("#"+el);
        let keys = [];
        tbl[0].find(".kolomForeign").forEach(function(elem){
          keys.push( "connector_"+elem.getId());
        });
        let fks = stage.find(".connectors");
        fks.forEach(function(connector){
          if(connector.getId().includes(el) || keys.includes(connector.getId()) ){
            stage.find('#'+ (connector.getId().replace("connector_","dot_")) ).destroy();
            connector.destroy();
          };
        });
        tbl.destroy();
        layer.draw();
        tableX = tableX -80;
      };
    });
  });
  let checkeds = localStorage.checked!==undefined?JSON.parse(localStorage.checked):[];
  checkeds.forEach(function(tab){
    if(!tercheck.includes(tab)){
      tercheck.push(tab)
      document.getElementById(tab)?.click();
    }
  });
}
    
  stage.find(".table").on('mouseenter', function() {
    stage.container().style.cursor = 'move';
  });

  stage.find(".table").on('mouseleave', function() {
    stage.container().style.cursor = 'default';
  });

  stage.on('mousedown', function() {
    stage.container().style.cursor = 'move';
  });

  stage.on('mouseup', function() {
    stage.container().style.cursor = 'default';
  });


  var scaleBy = 1.09;
  stage.on('wheel', e => {
    e.evt.preventDefault();
    var oldScale = stage.scaleX();
    var mousePointTo = {
      x: stage.getPointerPosition().x / oldScale - stage.x() / oldScale,
      y: stage.getPointerPosition().y / oldScale - stage.y() / oldScale
    };

    newScale =
      e.evt.deltaY > 0 ? oldScale * scaleBy : oldScale / scaleBy;
    stage.scale({ x: newScale, y: newScale });
    if(!gridSembunyi){
      document.body.style.backgroundSize=`${20*newScale}px ${20*newScale}px`;
    }
    var newPos = {
      x:
        -(mousePointTo.x - stage.getPointerPosition().x / newScale) *
        newScale,
      y:
        -(mousePointTo.y - stage.getPointerPosition().y / newScale) *
        newScale
    };
    stage.position(newPos);
    stage.batchDraw();
  });


  stage.on('click tap', function(e) {
    if (e.target === stage) {
      stage.find('Transformer').destroy();
      layer.draw();
      return;
    }
    
    if (!e.target.parent.hasName('table')) {
      return;
    }
    terpilih = e.target.parent;
    stage.find('Transformer').destroy();

    var tr = new Konva.Transformer();
    layer.add(tr);
    tr.attachTo(e.target.parent);
    layer.draw();
  });      

  function drawForeign(keyForeign, parent){
    var index = parent.find(".col1")[0].find("#"+keyForeign)[0].index;
    for(let i=0; i<belongs.length;i++){

      if(belongs[i].id == keyForeign){

        var moveX = parent.getPosition().x;
        var moveY = parent.getPosition().y;
        if(stage.find("#"+belongs[i].parent)[0]===undefined){continue;}
        var dstX = stage.find("#"+belongs[i].parent)[0].getPosition().x;
        var dstY = stage.find("#"+belongs[i].parent)[0].getPosition().y + stage.find("#"+belongs[i].parent)[0].find(".headerRect")[0].height() + (stage.find("#"+belongs[i].parent)[0].find(".bodyTable")[0].height()/(stage.find("#"+belongs[i].parent)[0].find(".col1")[0].find("Text").length))*1- (stage.find("#"+belongs[i].parent)[0].find(".bodyTable")[0].height()/(stage.find("#"+belongs[i].parent)[0].find(".col1")[0].find("Text").length))/2 ;

        var FixedX;
        
        let parentHeightY = parent.find(".col1")[0].find("#"+keyForeign)[0].getPosition().y+(parent.find(".col1")[0].find("#"+keyForeign)[0].getHeight()/2);
        
        if (moveX<dstX){
            fixedX = moveX + parent.find(".headerRect")[0].width();
            fixedY = moveY + parentHeightY +parent.find(".headerRect")[0].height();
        }else{
            dstX = dstX+stage.find("#"+belongs[i].parent)[0].find(".headerRect")[0].width();
            fixedX = moveX ;
            fixedY = moveY + parentHeightY +parent.find(".headerRect")[0].height();
        }

        dstY+=8;
        // fixedY+=10;

          stage.find('#dot_'+keyForeign).destroy();
          var circle = new Konva.Circle({
            x:fixedX-moveX,
            y:parentHeightY,
            radius: 1,
            fill: belongs[i].color,
            stroke: belongs[i].color,
            strokeWidth: 5,
            id:"dot_"+keyForeign,
            name:"foreign_dots"
          });
          parent.find("Group")[0].add(circle);

        stage.find('#connector_'+keyForeign).destroy();
        var line = new Konva.Arrow(
        {
            points: [ fixedX, fixedY,
                      (fixedX+dstX)/2, fixedY, 
                      (dstX+fixedX)/2, dstY,               
                      dstX, dstY],
            stroke: belongs[i].color,
            fill:belongs[i].color,
            id:"connector_"+keyForeign,
          //   tension: ,
            pointerLength : 5,
            pointerWidth : 5,
            name:"connectors",
        });
        layer.add(line);
        layer.batchDraw();

        break;

      }
    }
  }

  function doactionPerEl(el){
    el.on("dragmove xChange yChange scaleYChange scaleXChange", function(e){
        var src;
        if(e.type=='dragmove'){
          src = e.target;
        }else{
          src=e.currentTarget;
        }
        
        for(let i=0;i<src.find(".kolomForeign").length;i++){
          let kol_id  = src.find(".kolomForeign")[i].getId();
          drawForeign( kol_id , src );
        }
        if( childs[src.getId()] !== undefined ){
            for(let i=0; i<childs[src.getId()].length;i++){
            if(src.getId() == childs[src.getId()][i].parent){
                var parent = stage.find( "#"+childs[src.getId()][i].child)[0] ;
                if(parent!=undefined){
                    drawForeign(childs[src.getId()][i].id , stage.find( "#"+childs[src.getId()][i].child)[0] );
                }
            }
            
            }
        };
        stage.find(".connectors").zIndex(0);
      });
  }

  function doaction(){
    stage.find(".table").on("dragmove xChange yChange scaleYChange scaleXChange", function(e){
        var src;
        if(e.type=='dragmove'){
          src = e.target;
        }else{
          src=e.currentTarget;
        }
        
        for(let i=0;i<src.find(".kolomForeign").length;i++){
          let kol_id  = src.find(".kolomForeign")[i].getId();
          drawForeign( kol_id , src );
        }
        if( childs[src.getId()] !== undefined ){
            for(let i=0; i<childs[src.getId()].length;i++){
            if(src.getId() == childs[src.getId()][i].parent){
                var parent = stage.find( "#"+childs[src.getId()][i].child)[0] ;
                if(parent!=undefined){
                    drawForeign(childs[src.getId()][i].id , stage.find( "#"+childs[src.getId()][i].child)[0] );
                }
            }
            
            }
        };
        stage.find(".connectors").zIndex(0);
      });
  }

  const schemaArr = @json($schema);
  jsonGet(schemaArr)

</script>
  
</body>
</html>