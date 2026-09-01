<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { CheckCircle2, File, FileImage, FileSpreadsheet, LoaderCircle, RotateCcw, Trash2, UploadCloud } from 'lucide-vue-next'
import { useToast } from '../../composables/useToast'

type UploadState='uploading'|'done'|'error'
interface UploadFile { id:number; name:string; size:number; type:string; progress:number; state:UploadState }
const props=withDefaults(defineProps<{accept?:string;maxSizeMb?:number;multiple?:boolean}>(),{accept:'image/*,.pdf,.xlsx,.xls,.csv',maxSizeMb:20,multiple:true})
const emit=defineEmits<{change:[files:UploadFile[]]}>(); const input=ref<HTMLInputElement|null>(null); const dragging=ref(false); const files=ref<UploadFile[]>([]); const timers=new Map<number,number>(); const {success,error}=useToast(); let id=0
function formatSize(bytes:number){return bytes<1024*1024?`${(bytes/1024).toFixed(1)} KB`:`${(bytes/1024/1024).toFixed(1)} MB`}
function iconFor(file:UploadFile){if(file.type.startsWith('image/'))return FileImage;if(/sheet|excel|csv/.test(file.type)||/\.(xlsx?|csv)$/i.test(file.name))return FileSpreadsheet;return File}
function validate(file:globalThis.File){const allowed=file.type.startsWith('image/')||file.type==='application/pdf'||/\.(xlsx?|csv)$/i.test(file.name);if(!allowed){error('不支持该文件格式',`${file.name} 不是图片、PDF、Excel 或 CSV 文件。`);return false}if(file.size>props.maxSizeMb*1024*1024){error('文件未添加',`${file.name} 超过 ${props.maxSizeMb} MB 大小限制。`);return false}return true}
function add(list:FileList|globalThis.File[]){Array.from(list).filter(validate).forEach(raw=>{const item:UploadFile={id:++id,name:raw.name,size:raw.size,type:raw.type,progress:8,state:'uploading'};files.value.push(item);simulate(item)});emit('change',files.value)}
function simulate(item:UploadFile){const timer=window.setInterval(()=>{item.progress=Math.min(100,item.progress+Math.ceil(Math.random()*18));if(item.progress>=100){item.state='done';window.clearInterval(timer);timers.delete(item.id);success('文件上传完成',item.name);emit('change',files.value)}},180);timers.set(item.id,timer)}
function remove(item:UploadFile){const timer=timers.get(item.id);if(timer)window.clearInterval(timer);timers.delete(item.id);files.value=files.value.filter(file=>file.id!==item.id);emit('change',files.value)}
function retry(item:UploadFile){item.progress=8;item.state='uploading';simulate(item)}
function drop(event:DragEvent){dragging.value=false;if(event.dataTransfer?.files)add(event.dataTransfer.files)}
function choose(event:Event){const target=event.target as HTMLInputElement;if(target.files)add(target.files);target.value=''}
onBeforeUnmount(()=>timers.forEach(timer=>window.clearInterval(timer)))
</script>

<template><div class="file-uploader"><input ref="input" class="visually-hidden" type="file" :accept="accept" :multiple="multiple" @change="choose"/><button class="upload-dropzone" :class="{dragging}" type="button" @click="input?.click()" @dragenter.prevent="dragging=true" @dragover.prevent="dragging=true" @dragleave.prevent="dragging=false" @drop.prevent="drop"><span><UploadCloud :size="20"/></span><div><strong>拖拽文件到这里，或点击选择</strong><small>支持图片、PDF、Excel 和 CSV，单个文件不超过 {{maxSizeMb}} MB</small></div></button><ul v-if="files.length" class="upload-list"><li v-for="item in files" :key="item.id"><span class="upload-file-icon"><component :is="iconFor(item)" :size="18"/></span><div><strong>{{item.name}}</strong><small>{{formatSize(item.size)}} · {{item.state==='done'?'上传完成':item.state==='error'?'上传失败':`正在上传 ${item.progress}%`}}</small><i v-if="item.state==='uploading'"><b :style="{width:`${item.progress}%`}"></b></i></div><CheckCircle2 v-if="item.state==='done'" class="upload-success" :size="17"/><LoaderCircle v-else-if="item.state==='uploading'" class="upload-spinner" :size="17"/><button v-else aria-label="重新上传" @click="retry(item)"><RotateCcw :size="16"/></button><button aria-label="移除文件" @click="remove(item)"><Trash2 :size="16"/></button></li></ul></div></template>
