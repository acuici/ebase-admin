import { ref } from 'vue'
import { getSystemSettings } from '../api/settings'

const systemName = ref('EBASE 商业运营后台')

export function useSystemBranding() {
  async function loadBranding(): Promise<void> {
    try {
      const data = await getSystemSettings('company')
      const value = data.settings.system_name
      if (typeof value === 'string' && value.trim()) systemName.value = value
    } catch {
      // The default title remains available during API failures.
    }
  }

  function setSystemName(value: string): void {
    if (value.trim()) systemName.value = value.trim()
  }

  return { systemName, loadBranding, setSystemName }
}
