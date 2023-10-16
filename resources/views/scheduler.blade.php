<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/windicss-runtime-dom"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Scheduler</title>
</head>
<body class="block" hidden>
    @verbatim
    <div id="app">
        <table class="w-screen text-xs p-1" cellspacing="0" border="0.5">
            <tr class="bg-gray-100">
                <th class="p-0.5 border border-gray-700">Action</th>
                <th class="p-0.5 border border-gray-700">Title</th>
                <th class="p-0.5 border border-gray-700">Every</th>
                <th class="p-0.5 border border-gray-700">Every (param)</th>
                <th class="p-0.5 border border-gray-700">Class</th>
                <th class="p-0.5 border border-gray-700">Method</th>
                <th class="p-0.5 border border-gray-700">Days</th>
                <th class="p-0.5 border border-gray-700">Start Time</th>
                <th class="p-0.5 border border-gray-700">End Time</th>
                <th class="p-0.5 border border-gray-700">Note</th>
                <th class="p-0.5 border border-gray-700">Status</th>
                <th class="p-0.5 border border-gray-700">Last Executed</th>
            </tr>
            <tbody>
            <tr v-for="(sch, idx) in data" :key="sch.id+'-data'" :class="{ 'bg-yellow-100': isDirty(sch, sch.__updated) }">
                <td class="p-0.5 border border-gray-700 grid grid-cols-1 p-1 items-center justify-center gap-1"
                 :class="{ '!grid-cols-2': sch.id && isDirty(sch, sch.__updated) }">
                    <i v-show="sch.id" @click="onDelete(sch.id)" title="Remove" class="text-white fa fa-times bg-red-500 p-1 text-center hover:bg-red-600 cursor-pointer select-none"></i>
                    <i @click="onUpdateOrCreate(sch, idx)" v-show="isDirty(sch, sch.__updated)" title="Update" class="text-white fa fa-check bg-yellow-500 p-1 text-center hover:bg-yellow-600 cursor-pointer select-none"></i>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.title=e.target.value" :value="sch.__updated.title||sch.title" class="w-full p-0.5 h-full border-red-500 border outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.every=e.target.value" :value="sch.__updated.every||sch.every" class="w-full p-0.5 h-full border-red-500 border outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.every_param=e.target.value" :value="sch.__updated.every_param||sch.every_param" class="w-full p-0.5 h-full border-none border outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.class_name=e.target.value" :value="sch.__updated.class_name||sch.class_name" class="w-full p-0.5 h-full border-red-500 border outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.func_name=e.target.value" :value="sch.__updated.func_name||sch.func_name" class="w-full p-0.5 h-full border-red-500 border outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.days=e.target.value" :value="sch.__updated.days||sch.days" class="w-full p-0.5 h-full border-none outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.start_at=e.target.value" placeholder="00:00" :value="sch.__updated.start_at||sch.start_at" class="w-full p-0.5 h-full border-none outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.end_at=e.target.value" placeholder="24:00" :value="sch.__updated.end_at||sch.end_at" class="w-full p-0.5 h-full border-none outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.note=e.target.value" :value="sch.__updated.note||sch.note" class="w-full p-0.5 h-full border-none outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input @input="e=>sch.__updated.status=e.target.value" :value="sch.__updated.status||sch.status" class="w-full p-0.5 h-full border-none outline-none bg-transparent focus:bg-white"/>
                </td>
                <td class="border border-gray-700">
                    <input readonly :value="sch.last_executed_at" class="w-full p-0.5 h-full border-none outline-none bg-gray-100"/>
                </td>
            </tr>
            <tr v-show="data.every(dt=>dt.id)">
                <td class="flex w-full items-center justify-center" colspan="12">
                    <i @click="onAdd" title="add" class="p-2 fa-xl text-white fa fa-plus bg-blue-500 hover:bg-blue-600 cursor-pointer select-none"></i>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    @endverbatim
    <script>
        const dataSchedules = @json($schedules);
        const { createApp, ref } = Vue

        createApp({
            setup() {
                const data = ref([...dataSchedules].map(dt=>{
                    dt.__updated = {}
                    return dt;
                }))

                function isDirty( currentVal, newVal ){
                    if ( Object.keys(newVal).length === 0 ) return false;
                    for( const key in newVal ){
                        if( newVal[key]!==currentVal[key] ){
                            return true
                        }
                    }
                    return false
                }

                async function onDelete( id ){
                    let confirmed = await confirm(`Delete selected data [id=${id}]?`, true);
                    if( !confirmed ) return;
                    let res, responseJson;
                    try{
                        res = await fetch(`/docs/scheduler/${id}`,{
                            method: 'DELETE',
                            headers:{
                                'Content-type':'application/json',
                                authorization: "{{config('editor.password')}}"
                            }
                        })
                        responseJson = await res.json()
                        if(!res.ok) {
                            throw Error(responseJson.message||'Failed to delete')
                        }
                    }catch(err){
                        return alert(err)
                    }
                    window.location.reload()
                }

                async function onUpdateOrCreate( newObj, idx ){
                    let res, responseJson;
                    const newData = {...newObj.__updated}
                    for(let key in newData){
                        if(newData[key]==''){
                            delete newData[key]
                        }
                    }
                    try{
                        res = await fetch(`/docs/scheduler${newObj.id?`/${newObj.id}`:''}`,{
                            method: newObj.id?'PUT':'POST',
                            headers:{
                                'Content-type':'application/json',
                                authorization: "{{config('editor.password')}}"
                            },
                            body: JSON.stringify( newData )
                        })
                        
                        responseJson = await res.json()
                        if(!res.ok) {
                            throw Error(responseJson.message||'Failed to '+(newObj.id?'Update':'Create'))
                        }
                    }catch(err){
                        return alert(err.toString().split('DETAIL')[0])
                    }
                    if(!newObj.id){
                        newData.id = responseJson.id
                    }
                    Object.assign(data.value[idx], newData)
                    data.value[idx].__updated = {}
                }

                function onAdd(){
                    data.value.push({
                        __updated:{
                            title:'new',
                            status:'ACTIVE'
                        }
                    })
                }

                return {
                    data, isDirty, onDelete, onUpdateOrCreate, onAdd
                }
            }
        }).mount('#app')
    </script>
</body>
</html>