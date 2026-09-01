import { readonly, ref } from 'vue'
export type TopbarLayer='store'|'search'|'help'|'notifications'|'account'
const active=ref<TopbarLayer|null>(null)
export function useTopbarLayer(){
  function isOpen(layer:TopbarLayer){return active.value===layer}
  function open(layer:TopbarLayer){active.value=layer}
  function close(layer?:TopbarLayer){if(!layer||active.value===layer)active.value=null}
  function toggle(layer:TopbarLayer){active.value=active.value===layer?null:layer}
  return{active:readonly(active),isOpen,open,close,toggle}
}
