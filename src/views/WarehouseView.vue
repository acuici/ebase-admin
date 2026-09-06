<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { ArrowLeft, Check, Eye, Pencil, Plus, Search, Trash2, X } from "lucide-vue-next";
import { useRouter } from "vue-router";
import TableState from "../components/common/TableState.vue";
import ToolbarSelect from "../components/common/ToolbarSelect.vue";
import { ApiError } from "../api/client";
import {
  createWarehouse,
  deleteWarehouse,
  handoverWarehouse,
  listWarehouseHandovers,
  listWarehouseMembers,
  listWarehouses,
  restoreWarehouse,
  updateWarehouse,
  type Warehouse,
  type WarehouseMember,
} from "../api/warehouses";

const router = useRouter();
const items = ref<Warehouse[]>([]);
const members = ref<WarehouseMember[]>([]);
const handovers = ref<Array<Record<string, unknown>>>([]);
const loading = ref(false);
const errorMessage = ref("");
const toast = ref("");
const keyword = ref("");
const status = ref("");
const ownerCode = ref("");
const city = ref("");
const page = ref(1);
const pageSize = ref(20);
const total = ref(0);
const pages = ref(1);
const selected = ref<Warehouse | null>(null);
const drawer = ref(false);
const handoverDrawer = ref(false);
const mode = ref<"create" | "edit" | "view">("view");
const formError = ref("");
const handoverError = ref("");
const form = reactive<Record<string, string | number>>({
  warehouse_code: "",
  name: "",
  owner_code: "",
  city: "",
  capacity_rate: "",
  inbound_quantity: 0,
  outbound_quantity: 0,
  status: "active",
});
const handoverForm = reactive({
  to_owner_code: "",
  handover_type: "temporary" as "temporary" | "permanent",
  reason: "",
});

const activeMembers = computed(() => members.value.filter((member) => member.member_code));
const ownerOptions = computed(() => [{ label: "全部负责人", value: "" }, ...activeMembers.value.map(member => ({ label: member.name, value: member.member_code }))]);
const hasFilters = computed(() => Boolean(keyword.value || status.value || ownerCode.value || city.value));
const visiblePages = computed(() => Array.from(new Set([1, page.value - 1, page.value, page.value + 1, pages.value])).filter(value => value >= 1 && value <= pages.value).sort((a, b) => a - b).flatMap((value, index, values): (number | string)[] => index && value - values[index - 1]! > 1 ? [`gap-${value}`, value] : [value]));
function clearFilters() { keyword.value = ""; status.value = ""; ownerCode.value = ""; city.value = ""; }
function changePageSize(value: string) { pageSize.value = Number(value); page.value = 1; void load(); }
const handoverMembers = computed(() => activeMembers.value.filter((member) => member.member_code !== selected.value?.owner_code));
const drawerTitle = computed(() => {
  if (mode.value === "create") return "新增仓库";
  if (mode.value === "edit") return "编辑仓库";
  return "仓库详情";
});
const statusOptions = [
  { label: "全部状态", value: "" },
  { label: "启用", value: "active" },
  { label: "已停用", value: "disabled" },
];

function notify(message: string) {
  toast.value = message;
  window.setTimeout(() => {
    toast.value = "";
  }, 2200);
}

function memberName(code?: string | null) {
  if (!code) return "未分配";
  return members.value.find((member) => member.member_code === code)?.name || code;
}

function statusLabel(value: string) {
  return value === "active" ? "启用" : "已停用";
}

function rateLabel(value: number | string | null) {
  if (value === null || value === "") return "—";
  return `${value}%`;
}

async function load() {
  loading.value = true;
  errorMessage.value = "";
  try {
    const result = await listWarehouses({
      page: page.value,
      page_size: pageSize.value,
      keyword: keyword.value,
      status: status.value,
      owner_code: ownerCode.value,
      city: city.value,
      sort_by: "id",
      sort_order: "desc",
    });
    items.value = result.items;
    total.value = result.pagination.total;
    pages.value = Math.max(1, result.pagination.pages);
    if (selected.value) {
      selected.value = items.value.find((item) => item.id === selected.value?.id) || null;
    }
  } catch (error) {
    errorMessage.value = error instanceof ApiError ? error.body.message : "仓库数据加载失败";
    items.value = [];
  } finally {
    loading.value = false;
  }
}

async function loadMembers() {
  try {
    const result = await listWarehouseMembers();
    members.value = result.items;
  } catch {
    members.value = [];
  }
}

async function openDetails(item: Warehouse) {
  selected.value = item;
  try {
    const result = await listWarehouseHandovers(item.id);
    handovers.value = result.items;
  } catch {
    handovers.value = [];
  }
}

function resetForm() {
  form.warehouse_code = "NEW-" + Date.now().toString().slice(-6);
  form.name = "";
  form.owner_code = "";
  form.city = "";
  form.capacity_rate = "";
  form.inbound_quantity = 0;
  form.outbound_quantity = 0;
  form.status = "active";
  formError.value = "";
}

function openCreate() {
  mode.value = "create";
  selected.value = null;
  resetForm();
  drawer.value = true;
}

function openEdit(item: Warehouse) {
  mode.value = "edit";
  selected.value = item;
  formError.value = "";
  Object.assign(form, {
    warehouse_code: item.warehouse_code,
    name: item.name,
    owner_code: item.owner_code || "",
    city: item.city || "",
    capacity_rate: item.capacity_rate ?? "",
    inbound_quantity: item.inbound_quantity,
    outbound_quantity: item.outbound_quantity,
    status: item.status,
  });
  drawer.value = true;
}

function openView(item: Warehouse) {
  mode.value = "view";
  void openDetails(item);
  drawer.value = true;
}

function startEdit() {
  if (selected.value) openEdit(selected.value);
}

async function save() {
  if (!String(form.warehouse_code).trim() || !String(form.name).trim()) {
    formError.value = "仓库编码和仓库名称为必填项";
    return;
  }
  formError.value = "";
  const payload = {
    warehouse_code: String(form.warehouse_code).trim(),
    name: String(form.name).trim(),
    owner_code: String(form.owner_code || "") || null,
    city: String(form.city || "") || null,
    capacity_rate: form.capacity_rate === "" ? null : Number(form.capacity_rate),
    inbound_quantity: Number(form.inbound_quantity || 0),
    outbound_quantity: Number(form.outbound_quantity || 0),
    status: String(form.status),
  };
  try {
    if (mode.value === "create") {
      const created = await createWarehouse(payload);
      selected.value = created;
      notify("仓库已创建");
    } else if (selected.value) {
      const updated = await updateWarehouse(selected.value.id, payload);
      selected.value = updated;
      notify("仓库已更新");
    }
    drawer.value = false;
    await load();
  } catch (error) {
    formError.value = error instanceof ApiError ? error.body.message : "保存失败，请稍后重试";
  }
}

async function remove(item: Warehouse) {
  if (!window.confirm(`确认停用仓库“${item.name}”吗？仓库历史和库存关联会保留。`)) return;
  try {
    await deleteWarehouse(item.id);
    if (selected.value?.id === item.id) {
      selected.value = null;
      drawer.value = false;
    }
    notify("仓库已停用");
    await load();
  } catch (error) {
    notify(error instanceof ApiError ? error.body.message : "删除失败");
  }
}

function openHandover(item: Warehouse) {
  selected.value = item;
  handoverError.value = "";
  handoverForm.to_owner_code = activeMembers.value.find((member) => member.member_code !== item.owner_code)?.member_code || "";
  handoverForm.handover_type = "temporary";
  handoverForm.reason = "";
  handoverDrawer.value = true;
}

async function submitHandover() {
  if (!selected.value || !handoverForm.to_owner_code) {
    handoverError.value = "请选择接管成员";
    return;
  }
  try {
    const result = await handoverWarehouse(selected.value.id, handoverForm);
    selected.value = result;
    handoverDrawer.value = false;
    notify("负责人交接已生效");
    await load();
    await openDetails(result);
  } catch (error) {
    handoverError.value = error instanceof ApiError ? error.body.message : "交接失败，请稍后重试";
  }
}

async function restore(item: Warehouse) {
  try {
    const result = await restoreWarehouse(item.id);
    selected.value = result;
    notify("临时交接已恢复");
    await load();
    await openDetails(result);
  } catch (error) {
    notify(error instanceof ApiError ? error.body.message : "恢复交接失败");
  }
}

function changePage(target: number) {
  if (target < 1 || target > pages.value || target === page.value) return;
  page.value = target;
  void load();
}

watch([keyword, status, ownerCode, city], () => {
  page.value = 1;
  void load();
});

onMounted(() => {
  void Promise.all([load(), loadMembers()]);
});
</script>

<template>
  <section class="secondary-page warehouse-page">
    <RouterLink to="/features" class="secondary-back"><ArrowLeft :size="16" />返回功能地图</RouterLink>
    <div class="page-heading">
      <div>
        <span class="eyebrow">SUPPLY CHAIN · 供应链</span>
        <h1>仓库管理</h1>
        <p>管理仓库、查看库存与出入库情况，安排负责人及岗位交接。</p>
      </div>
      <button class="button primary" @click="openCreate"><Plus :size="16" />新增仓库</button>
    </div>

    <article class="surface secondary-table">
      <div class="module-toolbar warehouse-toolbar">
        <label class="module-search"><Search :size="16" /><input v-model="keyword" name="keyword" aria-label="搜索仓库" placeholder="搜索仓库编码、名称或负责人" /></label>
        <div class="warehouse-filters">
        <ToolbarSelect v-model="status" label="状态" :options="statusOptions" aria-label="仓库状态" />
        <ToolbarSelect v-model="ownerCode" :options="ownerOptions" aria-label="负责人" />
        <label class="module-search warehouse-city"><span>城市</span><input v-model="city" name="city" aria-label="所在城市" placeholder="输入城市" /></label>
        <button v-if="hasFilters" class="button secondary" @click="clearFilters">重置</button>
        </div>
      </div>
      <TableState v-if="loading" state="loading" title="正在加载仓库" description="正在同步仓库与库存信息。" />
      <TableState v-else-if="errorMessage" state="error" title="仓库加载失败" :description="errorMessage"><template #action><button class="button secondary" @click="load">重新加载</button></template></TableState>
      <TableState v-else-if="!items.length" state="empty" :filtered="hasFilters" :title="hasFilters ? '没有找到匹配的仓库' : '暂无仓库记录'" :description="hasFilters ? '试试其他关键词，或清除筛选条件后重新查看。' : '新增第一个仓库，开始管理库存与负责人。'"><template #action><button v-if="hasFilters" class="button secondary" @click="clearFilters">清除筛选</button><button v-else class="button secondary" @click="openCreate"><Plus :size="16" />新增仓库</button></template></TableState>
      <div v-else class="table-scroll">
        <table>
          <thead><tr><th>仓库编码</th><th>仓库名称</th><th>负责人</th><th>城市</th><th>库容率</th><th>SKU 数</th><th>可用库存</th><th>入库 / 出库</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
            <tr v-for="item in items" :key="item.id" :class="{ selected: selected?.id === item.id }">
              <td class="mono">{{ item.warehouse_code }}</td>
              <td class="strong-cell">{{ item.name }}</td>
              <td>{{ item.owner_name || "未分配" }}</td>
              <td>{{ item.city || "—" }}</td>
              <td>{{ rateLabel(item.capacity_rate) }}</td>
              <td>{{ item.sku_count ?? 0 }}</td>
              <td>{{ item.available_stock ?? 0 }}</td>
              <td>{{ item.inbound_quantity }} / {{ item.outbound_quantity }}</td>
              <td><span class="status-tag" :data-status="item.status">{{ statusLabel(item.status) }}</span></td>
              <td class="crud-actions"><button title="查看" @click="openView(item)"><Eye :size="14" /></button><button title="编辑" @click="openEdit(item)"><Pencil :size="14" /></button><button title="交接" @click="openHandover(item)">交接</button><button class="danger" title="停用" @click="remove(item)"><Trash2 :size="14" /></button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <footer class="table-footer warehouse-footer"><span>{{ loading ? '正在加载…' : `共 ${total.toLocaleString()} 条记录` }}</span><div v-if="total > 0" class="pagination" role="navigation" aria-label="仓库列表分页"><div class="pagination-meta"><div class="pagination-size"><span>每页</span><ToolbarSelect class="pagination-size-select" :model-value="String(pageSize)" :options="[20, 50, 100].map(value => ({ label: `${value} 条`, value: String(value) }))" aria-label="每页显示数量" @update:model-value="changePageSize" /></div><span class="pagination-summary">第 {{ page }} / {{ pages }} 页</span></div><div class="pagination-pages"><button :disabled="loading || page === 1" @click="changePage(page - 1)">上一页</button><button v-for="target in visiblePages" :key="target" :disabled="loading || typeof target === 'string'" :class="{ active: page === target, ellipsis: typeof target === 'string' }" :aria-current="page === target ? 'page' : undefined" @click="typeof target === 'number' && changePage(target)">{{ typeof target === 'number' ? target : '…' }}</button><button :disabled="loading || page === pages" @click="changePage(page + 1)">下一页</button></div></div></footer>
    </article>

    <Teleport to="body">
      <div v-if="drawer" class="crud-overlay" @click.self="drawer = false">
        <aside class="crud-drawer">
          <header><div><span>{{ mode.toUpperCase() }}</span><h2>{{ drawerTitle }}</h2><p>仓库与负责人真实数据</p></div><button @click="drawer = false"><X :size="18" /></button></header>
          <div class="crud-form">
            <div v-if="formError" class="crud-error">{{ formError }}</div>
            <template v-if="mode !== 'view'">
              <label><span>仓库编码 *</span><input v-model="form.warehouse_code" name="warehouse_code" :readonly="mode === 'edit'" /></label>
              <label><span>仓库名称 *</span><input v-model="form.name" name="name" /></label>
              <label><span>负责人</span><select v-model="form.owner_code" name="owner_code"><option value="">未分配</option><option v-for="member in activeMembers" :key="member.member_code" :value="member.member_code">{{ member.name }}（{{ member.member_code }}）</option></select></label>
              <label><span>所在城市</span><input v-model="form.city" name="city" /></label>
              <label><span>库容率（0–100）</span><input v-model="form.capacity_rate" name="capacity_rate" type="number" min="0" max="100" step="0.01" /></label>
              <label><span>今日入库</span><input v-model="form.inbound_quantity" name="inbound_quantity" type="number" min="0" /></label>
              <label><span>今日出库</span><input v-model="form.outbound_quantity" name="outbound_quantity" type="number" min="0" /></label>
              <label><span>状态</span><select v-model="form.status" name="status"><option value="active">启用</option><option value="disabled">已停用</option></select></label>
            </template>
            <template v-else-if="selected">
              <dl class="detail-list"><div><dt>仓库编码</dt><dd>{{ selected.warehouse_code }}</dd></div><div><dt>负责人</dt><dd>{{ selected.owner_name || "未分配" }}</dd></div><div><dt>城市</dt><dd>{{ selected.city || "—" }}</dd></div><div><dt>库容率</dt><dd>{{ rateLabel(selected.capacity_rate) }}</dd></div><div><dt>库存关联</dt><dd>{{ selected.sku_count ?? 0 }} 个 SKU · 可用 {{ selected.available_stock ?? 0 }}</dd></div></dl>
              <h3>负责人交接记录</h3><p v-if="!handovers.length">暂无交接记录。</p><ul v-else class="handover-list"><li v-for="record in handovers" :key="String(record.id)">{{ record.from_member_name || "未分配" }} → {{ record.to_member_name }} · {{ record.handover_type === "temporary" ? "临时" : "永久" }} · {{ record.status }}<button v-if="record.status === 'active' && record.handover_type === 'temporary'" class="button secondary" @click="restore(selected)">恢复</button></li></ul>
            </template>
          </div>
          <footer><button class="button secondary" @click="drawer = false">关闭</button><button v-if="mode === 'view'" class="button primary" @click="startEdit"><Pencil :size="14" />编辑</button><button v-else class="button primary" @click="save"><Check :size="14" />保存</button></footer>
        </aside>
      </div>
      <div v-if="handoverDrawer && selected" class="crud-overlay" @click.self="handoverDrawer = false"><aside class="crud-drawer"><header><div><span>HANDOVER</span><h2>交接负责人</h2><p>{{ selected.name }} · {{ selected.owner_name || "未分配" }}</p></div><button @click="handoverDrawer = false"><X :size="18" /></button></header><div class="crud-form"><div v-if="handoverError" class="crud-error">{{ handoverError }}</div><label><span>接管成员 *</span><select v-model="handoverForm.to_owner_code" name="to_owner_code"><option value="">请选择成员</option><option v-for="member in handoverMembers" :key="member.member_code" :value="member.member_code">{{ member.name }}（{{ member.member_code }}）</option></select></label><label><span>交接类型</span><select v-model="handoverForm.handover_type" name="handover_type"><option value="temporary">临时交接</option><option value="permanent">永久交接</option></select></label><label><span>交接说明</span><textarea v-model="handoverForm.reason" name="reason" rows="4" placeholder="说明离职、休假或岗位空缺原因" /></label></div><footer><button class="button secondary" @click="handoverDrawer = false">取消</button><button class="button primary" @click="submitHandover">确认交接</button></footer></aside></div>
      <div v-if="toast" class="crud-toast"><Check :size="15" />{{ toast }}</div>
    </Teleport>
  </section>
</template>

<style scoped>
.warehouse-toolbar { gap: 16px; flex-wrap: wrap; }
.warehouse-toolbar > .module-search { width: min(360px, 100%); flex: 1 1 280px; }
.warehouse-filters { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
.warehouse-filters :deep(.toolbar-select-trigger) { min-height: 38px; }
.warehouse-city { width: 168px; height: 38px; min-width: 0; }
.warehouse-city > span { flex-shrink: 0; color: var(--muted); font-size: 12px; }
.warehouse-city input { width: 100%; min-width: 0; }
.warehouse-footer { min-height: 64px; gap: 16px; flex-wrap: wrap; }
.warehouse-page table td:nth-child(n+5):nth-child(-n+8), .warehouse-page table th:nth-child(n+5):nth-child(-n+8) { text-align: right; font-variant-numeric: tabular-nums; }
.warehouse-page .status-tag[data-status="active"] { color: var(--success); background: #eaf8f2; }
.crud-form select, .crud-form textarea { width: 100%; min-height: 38px; padding: 9px 10px; border: 1px solid var(--border); border-radius: 7px; background: var(--surface); color: var(--text); font: inherit; font-size: 14px; }
.crud-form textarea { resize: vertical; }
.crud-form input { font-size: 14px; }
.crud-drawer > footer { display: flex; justify-content: flex-end; }
.detail-list { margin: 0 0 24px; }
.detail-list > div { display: flex; justify-content: space-between; gap: 16px; padding: 14px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
.detail-list dt { color: var(--muted); }
.detail-list dd { margin: 0; text-align: right; overflow-wrap: anywhere; }
.handover-list { padding: 0; list-style: none; font-size: 14px; }
.handover-list li { padding: 12px 0; border-bottom: 1px solid var(--border); line-height: 1.6; }
@media (max-width: 1100px) {
  .warehouse-toolbar > .module-search { flex: none; width: 100%; }
  .warehouse-filters { width: 100%; }
}
@media (max-width: 760px) {
  .warehouse-filters > .toolbar-select, .warehouse-city { flex: 1 1 140px; width: auto; }
  .warehouse-filters :deep(.toolbar-select-trigger) { width: 100%; }
  .warehouse-footer .pagination { flex-wrap: wrap; }
}
</style>
