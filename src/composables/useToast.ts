import { readonly, ref } from 'vue'

export type ToastTone = 'success' | 'error' | 'warning' | 'info'
export interface ToastItem { id:number; tone:ToastTone; title:string; description?:string; duration:number }

const items = ref<ToastItem[]>([])
let toastId = 0

function dismiss(id:number){items.value=items.value.filter(item=>item.id!==id)}
function show(tone:ToastTone,title:string,description?:string,duration=3200){
  const id=++toastId
  items.value=[...items.value,{id,tone,title,description,duration}].slice(-4)
  window.setTimeout(()=>dismiss(id),duration)
  return id
}

export function useToast(){
  return {
    items:readonly(items), dismiss,
    success:(title:string,description?:string)=>show('success',title,description),
    error:(title:string,description?:string)=>show('error',title,description,4800),
    warning:(title:string,description?:string)=>show('warning',title,description,4200),
    info:(title:string,description?:string)=>show('info',title,description),
  }
}
