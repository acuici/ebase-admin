<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  ArrowRight,
  Download,
  MoreHorizontal,
  Plus,
  RefreshCw,
  Search,
} from "lucide-vue-next";
import {
  getOrder,
  listOrders,
  type Order,
  type OrderDetail,
} from "../api/orders";
import {
  getDashboardStats,
  getOperationModuleStats,
  listOperationModule,
  type ModuleStats,
} from "../api/operations";
import { ApiError } from "../api/client";
import { useToast } from "../composables/useToast";
import { moduleConfigs } from "../data/moduleConfigs";
import TableState from "../components/common/TableState.vue";
import DateRangePicker, {
  type DateRangeValue,
} from "../components/common/DateRangePicker.vue";
import ToolbarSelect, {
  type ToolbarSelectOption,
} from "../components/common/ToolbarSelect.vue";
const props = defineProps<{ title: string }>();
const route = useRoute();
const router = useRouter();
const { success, error: showError, info } = useToast();
const query = ref(String(route.query.order_no || route.query.keyword || ""));
const activeTab = ref(Math.max(0, Number(route.query.tab) || 0));
const selectedRow = ref(0);
const selected = ref<number[]>([]);
const reportDates = ref<DateRangeValue>({
  start: String(route.query.start_date || "2026-08-04"),
  end: String(route.query.end_date || "2026-09-02"),
  preset: 30,
});
const filterValues = reactive<Record<string, string>>({});
const filterParamNames: Record<string, string> = {
  全部类目: "category",
  全部品牌: "brand",
  销售状态: "sales_status",
  发布渠道: "publish_channel",
  全部仓库: "warehouse",
  库存状态: "inventory_status",
  供应商: "supplier",
  周转天数: "turnover_days",
  会员等级: "member_tier",
  来源渠道: "source_channel",
  用户标签: "customer_tag",
  消费金额: "spend_range",
  全部承运商: "carrier_code",
  时效状态: "delivery_status",
  异常类型: "exception_type",
  内容类型: "content_type",
  内容状态: "content_status",
  发布时间: "published_at",
  券类型: "coupon_type",
  适用渠道: "applicable_channel",
  目标人群: "audience",
  有效期: "validity",
  活动类型: "campaign_type",
  负责人: "owner",
  活动日期: "campaign_date",
  对比上周期: "comparison",
  全部店铺: "store",
  全部渠道: "channel",
  所有部门: "department",
  所有角色: "role",
  账号状态: "account_status",
  数据范围: "data_scope",
};
function filterParamName(label: string) {
  const parameter = filterParamNames[label];
  if (!parameter) throw new Error(`缺少筛选参数映射：${label}`);
  return parameter;
}
const channelOptions: ToolbarSelectOption[] = [
  { label: "全部渠道", value: "" },
  { label: "独立站", value: "storefront" },
  { label: "天猫", value: "tmall" },
  { label: "京东", value: "jd" },
  { label: "抖音", value: "douyin" },
  { label: "品牌小程序", value: "wechat_miniapp" },
];
const pageSizeOptions: ToolbarSelectOption[] = [
  { label: "20 条", value: "20" },
  { label: "50 条", value: "50" },
  { label: "100 条", value: "100" },
];
const config = computed(() => moduleConfigs[props.title]);
const paths: Record<string, { primary: string; detail: string }> = {
  订单管理: { primary: "/orders/new", detail: "/orders/EB202609010846" },
  物流履约: {
    primary: "/logistics/SF128604780",
    detail: "/logistics/SF128604780",
  },
  产品管理: { primary: "/products/new", detail: "/products/new" },
  库存中心: { primary: "/inventory/restock", detail: "/inventory/restock" },
  用户管理: { primary: "/users/U-286420", detail: "/users/U-286420" },
  内容中心: { primary: "/content/new", detail: "/content/new" },
  优惠券管理: { primary: "/coupons/new", detail: "/coupons/new" },
  营销活动: { primary: "/marketing/new", detail: "/marketing/new" },
  数据报表: { primary: "/reports/analysis", detail: "/reports/analysis" },
  权限与系统设置: {
    primary: "/settings/roles/operator",
    detail: "/settings/roles/operator",
  },
};
const workflow = computed(() => paths[props.title]);
const initialPage = Math.max(1, Number(route.query.page) || 1);
const remoteOrders = ref<Order[]>([]);
const orderTotal = ref(0);
const orderPages = ref(1);
const page = ref(initialPage);
const pageSize = ref(20);
const jumpPage = ref(String(initialPage));
const loading = ref(false);
const loadError = ref("");
const isUpdating = ref(false);
const selectedOrderId = ref("");
const selectedOrderDetail = ref<OrderDetail | null>(null);
const detailLoading = ref(false);
const channel = ref(String(route.query.channel || ""));
const storeId = ref(String(route.query.store_id || ""));
const isOrders = computed(() => props.title === "订单管理");
const orderTabs = [
  { label: "全部订单", status: "" },
  { label: "待付款", status: "pending_payment" },
  { label: "待发货", status: "paid" },
  { label: "处理中", status: "processing" },
  { label: "运输中", status: "shipped" },
  { label: "已完成", status: "completed" },
  { label: "已取消", status: "cancelled" },
];
const orderTabCounts = ref<Record<string, number>>({});
const moduleTabOptions: Record<
  string,
  Array<{ label: string; status?: string }>
> = {
  products: [
    { label: "全部商品" },
    { label: "在售", status: "active" },
    { label: "草稿", status: "draft" },
    { label: "已归档", status: "archived" },
  ],
  inventory: [
    { label: "全部库存" },
    { label: "可售", status: "active" },
    { label: "低库存", status: "low_stock" },
    { label: "缺货", status: "out_of_stock" },
  ],
  customers: [
    { label: "全部用户" },
    { label: "正常", status: "active" },
    { label: "已停用", status: "disabled" },
  ],
  logistics: [
    { label: "全部运单" },
    { label: "运输中", status: "shipped" },
    { label: "已签收", status: "delivered" },
  ],
};
const moduleTabCounts = ref<Record<string, number[]>>({});
const currentTabs = computed(() =>
  isOrders.value
    ? orderTabs.map((item) => item.label)
    : moduleTabOptions[moduleKey[props.title]]?.map((item) => item.label) ||
      config.value.tabs,
);
const currentTabCounts = computed(() => {
  if (isOrders.value)
    return orderTabs.map((item) => orderTabCounts.value[item.status] ?? 0);
  if (moduleKey[props.title])
    return moduleTabCounts.value[moduleKey[props.title]] || [];
  return config.value.tabCounts;
});
const activeOrderStatus = computed(() =>
  isOrders.value ? orderTabs[activeTab.value]?.status || "" : "",
);
function formatCount(value: string | number | undefined): string {
  const numeric = Number(String(value ?? "").replace(/,/g, ""));
  return Number.isFinite(numeric) ? numeric.toLocaleString("zh-CN") : "0";
}
function displayOrderStatus(status: string) {
  return (
    (
      {
        pending_payment: "待付款",
        paid: "待发货",
        processing: "处理中",
        shipped: "运输中",
        completed: "已完成",
        cancelled: "已取消",
      } as Record<string, string>
    )[status] || status
  );
}
function displayChannel(channel: string) {
  return (
    (
      {
        storefront: "独立站",
        tmall: "天猫",
        jd: "京东",
        douyin: "抖音",
        wechat_miniapp: "品牌小程序",
      } as Record<string, string>
    )[channel] || channel
  );
}
function displayCarrier(carrier: string) {
  return (
    (
      { sf: "顺丰", jd: "京东物流", yto: "圆通", zto: "中通" } as Record<
        string,
        string
      >
    )[carrier.toLowerCase()] || carrier
  );
}
function displayPayment(channel = "") {
  return (
    (
      {
        wechat: "微信支付",
        alipay: "支付宝",
        wechat_pay: "微信支付",
      } as Record<string, string>
    )[channel] ||
    channel ||
    "暂无支付记录"
  );
}
function formatTime(value?: string | null) {
  if (!value) return "—";
  return new Intl.DateTimeFormat("zh-CN", {
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  }).format(new Date(value));
}
const remoteRows = ref<Array<Record<string, unknown>>>([]);
const moduleTotal = ref(0);
const modulePages = ref(1);
const moduleKey: Record<string, string> = {
  产品管理: "products",
  库存中心: "inventory",
  用户管理: "customers",
  物流履约: "logistics",
  内容中心: "content",
  优惠券管理: "coupons",
  营销活动: "campaigns",
};
const moduleColumns: Record<string, string[]> = {
  products: [
    "product_no",
    "name",
    "brand",
    "min_price",
    "sku_count",
    "available_stock",
    "published_channels",
    "status",
  ],
  inventory: [
    "sku_code",
    "product_name",
    "sku_name",
    "stock_quantity",
    "reserved_quantity",
    "available_stock",
    "price",
    "status",
  ],
  customers: [
    "customer_no",
    "name",
    "tags",
    "source_channel",
    "order_count",
    "total_spend",
    "last_order_at",
  ],
  logistics: [
    "package_no",
    "tracking_no",
    "carrier_code",
    "order_id",
    "status",
    "exception_type",
    "severity",
    "description",
  ],
  content: [
    "id",
    "title",
    "content_type",
    "slug",
    "status",
    "published_at",
    "updated_at",
  ],
  coupons: [
    "code",
    "name",
    "discount_type",
    "discount_value",
    "min_amount",
    "total_quantity",
    "claimed_quantity",
    "status",
  ],
  campaigns: [
    "id",
    "name",
    "campaign_type",
    "budget",
    "status",
    "starts_at",
    "ends_at",
  ],
};
const activeModuleStatus = computed(
  () =>
    moduleTabOptions[moduleKey[props.title]]?.[activeTab.value]?.status || "",
);
function selectedModuleFilters(): Record<string, string> {
  const filters: Record<string, string> = {};
  for (const label of config.value.filters || []) {
    const value = filterValues[label];
    if (value) filters[filterParamName(label)] = value;
  }
  return filters;
}
const moduleFilters = computed<Record<string, string>>(() => {
  const filters = selectedModuleFilters();
  if (activeModuleStatus.value) filters.status = activeModuleStatus.value;
  return filters;
});
async function loadModuleRows() {
  if (isOrders.value || !moduleKey[props.title]) return;
  const started = Date.now();
  loading.value = true;
  loadError.value = "";
  try {
    const data = await listOperationModule(moduleKey[props.title], {
      page: page.value,
      page_size: pageSize.value,
      keyword: query.value,
      ...moduleFilters.value,
    });
    await waitForTransition(started);
    remoteRows.value = data.items;
    moduleTotal.value = data.pagination.total;
    modulePages.value = Math.max(1, data.pagination.pages);
  } catch (e) {
    loadError.value = e instanceof ApiError ? e.body.message : "数据加载失败";
  } finally {
    loading.value = false;
  }
}
async function loadModuleTabCounts() {
  const key = moduleKey[props.title];
  const options = moduleTabOptions[key];
  if (!options) return;
  try {
    const counts = await Promise.all(
      options.map((item) =>
        listOperationModule(key, {
          page: 1,
          page_size: 1,
          status: item.status,
        }),
      ),
    );
    moduleTabCounts.value[key] = counts.map(
      (result) => result.pagination.total,
    );
  } catch {
    moduleTabCounts.value[key] = [];
  }
}
function displayStatus(value: unknown): string {
  return (
    (
      {
        active: "在售",
        draft: "草稿",
        archived: "已归档",
        pending: "待处理",
        processing: "处理中",
        shipped: "运输中",
        delivered: "已签收",
        open: "异常待处理",
        disabled: "已停用",
      } as Record<string, string>
    )[String(value)] || String(value ?? "—")
  );
}
function customerTier(value: unknown): string {
  const amount = Number(value || 0);
  return amount >= 50000
    ? "黑金会员"
    : amount >= 20000
      ? "金卡会员"
      : amount >= 5000
        ? "银卡会员"
        : "普通会员";
}
function moduleRow(item: Record<string, unknown>): string[] {
  if (moduleKey[props.title] === "products")
    return [
      String(item.product_no ?? "—"),
      String(item.name ?? "—"),
      String(item.category ?? "未分类"),
      `¥${item.min_price ?? 0}–¥${item.max_price ?? 0}`,
      formatCount(Number(item.sku_count)),
      formatCount(Number(item.available_stock)),
      `${formatCount(Number(item.published_channels))} 个渠道`,
      displayStatus(item.status),
    ];
  if (moduleKey[props.title] === "inventory")
    return [
      String(item.sku_code ?? "—"),
      String(item.product_name ?? "—"),
      String(item.sku_name ?? "—"),
      formatCount(Number(item.stock_quantity)),
      formatCount(Number(item.reserved_quantity)),
      formatCount(Number(item.available_stock)),
      `¥${item.price ?? 0}`,
      displayStatus(item.status),
    ];
  if (moduleKey[props.title] === "customers")
    return [
      String(item.customer_no ?? "—"),
      String(item.name ?? "—"),
      customerTier(item.total_spend),
      String(item.tags || "未打标签"),
      displayChannel(String(item.source_channel ?? "")),
      formatCount(Number(item.order_count)),
      `¥${item.total_spend ?? 0}`,
      formatTime(String(item.last_order_at ?? "")),
    ];
  if (moduleKey[props.title] === "logistics")
    return [
      String(item.package_no ?? "—"),
      String(item.tracking_no ?? "—"),
      displayCarrier(String(item.carrier_code ?? "—")),
      String(item.order_id ?? "—"),
      displayStatus(item.status),
      String(item.exception_type ?? "—"),
      String(item.severity ?? "—"),
      String(item.description ?? "—"),
    ];
  if (moduleKey[props.title] === "content")
    return [
      String(item.id ?? "—"),
      String(item.title ?? "—"),
      String(item.content_type ?? "—"),
      String(item.content_key ?? "—"),
      String(item.slug ?? "—"),
      String(item.created_by ?? "—"),
      formatTime(String(item.published_at ?? item.updated_at ?? "")),
      displayStatus(item.status),
    ];
  if (moduleKey[props.title] === "coupons")
    return [
      String(item.code ?? item.id ?? "—"),
      String(item.name ?? "—"),
      String(item.discount_type ?? "—"),
      String(item.discount_value ?? "—"),
      `${formatCount(item.total_quantity as number)} / ${formatCount(item.claimed_quantity as number)}`,
      formatCount(item.claimed_quantity as number),
      "—",
      displayStatus(item.status),
    ];
  if (moduleKey[props.title] === "campaigns")
    return [
      String(item.id ?? "—"),
      String(item.name ?? "—"),
      String(item.campaign_type ?? "—"),
      String(item.publish_channel ?? "—"),
      String(item.budget ?? "—"),
      String(item.revenue ?? "—"),
      String(item.roi ?? "—"),
      displayStatus(item.status),
    ];
  return moduleColumns[moduleKey[props.title]].map((key) =>
    String(item[key] ?? "—"),
  );
}
function hasSelectedFilters() {
  return Object.values(filterValues).some(Boolean);
}
function filterRenderedRows(source: string[][]) {
  const selected = Object.entries(filterValues).filter(([, value]) =>
    Boolean(value),
  );
  if (!selected.length) return source;
  return source.filter((row) =>
    selected.every(([label, value]) => {
      const display =
        toolbarOptions(label).find((option) => option.value === value)?.label ||
        value;
      return row.some((cell) =>
        String(cell).toLowerCase().includes(display.toLowerCase()),
      );
    }),
  );
}
function filteredStaticRows() {
  const k = query.value.trim().toLowerCase();
  const tab = config.value.tabs[activeTab.value];
  let result = config.value.rows;
  if (props.title === "数据报表") {
    const start = new Date(`${reportDates.value.start}T00:00:00`);
    const end = new Date(`${reportDates.value.end}T23:59:59`);
    result = result.filter((row) => {
      const date = new Date(`2026-${String(row[0]).padStart(5, "0")}T12:00:00`);
      return date >= start && date <= end;
    });
  }
  if (activeTab.value > 0)
    result = result.filter((r) =>
      r.some((v) => String(v).includes(tab) || tab.includes(String(v))),
    );
  if (k)
    result = result.filter((r) =>
      r.some((v) => String(v).toLowerCase().includes(k)),
    );
  return filterRenderedRows(result);
}
const allSelected = computed(
  () => rows.value.length > 0 && selected.value.length === rows.value.length,
);
const moduleStats = ref<ModuleStats | null>(null);
const statsLoading = ref(false);
async function loadModuleStats() {
  if (isOrders.value || !moduleKey[props.title]) return;
  statsLoading.value = true;
  try {
    moduleStats.value = await getOperationModuleStats(moduleKey[props.title]);
  } catch {
    moduleStats.value = null;
  } finally {
    statsLoading.value = false;
  }
}
const displayMetrics = computed(() => moduleStats.value?.metrics ?? []);
const displayPanel = computed(() => moduleStats.value?.panel);
const displayColumns = computed(() => {
  const key = moduleKey[props.title];
  if (key === "products")
    return [
      "SPU",
      "商品名称",
      "类目",
      "价格区间",
      "SKU 数",
      "可售库存",
      "发布渠道",
      "状态",
    ];
  if (key === "inventory")
    return [
      "SKU",
      "商品名称",
      "SKU 名称",
      "现货",
      "锁定",
      "可售",
      "价格",
      "状态",
    ];
  if (key === "customers")
    return [
      "用户 ID",
      "用户",
      "会员等级",
      "用户标签",
      "来源渠道",
      "累计订单",
      "累计消费",
      "最近购买",
    ];
  if (key === "logistics")
    return [
      "包裹号",
      "运单号",
      "承运商",
      "关联订单",
      "状态",
      "异常类型",
      "异常等级",
      "异常说明",
    ];
  return config.value.columns;
});
const displayStatusIndex = computed(() => {
  const key = moduleKey[props.title];
  if (key === "products" || key === "inventory") return 7;
  if (key === "logistics") return 4;
  if (key === "content" || key === "coupons" || key === "campaigns") return 7;
  return -1;
});
const rows = computed(() => {
  if (isOrders.value)
    return remoteOrders.value.map((order) => [
      order.order_no,
      order.external_order_no || "后台订单",
      `${order.items?.[0]?.product_name || "订单商品"} · ${order.items?.[0]?.quantity || 0} 件`,
      displayChannel(order.channel_type || "storefront"),
      `${order.currency} ${order.total_amount}`,
      "—",
      displayOrderStatus(order.status),
      order.created_at,
    ]);
  // 已接 API 的模块由后端完成筛选。这里不能再用中文展示文案过滤，
  // 否则 carrier_code=jd 等合法结果会被“京东物流”二次过滤为空。
  if (moduleKey[props.title]) return remoteRows.value.map(moduleRow);
  return filteredStaticRows();
});
let requestSequence = 0;
const waitForTransition = (started: number) =>
  new Promise<void>((resolve) =>
    setTimeout(resolve, Math.max(0, 1000 - (Date.now() - started))),
  );
async function loadOrderCounts() {
  try {
    const results = await Promise.all(
      orderTabs.map((item) =>
        listOrders({ page: 1, page_size: 1, status: item.status || undefined }),
      ),
    );
    orderTabCounts.value = Object.fromEntries(
      orderTabs.map((item, index) => [
        item.status,
        results[index].pagination.total,
      ]),
    );
  } catch {
    /* 列表错误态已承担主要反馈，统计失败不阻塞列表。 */
  }
}
async function loadOrderDetail(id: string) {
  if (!id) {
    selectedOrderDetail.value = null;
    return;
  }
  detailLoading.value = true;
  try {
    selectedOrderDetail.value = await getOrder(id);
  } catch (e) {
    showError(
      "订单详情加载失败",
      e instanceof ApiError ? e.body.message : "请稍后重试",
    );
  } finally {
    detailLoading.value = false;
  }
}
async function loadOrders() {
  const sequence = ++requestSequence;
  const started = Date.now();
  loading.value = true;
  loadError.value = "";
  selected.value = [];
  try {
    const data = await listOrders({
      page: page.value,
      page_size: pageSize.value,
      order_no: query.value,
      status: activeOrderStatus.value || undefined,
      channel_type: channel.value || undefined,
      channel_store_id: storeId.value || undefined,
      ...Object.fromEntries(
        Object.entries(filterValues).map(([label, value]) => [
          filterParamName(label),
          value || undefined,
        ]),
      ),
    });
    if (sequence !== requestSequence) return;
    await waitForTransition(started);
    remoteOrders.value = data.items;
    orderTotal.value = data.pagination.total;
    orderPages.value = Math.max(1, data.pagination.pages);
    const nextId = data.items.some((item) => item.id === selectedOrderId.value)
      ? selectedOrderId.value
      : data.items[0]?.id || "";
    selectedOrderId.value = nextId;
    selectedRow.value = Math.max(
      0,
      data.items.findIndex((item) => item.id === nextId),
    );
    await loadOrderDetail(nextId);
  } catch (e) {
    if (sequence !== requestSequence) return;
    loadError.value = e instanceof ApiError ? e.body.message : "订单加载失败";
    remoteOrders.value = [];
    selectedOrderDetail.value = null;
  } finally {
    if (sequence === requestSequence) loading.value = false;
  }
}
function toggleAll() {
  selected.value = allSelected.value ? [] : rows.value.map((_, i) => i);
}
function openOrder(order: Order) {
  router.push(`/orders/${order.id}`);
}
function exportData() {
  info("导出任务未创建", "当前后端尚未提供订单导出接口。");
}
function changeReportDates(value: DateRangeValue) {
  void router.replace({
    query: {
      ...route.query,
      start_date: value.start,
      end_date: value.end,
      page: undefined,
    },
  });
}
function toolbarOptions(label: string): ToolbarSelectOption[] {
  const optionSets: Record<string, Array<[string, string]>> = {
    category: [
      ["家居生活", "home_living"],
      ["箱包出行", "luggage"],
      ["数码影音", "digital"],
      ["智能穿戴", "wearables"],
    ],
    brand: [
      ["LUMEA Home", "lumea_home"],
      ["LUMEA Life", "lumea_life"],
      ["LUMEA Audio", "lumea_audio"],
      ["LUMEA Tech", "lumea_tech"],
    ],
    publish_channel: [
      ["独立站", "storefront"],
      ["天猫", "tmall"],
      ["京东", "jd"],
      ["抖音", "douyin"],
      ["品牌小程序", "wechat_miniapp"],
    ],
    applicable_channel: [
      ["独立站", "storefront"],
      ["天猫", "tmall"],
      ["京东", "jd"],
      ["抖音", "douyin"],
    ],
    source_channel: [
      ["独立站", "storefront"],
      ["天猫", "tmall"],
      ["京东", "jd"],
      ["抖音", "douyin"],
    ],
    warehouse: [
      ["华东一号仓", "east_1"],
      ["华南一号仓", "south_1"],
      ["华北二号仓", "north_2"],
      ["西南中心仓", "southwest"],
    ],
    carrier_code: [
      ["顺丰", "sf"],
      ["京东物流", "jd"],
      ["圆通", "yto"],
      ["中通", "zto"],
    ],
    sales_status: [
      ["在售", "active"],
      ["草稿", "draft"],
      ["已归档", "archived"],
    ],
    inventory_status: [
      ["可售", "active"],
      ["低库存", "low_stock"],
      ["缺货", "out_of_stock"],
    ],
    content_status: [
      ["已发布", "published"],
      ["草稿", "draft"],
      ["待审核", "pending_review"],
    ],
    account_status: [
      ["正常", "active"],
      ["已停用", "disabled"],
    ],
    member_tier: [
      ["黑金会员", "black_gold"],
      ["金卡会员", "gold"],
      ["银卡会员", "silver"],
      ["普通会员", "standard"],
    ],
    content_type: [
      ["图文", "article"],
      ["短视频", "short_video"],
      ["直播", "live"],
      ["商品详情", "product_detail"],
      ["公告", "notice"],
    ],
    coupon_type: [
      ["满减券", "threshold"],
      ["无门槛", "cash"],
      ["折扣券", "discount"],
      ["运费券", "shipping"],
      ["商品券", "product"],
    ],
    campaign_type: [
      ["会员日", "member_day"],
      ["新品营销", "new_product"],
      ["满减活动", "threshold"],
      ["秒杀", "flash_sale"],
      ["组合购", "bundle"],
    ],
    customer_tag: [
      ["高复购", "high_repeat"],
      ["高客单", "high_value"],
      ["办公人群", "office"],
      ["新品偏好", "new_product"],
    ],
    exception_type: [
      ["地址信息异常", "address"],
      ["超时未揽收", "pickup_timeout"],
      ["派送失败", "delivery_failed"],
      ["包裹破损", "damaged"],
    ],
    published_at: [
      ["今天", "today"],
      ["昨天", "yesterday"],
    ],
    campaign_date: [
      ["今天", "today"],
      ["昨天", "yesterday"],
    ],
    department: [
      ["运营中心", "operations"],
      ["供应链", "supply_chain"],
      ["营销中心", "marketing"],
      ["用户运营", "customer_operations"],
      ["商品中心", "catalog"],
    ],
    role: [
      ["运营总监", "operations_director"],
      ["仓库主管", "warehouse_manager"],
      ["营销主管", "marketing_manager"],
      ["用户运营", "customer_operator"],
      ["商品运营", "catalog_operator"],
    ],
    supplier: [
      ["深圳智联供应链", "shenzhen_zhilian"],
      ["苏州声学科技", "suzhou_acoustics"],
      ["宁波旅行用品", "ningbo_travel"],
    ],
    turnover_days: [
      ["7 天以内", "lte_7"],
      ["8–30 天", "8_to_30"],
      ["30 天以上", "gt_30"],
    ],
    spend_range: [
      ["¥5,000 以下", "lt_5000"],
      ["¥5,000–20,000", "5000_to_20000"],
      ["¥20,000 以上", "gte_20000"],
    ],
    delivery_status: [
      ["正常", "on_time"],
      ["即将超时", "at_risk"],
      ["已超时", "overdue"],
    ],
    audience: [
      ["新客", "new_customer"],
      ["会员用户", "member"],
      ["高价值用户", "high_value"],
    ],
    validity: [
      ["进行中", "active"],
      ["即将到期", "expiring"],
      ["已结束", "ended"],
    ],
    owner: [
      ["林知夏", "lin_zhixia"],
      ["陈曦", "chen_xi"],
      ["苏然", "su_ran"],
    ],
    comparison: [
      ["上一个周期", "previous_period"],
      ["去年同期", "year_over_year"],
    ],
    store: [
      ["天猫旗舰店", "tmall_flagship"],
      ["京东自营店", "jd_self_operated"],
      ["抖音商城", "douyin_mall"],
      ["品牌小程序", "wechat_miniapp"],
    ],
    channel: [
      ["独立站", "storefront"],
      ["天猫", "tmall"],
      ["京东", "jd"],
      ["抖音", "douyin"],
      ["品牌小程序", "wechat_miniapp"],
    ],
    data_scope: [
      ["全部数据", "all"],
      ["华东仓", "east_warehouse"],
      ["营销数据", "marketing"],
      ["用户数据", "customer"],
      ["商品数据", "catalog"],
    ],
  };
  return [
    { label, value: "" },
    ...(optionSets[filterParamName(label)] || []).map(
      ([optionLabel, value]) => ({ label: optionLabel, value }),
    ),
  ];
}
function changeToolbarFilter(label: string, value: string) {
  filterValues[label] = value;
  page.value = 1;
  void router.replace({
    query: {
      ...route.query,
      page: undefined,
      [filterParamName(label)]: value || undefined,
    },
  });
}
function clearTableFilters() {
  query.value = "";
  channel.value = "";
  storeId.value = "";
  activeTab.value = 0;
  Object.keys(filterValues).forEach((key) => delete filterValues[key]);
  page.value = 1;
  void router.replace({ query: {} });
}
function inspectContextItem(title: string, meta: string) {
  info(title, `${meta}。对应的详情处理能力将在业务接口接入后开放。`);
}
function selectTab(index: number) {
  if (index === activeTab.value) return;
  activeTab.value = index;
}
function selectOrder(index: number) {
  selectedRow.value = index;
  selectedOrderId.value = remoteOrders.value[index]?.id || "";
  void loadOrderDetail(selectedOrderId.value);
}
const currentPages = computed(() =>
  isOrders.value
    ? orderPages.value
    : moduleKey[props.title]
      ? modulePages.value
      : 1,
);
function goPage(target: number) {
  if (target < 1 || target > currentPages.value || target === page.value)
    return;
  page.value = target;
  void router.replace({
    query: { ...route.query, page: target === 1 ? undefined : String(target) },
  });
}
function changePageSize(value: string | number) {
  const next = Math.min(100, Math.max(10, Number(value) || 20));
  pageSize.value = next;
  page.value = 1;
}
function jumpToPage() {
  const target = Math.min(
    currentPages.value,
    Math.max(1, Number(jumpPage.value) || 1),
  );
  page.value = target;
  void router.replace({
    query: { ...route.query, page: target === 1 ? undefined : String(target) },
  });
}
const visiblePages = computed<Array<number | string>>(() => {
  const total = currentPages.value;
  if (total <= 7) return Array.from({ length: total }, (_, index) => index + 1);
  if (page.value <= 4) return [1, 2, 3, 4, 5, "ellipsis-end", total];
  if (page.value >= total - 3)
    return [
      1,
      "ellipsis-start",
      ...Array.from({ length: 5 }, (_, index) => total - 4 + index),
    ];
  return [
    1,
    "ellipsis-start",
    page.value - 1,
    page.value,
    page.value + 1,
    "ellipsis-end",
    total,
  ];
});
const orderMetrics = computed(() => [
  {
    label: "订单总量",
    value: orderTabCounts.value[""] ?? orderTotal.value,
    note: "来自订单 API",
  },
  {
    label: "待发货",
    value: orderTabCounts.value.paid ?? 0,
    note: "等待仓库处理",
  },
  {
    label: "运输中",
    value: orderTabCounts.value.shipped ?? 0,
    note: "正在履约",
  },
  {
    label: "当前页金额",
    value: `CNY ${remoteOrders.value.reduce((sum, item) => sum + Number(item.total_amount || 0), 0).toFixed(2)}`,
    note: `本页 ${remoteOrders.value.length} 单`,
  },
]);
const contextOrder = computed(
  () =>
    selectedOrderDetail.value ||
    remoteOrders.value.find((item) => item.id === selectedOrderId.value) ||
    null,
);
const fulfillmentProgress = computed(
  () =>
    (
      ({
        pending_payment: 12,
        paid: 30,
        processing: 52,
        shipped: 76,
        completed: 100,
        cancelled: 0,
      }) as Record<string, number>
    )[contextOrder.value?.status || ""] ?? 0,
);
const contextItems = computed(() => {
  const order = selectedOrderDetail.value;
  if (!order) return [];
  const payment = order.payments?.[0];
  const fulfillment = order.fulfillments?.[0];
  const log = order.status_logs?.at(-1);
  return [
    {
      title: payment?.status === "paid" ? "支付已确认" : "支付状态",
      meta: payment
        ? `${displayPayment(payment.channel)} · ${payment.currency} ${payment.amount}`
        : "暂无支付记录",
      tone: payment?.status === "paid" ? "success" : "warning",
    },
    {
      title: fulfillment ? "履约任务已创建" : "等待创建履约",
      meta: fulfillment
        ? `${fulfillment.warehouse_code || "未分配仓库"} · ${displayOrderStatus(fulfillment.status)}`
        : "订单付款后进入履约",
      tone: fulfillment ? "primary" : "warning",
    },
    {
      title: "当前订单状态",
      meta: `${displayOrderStatus(order.status)} · ${formatTime(order.updated_at)}`,
      tone: order.status === "cancelled" ? "danger" : "primary",
    },
    {
      title: "最近状态记录",
      meta: log
        ? `${displayOrderStatus(log.to_status)} · ${formatTime(log.created_at)}`
        : "暂无状态变更记录",
      tone: "primary",
    },
  ];
});
let searchTimer: number | undefined;
config.value.filters.forEach((label) => {
  const value = route.query[filterParamName(label)];
  if (value) filterValues[label] = String(value);
});
watch(
  () => props.title,
  () => {
    activeTab.value = 0;
    query.value = "";
    remoteRows.value = [];
    moduleTotal.value = 0;
    modulePages.value = 1;
    page.value = 1;
    loadError.value = "";
    void loadModuleRows();
    void loadModuleTabCounts();
    void loadModuleStats();
  },
);
watch(query, () => {
  window.clearTimeout(searchTimer);
  void router.replace({
    query: {
      ...route.query,
      order_no: isOrders.value ? query.value || undefined : undefined,
      keyword: isOrders.value ? undefined : query.value || undefined,
      page: undefined,
    },
  });
  searchTimer = window.setTimeout(() => {
    if (page.value !== 1) page.value = 1;
    else if (isOrders.value) void loadOrders();
    else void loadModuleRows();
  }, 280);
});
watch([activeTab, channel, storeId], () => {
  void router.replace({
    query: {
      ...route.query,
      tab: activeTab.value ? String(activeTab.value) : undefined,
      channel: channel.value || undefined,
      store_id: storeId.value || undefined,
      page: undefined,
    },
  });
  if (page.value !== 1) page.value = 1;
  else if (isOrders.value) void loadOrders();
  else void loadModuleRows();
});
watch(
  filterValues,
  () => {
    if (moduleKey[props.title]) void loadModuleRows();
  },
  { deep: true },
);
watch(page, (value) => {
  jumpPage.value = String(value);
  if (isOrders.value) void loadOrders();
  else void loadModuleRows();
});
watch(
  isOrders,
  (value) => {
    if (value) {
      void loadOrderCounts();
      void loadOrders();
    } else {
      void loadModuleRows();
      void loadModuleTabCounts();
      void loadModuleStats();
    }
  },
  { immediate: true },
);
onBeforeUnmount(() => window.clearTimeout(searchTimer));
</script>
<template>
  <section v-if="config" class="data-page">
    <div class="page-heading">
      <div>
        <span class="eyebrow">{{ config.eyebrow }}</span>
        <h1>{{ title }}</h1>
        <p>{{ config.description }}</p>
      </div>
      <div class="heading-actions">
        <button class="button secondary" @click="exportData">
          <Download :size="16" />导出</button
        ><button class="button primary" @click="router.push(workflow.primary)">
          <Plus :size="16" />{{ config.primaryAction }}
        </button>
      </div>
    </div>
    <section class="metric-strip module-metrics">
      <article
        v-for="m in isOrders
          ? orderMetrics
          : moduleKey[title]
            ? displayMetrics
            : config.metrics"
        :key="m.label"
        class="metric-item"
      >
        <span>{{ m.label }}</span
        ><strong>{{ m.value }}</strong
        ><small
          ><template v-if="'delta' in m"
            ><b :class="{ negative: 'negative' in m && m.negative }">{{
              m.delta
            }}</b></template
          >{{ m.note }}</small
        >
      </article>
    </section>
    <div class="module-tabs">
      <button
        v-for="(tab, i) in currentTabs"
        :key="tab"
        :class="{ active: activeTab === i }"
        @click="selectTab(i)"
      >
        {{ tab
        }}<span
          v-if="currentTabCounts[i] !== '' && currentTabCounts[i] !== undefined"
          >{{ formatCount(currentTabCounts[i]) }}</span
        >
      </button>
    </div>
    <div class="module-layout">
      <article class="surface module-table-card">
        <div class="module-toolbar">
          <label class="module-search"
            ><Search :size="16" /><input
              v-model="query"
              :name="isOrders ? 'order_no' : 'keyword'"
              :placeholder="config.searchPlaceholder"
          /></label>
          <div v-if="isOrders" class="filter-group order-filters">
            <ToolbarSelect
              v-model="channel"
              label="渠道"
              :options="channelOptions"
              aria-label="订单渠道"
            /><label
              ><span>店铺 ID</span
              ><input
                v-model="storeId"
                name="store_id"
                inputmode="numeric"
                placeholder="全部店铺" /></label
            ><button
              class="button secondary icon-only"
              aria-label="刷新订单"
              @click="loadOrders"
            >
              <RefreshCw :size="16" />
            </button>
          </div>
          <div v-else class="filter-group">
            <DateRangePicker
              v-if="title === '数据报表'"
              v-model="reportDates"
              @change="changeReportDates"
            /><ToolbarSelect
              v-for="f in title === '数据报表'
                ? config.filters.slice(1)
                : config.filters"
              :key="f"
              :model-value="filterValues[f] || f"
              :options="toolbarOptions(f)"
              :aria-label="f"
              @update:model-value="changeToolbarFilter(f, $event)"
            />
          </div>
        </div>
        <TableState
          v-if="loading"
          state="loading"
          :title="isOrders ? '正在加载订单' : `正在加载${title}`"
          :description="
            isOrders
              ? '正在从后台同步最新订单与履约状态。'
              : '正在从后台同步最新业务数据。'
          "
        /><TableState
          v-else-if="loadError"
          state="error"
          :title="isOrders ? '订单加载失败' : `${title}加载失败`"
          :description="loadError"
          ><template #action
            ><button
              class="button secondary"
              @click="isOrders ? loadOrders() : loadModuleRows()"
            >
              重新加载
            </button></template
          ></TableState
        ><TableState
          v-else-if="!rows.length"
          state="empty"
          :filtered="
            Boolean(
              query || channel || storeId || activeTab || hasSelectedFilters(),
            )
          "
          :title="isOrders ? '没有找到订单' : `没有找到${title}记录`"
          :description="
            isOrders
              ? '当前条件下没有订单，调整筛选条件或清除搜索后再试。'
              : '当前条件下没有业务记录，调整搜索、页签或筛选条件后再试。'
          "
          ><template #action
            ><button
              v-if="
                query || channel || storeId || activeTab || hasSelectedFilters()
              "
              class="button secondary"
              @click="clearTableFilters"
            >
              清除筛选
            </button></template
          ></TableState
        >
        <div v-else class="table-scroll">
          <table class="module-table">
            <thead>
              <tr>
                <th class="check-cell">
                  <input
                    name="select_all"
                    type="checkbox"
                    :checked="allSelected"
                    @change="toggleAll"
                  />
                </th>
                <th v-for="c in displayColumns" :key="c">{{ c }}</th>
                <th class="action-cell">操作</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, ri) in rows"
                :key="isOrders ? remoteOrders[ri].id : ri"
                :class="{ selected: selectedRow === ri }"
                @click="isOrders ? selectOrder(ri) : (selectedRow = ri)"
              >
                <td class="check-cell">
                  <input
                    v-model="selected"
                    name="row_ids"
                    type="checkbox"
                    :value="ri"
                    @click.stop
                  />
                </td>
                <td
                  v-for="(cell, i) in row"
                  :key="i"
                  :class="{ mono: i === 0, 'strong-cell': i === 1 }"
                >
                  <span
                    v-if="i === displayStatusIndex"
                    class="status-tag"
                    :data-status="cell"
                    >{{ cell }}</span
                  ><template v-else>{{
                    i === 7 && isOrders ? formatTime(cell) : cell
                  }}</template>
                </td>
                <td class="action-cell">
                  <button
                    class="row-action"
                    aria-label="查看详情"
                    @click.stop="
                      isOrders
                        ? openOrder(remoteOrders[ri])
                        : router.push(workflow.detail)
                    "
                  >
                    <MoreHorizontal :size="16" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <footer class="table-footer">
          <span class="table-total"
            >{{ hasSelectedFilters() ? `当前显示 ${rows.length} 条，` : "" }}共
            {{
              isOrders
                ? orderTotal
                : moduleKey[title]
                  ? moduleTotal
                  : config.total
            }}
            条记录</span
          >
          <div class="pagination" role="navigation" aria-label="列表分页">
            <div class="pagination-meta">
              <div class="pagination-size">
                <span>每页</span
                ><ToolbarSelect
                  class="pagination-size-select"
                  :model-value="String(pageSize)"
                  :options="pageSizeOptions"
                  aria-label="每页显示数量"
                  @update:model-value="changePageSize"
                />
              </div>
              <span class="pagination-summary"
                >第 {{ page }} / {{ currentPages }} 页</span
              >
            </div>
            <div class="pagination-pages">
              <button
                :disabled="page === 1"
                aria-label="上一页"
                @click="goPage(page - 1)"
              >
                上一页</button
              ><button
                v-for="item in visiblePages"
                :key="item"
                :disabled="typeof item === 'string'"
                :class="{
                  active: page === item,
                  ellipsis: typeof item === 'string',
                }"
                :aria-current="page === item ? 'page' : undefined"
                :aria-label="
                  typeof item === 'number' ? `第 ${item} 页` : undefined
                "
                @click="typeof item === 'number' && goPage(item)"
              >
                {{ typeof item === "string" ? "…" : item }}</button
              ><button
                :disabled="page === currentPages"
                aria-label="下一页"
                @click="goPage(page + 1)"
              >
                下一页
              </button>
            </div>
            <label class="pagination-jump"
              >跳转<input
                v-model="jumpPage"
                name="page"
                type="number"
                min="1"
                :max="currentPages"
                aria-label="跳转页码"
                @keyup.enter="jumpToPage"
              /><button @click="jumpToPage">确定</button></label
            >
          </div>
        </footer>
      </article>
      <aside v-if="isOrders" class="surface context-panel order-context">
        <TableState
          v-if="detailLoading"
          state="loading"
          title="正在加载订单详情"
          description="支付与履约信息正在同步。"
        /><template v-else-if="contextOrder"
          ><div class="context-heading">
            <span>ORDER DETAIL</span>
            <h2>订单 {{ contextOrder.order_no }}</h2>
            <p>
              {{ displayChannel(contextOrder.channel_type || "storefront") }} ·
              {{ formatTime(contextOrder.created_at) }} 创建
            </p>
          </div>
          <div class="context-score">
            <span>履约完成度</span><strong>{{ fulfillmentProgress }}%</strong
            ><i><b :style="{ width: fulfillmentProgress + '%' }"></b></i>
          </div>
          <div class="context-list">
            <button
              v-for="it in contextItems"
              :key="it.title"
              @click="inspectContextItem(it.title, it.meta)"
            >
              <span :data-tone="it.tone"></span>
              <div>
                <strong>{{ it.title }}</strong
                ><small>{{ it.meta }}</small>
              </div>
              <ArrowRight :size="15" />
            </button>
          </div>
          <button class="context-action" @click="openOrder(contextOrder)">
            查看完整订单<ArrowRight :size="15" /></button></template
        ><TableState
          v-else
          state="empty"
          title="尚未选择订单"
          description="从左侧列表选择一条订单查看支付和履约详情。"
        />
      </aside>
      <aside v-else class="surface context-panel">
        <div class="context-heading">
          <span>{{ displayPanel?.eyebrow || config.panelEyebrow }}</span
          ><span>{{ displayPanel?.title || config.panelTitle }}</span>
          <p>{{ moduleStats?.panel.description || config.panelDescription }}</p>
        </div>
        <div class="context-score">
          <span>{{ moduleStats?.panel.score_label || config.scoreLabel }}</span
          ><strong>{{ moduleStats?.panel.score || config.score }}</strong
          ><i
            ><b
              :style="{
                width:
                  (moduleStats?.panel.score_width || config.scoreWidth) + '%',
              }"
            ></b
          ></i>
        </div>
        <div class="context-list">
          <button
            v-for="it in moduleStats?.panel.items || config.panelItems"
            :key="it.title"
            @click="inspectContextItem(it.title, it.meta)"
          >
            <span :data-tone="it.tone"></span>
            <div>
              <strong>{{ it.title }}</strong
              ><small>{{ it.meta }}</small>
            </div>
            <ArrowRight :size="15" />
          </button>
        </div>
        <button class="context-action" @click="router.push(workflow.detail)">
          {{ config.panelAction }}<ArrowRight :size="15" />
        </button>
      </aside>
    </div>
  </section>
</template>
