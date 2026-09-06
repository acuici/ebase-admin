<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  ArrowLeft,
  Check,
  Eye,
  MoreHorizontal,
  Pencil,
  Plus,
  Save,
  Search,
  Trash2,
  X,
} from "lucide-vue-next";
import { useToast } from "../composables/useToast";
import {
  listStorefrontSites,
  createStorefrontSite,
  updateStorefrontSite,
  type StorefrontSite,
} from "../api/storefront";
import ToolbarSelect from "../components/common/ToolbarSelect.vue";
import TableState from "../components/common/TableState.vue";
import { formFieldName, formOptionValue } from "../utils/formFields";
type Config = {
  title: string;
  eyebrow: string;
  description: string;
  action: string;
  columns: string[];
  rows: string[][];
  metrics: [string, string, string][];
};
const props = defineProps<{ section: string; mode?: string }>();
const route = useRoute();
const router = useRouter();
const { success } = useToast();
const query = ref(String(route.query.keyword || ""));
const statusFilter = ref(String(route.query.status || ""));
const storefrontStatusOptions = [
  { label: "草稿", value: "draft" },
  { label: "待配置", value: "pending_configuration" },
  { label: "已发布", value: "published" },
  { label: "正常", value: "active" },
  { label: "运行中", value: "running" },
  { label: "维护中", value: "maintenance" },
];
const storefrontStatusCode = (value: string) =>
  storefrontStatusOptions.find((option) => option.label === value)?.value ??
  value;
const storefrontStatusLabel = (value: string) =>
  storefrontStatusOptions.find((option) => option.value === value)?.label ??
  value;
const drawer = ref(false);
const editing = ref(-1);
const form = ref<string[]>([]);
const validation = ref("");
const configs: Record<string, Config> = {
  sites: {
    title: "站点管理",
    eyebrow: "STOREFRONTS · 独立站",
    description: "管理站点品牌、语言、币种、时区与经营状态。",
    action: "新建站点",
    columns: ["站点名称", "主域名", "语言", "币种", "主题", "最近发布", "状态"],
    rows: [
      [
        "中国大陆站",
        "shop.ebase.cn",
        "简体中文",
        "CNY",
        "Clarity 1.2",
        "今天 10:28",
        "运行中",
      ],
      [
        "全球体验站",
        "global.ebase.cn",
        "English",
        "USD",
        "Clarity 1.2",
        "尚未发布",
        "草稿",
      ],
    ],
    metrics: [
      ["全部站点", "2", "1 个运行中"],
      ["今日访客", "28,642", "+16.8%"],
      ["平均转化率", "4.8%", "+0.6%"],
      ["待发布变更", "18", "需要处理"],
    ],
  },
  domains: {
    title: "域名管理",
    eyebrow: "DOMAINS · 网络入口",
    description: "绑定自有域名并检查 DNS、HTTPS 与访问状态。",
    action: "绑定域名",
    columns: [
      "域名",
      "关联站点",
      "类型",
      "DNS 状态",
      "HTTPS",
      "到期时间",
      "状态",
    ],
    rows: [
      [
        "shop.ebase.cn",
        "中国大陆站",
        "主域名",
        "已验证",
        "已启用",
        "自动续期",
        "正常",
      ],
      [
        "www.ebase.cn",
        "中国大陆站",
        "重定向",
        "已验证",
        "已启用",
        "自动续期",
        "正常",
      ],
      [
        "global.ebase.cn",
        "全球体验站",
        "主域名",
        "待验证",
        "配置中",
        "—",
        "待配置",
      ],
    ],
    metrics: [
      ["已绑定域名", "3", "2 个已验证"],
      ["HTTPS 覆盖", "100%", "已启用"],
      ["解析异常", "1", "等待配置"],
      ["证书到期", "0", "未来 30 天"],
    ],
  },
  navigation: {
    title: "导航菜单",
    eyebrow: "NAVIGATION · 信息架构",
    description: "维护页头、页脚和移动端导航结构。",
    action: "新建菜单",
    columns: [
      "菜单名称",
      "展示位置",
      "菜单项",
      "关联站点",
      "最近更新",
      "负责人",
      "状态",
    ],
    rows: [
      ["主导航", "页头", "8", "中国大陆站", "今天 09:42", "苏然", "已发布"],
      [
        "服务与支持",
        "页脚",
        "6",
        "中国大陆站",
        "昨天 16:08",
        "林知夏",
        "已发布",
      ],
      ["Global Header", "页头", "5", "全球体验站", "08-28", "陈曦", "草稿"],
    ],
    metrics: [
      ["菜单组", "6", "18 个菜单项"],
      ["已发布", "4", "运行正常"],
      ["草稿", "2", "等待发布"],
      ["失效链接", "1", "需要修复"],
    ],
  },
  pages: {
    title: "页面与专题",
    eyebrow: "PAGES · 内容发布",
    description: "创建自定义页面、活动专题并管理发布版本。",
    action: "新建页面",
    columns: [
      "页面标题",
      "页面类型",
      "URL Slug",
      "关联站点",
      "版本",
      "更新时间",
      "状态",
    ],
    rows: [
      [
        "关于清透商业",
        "品牌页面",
        "about-us",
        "中国大陆站",
        "v6",
        "今天 10:16",
        "已发布",
      ],
      [
        "中秋礼赠季",
        "活动专题",
        "mid-autumn-2026",
        "中国大陆站",
        "v3",
        "今天 09:48",
        "审核中",
      ],
      [
        "Shipping Policy",
        "政策页面",
        "shipping-policy",
        "全球体验站",
        "v1",
        "08-30",
        "草稿",
      ],
    ],
    metrics: [
      ["全部页面", "24", "18 个已发布"],
      ["活动专题", "6", "2 个进行中"],
      ["待审核", "3", "需要处理"],
      ["本周浏览", "186,420", "+24.6%"],
    ],
  },
  theme: {
    title: "主题装修",
    eyebrow: "THEME EDITOR · 视觉呈现",
    description: "配置首页区块、页头页脚与全站视觉主题。",
    action: "新增页面区块",
    columns: [
      "区块名称",
      "区块类型",
      "展示页面",
      "排序",
      "可见范围",
      "最近修改",
      "状态",
    ],
    rows: [
      [
        "首屏品牌横幅",
        "Hero Banner",
        "首页",
        "1",
        "全部访客",
        "今天 10:06",
        "已发布",
      ],
      ["本周新品", "商品集合", "首页", "2", "全部访客", "今天 09:22", "已发布"],
      ["会员专享", "权益区块", "首页", "3", "登录会员", "昨天 18:16", "草稿"],
      ["品牌故事", "图文内容", "首页", "4", "全部访客", "08-30", "已发布"],
    ],
    metrics: [
      ["当前主题", "Clarity 1.2", "已发布"],
      ["首页区块", "8", "1 个草稿"],
      ["全局组件", "12", "页头与页脚"],
      ["待发布变更", "4", "上次发布 2h 前"],
    ],
  },
  seo: {
    title: "SEO 设置",
    eyebrow: "SEARCH DISCOVERY · 自然流量",
    description: "管理默认元信息、索引策略和商品结构化数据。",
    action: "新建重定向",
    columns: [
      "规则名称",
      "规则类型",
      "匹配范围",
      "目标值",
      "关联站点",
      "最近检测",
      "状态",
    ],
    rows: [
      [
        "商品标题模板",
        "Meta Title",
        "商品详情",
        "{商品名} | EBASE",
        "中国大陆站",
        "今天 06:00",
        "正常",
      ],
      [
        "商品结构化数据",
        "JSON-LD",
        "商品详情",
        "Product + Offer",
        "全部站点",
        "今天 06:00",
        "正常",
      ],
      [
        "旧活动页跳转",
        "301 Redirect",
        "/sale-2025",
        "/campaigns",
        "中国大陆站",
        "昨天",
        "正常",
      ],
      [
        "全球站 Sitemap",
        "Sitemap",
        "全站",
        "自动生成",
        "全球体验站",
        "08-30",
        "待发布",
      ],
    ],
    metrics: [
      ["SEO 完整度", "92%", "+8% 本月"],
      ["已收录页面", "2,846", "搜索引擎"],
      ["结构化数据", "98.6%", "校验通过"],
      ["失效链接", "6", "需要修复"],
    ],
  },
};
const site = reactive({
  name: props.mode === "edit" ? "中国大陆站" : "",
  code: props.mode === "edit" ? "cn-main" : "",
  domain: props.mode === "edit" ? "shop.ebase.cn" : "",
  language: "zh-CN",
  currency: "CNY",
  timezone: "Asia/Shanghai",
  status: props.mode === "edit" ? "active" : "draft",
  brand: "EBASE 清透商业",
  email: "service@ebase.cn",
  seoTitle: "EBASE 清透商业｜品质生活精选",
  seoDescription: "探索数码、家居与生活方式好物，享受可靠配送和会员专属服务。",
});
const isEditor = computed(() => props.section === "site-editor");
const config = computed(() => configs[props.section]);
const remoteSites = ref<StorefrontSite[]>([]);
const loadingSites = ref(false);
const sitesLoaded = ref(false);
async function loadSites() {
  if (props.section !== "sites") return;
  loadingSites.value = true;
  try {
    const data = await listStorefrontSites({
      page: 1,
      page_size: 100,
      keyword: query.value || undefined,
      status: statusFilter.value || undefined,
    });
    remoteSites.value = data.items;
    sitesLoaded.value = true;
  } finally {
    loadingSites.value = false;
  }
}
onMounted(() => void loadSites());
const sourceRows = computed(() =>
  props.section === "sites" && sitesLoaded.value
    ? remoteSites.value.map((site) => [
        site.name,
        site.site_code,
        site.default_locale,
        site.currency,
        "—",
        "—",
        site.status,
      ])
    : config.value?.rows || [],
);
const statusOptions = computed(() => {
  if (props.section === "sites")
    return [
      { label: "全部状态", value: "" },
      ...storefrontStatusOptions.filter((option) =>
        ["draft", "active", "maintenance", "disabled"].includes(option.value),
      ),
    ];
  return [
    { label: "全部状态", value: "" },
    ...Array.from(
      new Set(sourceRows.value.map((row) => row.at(-1) || "").filter(Boolean)),
    ).map((value) => ({
      label: storefrontStatusLabel(storefrontStatusCode(value)),
      value: storefrontStatusCode(value),
    })),
  ];
});
const rows = computed(() => {
  if (props.section === "sites") return sourceRows.value;
  const key = query.value.trim().toLowerCase();
  return sourceRows.value.filter(
    (row) =>
      (!key || row.join(" ").toLowerCase().includes(key)) &&
      (!statusFilter.value ||
        storefrontStatusCode(row.at(-1) || "") === statusFilter.value),
  );
});
watch(
  () => props.section,
  () => {
    query.value = "";
    statusFilter.value = "";
    drawer.value = false;
  },
);
watch([query, statusFilter], () => {
  void router.replace({
    query: {
      ...route.query,
      keyword: query.value || undefined,
      status: statusFilter.value || undefined,
    },
  });
  if (props.section === "sites") void loadSites();
});
function openCreate() {
  if (props.section === "sites") {
    router.push("/storefront/sites/new");
    return;
  }
  editing.value = -1;
  validation.value = "";
  form.value = config.value.columns.map((column, index) =>
    index === config.value.columns.length - 1 ? "draft" : "",
  );
  drawer.value = true;
}
function openEdit(row: string[]) {
  editing.value = config.value.rows.indexOf(row);
  form.value = [...row];
  form.value[form.value.length - 1] = String(
    formOptionValue(form.value.at(-1) || ""),
  );
  validation.value = "";
  drawer.value = true;
}
function saveRow() {
  if (!form.value[0]?.trim()) {
    validation.value = `${config.value.columns[0]}不能为空`;
    return;
  }
  if (editing.value >= 0) config.value.rows[editing.value] = [...form.value];
  else config.value.rows.unshift([...form.value]);
  drawer.value = false;
  success(editing.value >= 0 ? "修改已保存" : "记录已创建", form.value[0]);
}
function removeRow(index: number) {
  const row = config.value.rows[index];
  config.value.rows.splice(index, 1);
  success("记录已删除", row[0]);
}
function saveSite() {
  if (!site.name || !site.code) {
    return;
  }
  const payload = {
    name: site.name,
    site_code: site.code,
    brand_name: site.brand,
    service_email: site.email,
    default_locale: site.language,
    currency: site.currency,
    timezone: site.timezone,
    status: site.status,
    default_seo_title: site.seoTitle,
    default_seo_description: site.seoDescription,
  };
  const request =
    props.mode === "create"
      ? createStorefrontSite(payload)
      : updateStorefrontSite(String(route.params.id || ""), payload);
  request.then(() => {
    success(
      props.mode === "create" ? "独立站已创建" : "站点设置已保存",
      site.name,
    );
    if (props.mode === "create") router.push("/storefront/sites");
  });
}
</script>
<template>
  <section v-if="isEditor" class="data-page storefront-editor">
    <button class="editor-back" @click="router.push('/storefront/sites')">
      <ArrowLeft :size="15" />返回站点管理
    </button>
    <div class="page-heading">
      <div>
        <span class="eyebrow">{{
          mode === "create"
            ? "NEW STOREFRONT · 创建渠道"
            : "SITE SETTINGS · CN-MAIN"
        }}</span>
        <h1>{{ mode === "create" ? "新建独立站" : "中国大陆站" }}</h1>
        <p>配置站点身份、区域规则、品牌信息和默认 SEO。</p>
      </div>
      <div class="heading-actions">
        <button
          class="button secondary"
          @click="router.push('/storefront/sites')"
        >
          取消</button
        ><button class="button primary" @click="saveSite">
          <Save :size="15" />{{ mode === "create" ? "创建站点" : "保存设置" }}
        </button>
      </div>
    </div>
    <div class="storefront-editor-layout">
      <main>
        <article class="surface editor-section">
          <header>
            <div>
              <h2>站点基础信息</h2>
              <p>站点代码创建后不可修改，用于订单和渠道数据识别。</p>
            </div>
          </header>
          <div class="editor-fields">
            <label
              ><span>站点名称</span
              ><input
                v-model="site.name"
                name="site_name"
                placeholder="例如：中国大陆站" /></label
            ><label
              ><span>站点代码</span
              ><input
                v-model="site.code"
                name="site_code"
                :disabled="mode === 'edit'"
                placeholder="例如：cn-main" /></label
            ><label
              ><span>默认语言</span
              ><select v-model="site.language" name="default_locale">
                <option value="zh-CN">简体中文</option>
                <option value="en-US">English</option>
              </select></label
            ><label
              ><span>默认币种</span
              ><select v-model="site.currency" name="currency">
                <option value="CNY">人民币 CNY</option>
                <option value="USD">美元 USD</option>
              </select></label
            ><label
              ><span>默认时区</span
              ><select v-model="site.timezone" name="timezone">
                <option value="Asia/Shanghai">Asia/Shanghai (UTC+8)</option>
                <option value="UTC">UTC</option>
              </select></label
            ><label
              ><span>站点状态</span
              ><select v-model="site.status" name="status">
                <option value="draft">草稿</option>
                <option value="active">运行中</option>
                <option value="maintenance">维护中</option>
              </select></label
            >
          </div>
        </article>
        <article class="surface editor-section">
          <header>
            <div>
              <h2>品牌与客户服务</h2>
              <p>用于站点页头、邮件通知和客户服务入口。</p>
            </div>
          </header>
          <div class="editor-fields">
            <label
              ><span>品牌名称</span
              ><input v-model="site.brand" name="brand_name" /></label
            ><label
              ><span>客服邮箱</span
              ><input
                v-model="site.email"
                name="support_email"
                type="email" /></label
            ><label class="field-wide"
              ><span>默认域名</span
              ><input
                v-model="site.domain"
                name="primary_domain"
                placeholder="shop.example.com"
              /><small
                >保存后仍需在域名管理完成 DNS 和 HTTPS 验证。</small
              ></label
            >
          </div>
        </article>
        <article class="surface editor-section">
          <header>
            <div>
              <h2>默认 SEO</h2>
              <p>没有单独配置的页面将继承这些元信息。</p>
            </div>
          </header>
          <div class="editor-fields">
            <label class="field-wide"
              ><span>默认标题</span
              ><input v-model="site.seoTitle" name="seo_title" /></label
            ><label class="field-wide"
              ><span>默认描述</span
              ><textarea
                v-model="site.seoDescription"
                name="seo_description"
              ></textarea>
            </label>
          </div>
        </article>
      </main>
      <aside class="surface editor-summary">
        <span class="site-preview-mark">{{ site.name?.[0] || "站" }}</span>
        <h3>{{ site.name || "未命名站点" }}</h3>
        <p>{{ site.domain || "尚未绑定域名" }}</p>
        <dl>
          <div>
            <dt>语言</dt>
            <dd>{{ site.language }}</dd>
          </div>
          <div>
            <dt>币种</dt>
            <dd>{{ site.currency }}</dd>
          </div>
          <div>
            <dt>状态</dt>
            <dd>{{ site.status }}</dd>
          </div>
          <div>
            <dt>主题</dt>
            <dd>Clarity 1.2</dd>
          </div>
        </dl>
      </aside>
    </div>
  </section>
  <section v-else-if="config" class="data-page storefront-section">
    <button class="editor-back" @click="router.push('/storefront')">
      <ArrowLeft :size="15" />返回独立站控制中心
    </button>
    <div class="page-heading">
      <div>
        <span class="eyebrow">{{ config.eyebrow }}</span>
        <h1>{{ config.title }}</h1>
        <p>{{ config.description }}</p>
      </div>
      <button class="button primary" @click="openCreate">
        <Plus :size="15" />{{ config.action }}
      </button>
    </div>
    <div class="metric-strip storefront-section-metrics">
      <div
        v-for="metric in config.metrics"
        :key="metric[0]"
        class="metric-item"
      >
        <span>{{ metric[0] }}</span
        ><strong>{{ metric[1] }}</strong
        ><small
          ><b>{{ metric[2] }}</b></small
        >
      </div>
    </div>
    <article class="surface storefront-data">
      <header>
        <div class="storefront-table-filters">
          <label class="module-search"
            ><Search :size="16" /><input
              v-model="query"
              name="keyword"
              :placeholder="`搜索${config.title}`"
          /></label>
          <ToolbarSelect
            v-model="statusFilter"
            label="状态"
            :options="statusOptions"
            aria-label="记录状态"
          />
        </div>
        <span>显示 {{ rows.length }} / {{ sourceRows.length }} 条记录</span>
      </header>
      <TableState
        v-if="loadingSites"
        state="loading"
        title="正在加载站点"
        description="正在同步独立站配置。"
      />
      <TableState
        v-else-if="!rows.length"
        state="empty"
        :filtered="Boolean(query || statusFilter)"
        title="没有匹配的记录"
        description="调整关键词或状态筛选后再试。"
      >
        <template #action
          ><button
            class="button secondary"
            @click="
              query = '';
              statusFilter = '';
            "
          >
            清除筛选
          </button></template
        >
      </TableState>
      <div v-else class="table-scroll">
        <table>
          <thead>
            <tr>
              <th v-for="column in config.columns">{{ column }}</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows">
              <td
                v-for="(cell, index) in row"
                :class="{ 'strong-cell': index === 0 }"
              >
                <span
                  v-if="index === row.length - 1"
                  class="status-tag"
                  :data-status="cell"
                  >{{ cell }}</span
                ><template v-else>{{ cell }}</template>
              </td>
              <td class="storefront-row-actions">
                <button aria-label="预览"><Eye :size="14" /></button
                ><button aria-label="编辑" @click="openEdit(row)">
                  <Pencil :size="14" /></button
                ><button
                  aria-label="删除"
                  @click="removeRow(config.rows.indexOf(row))"
                >
                  <Trash2 :size="14" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </article>
    <Teleport to="body"
      ><div v-if="drawer" class="crud-overlay" @click.self="drawer = false">
        <aside class="crud-drawer">
          <header>
            <div>
              <span>{{ editing >= 0 ? "EDIT" : "CREATE" }}</span>
              <h2>{{ editing >= 0 ? "编辑记录" : config.action }}</h2>
              <p>{{ config.title }}</p>
            </div>
            <button aria-label="关闭" @click="drawer = false">
              <X :size="18" />
            </button>
          </header>
          <div class="crud-form">
            <div v-if="validation" class="crud-error">{{ validation }}</div>
            <label v-for="(column, index) in config.columns"
              ><span>{{ column }} <b v-if="index === 0">*</b></span
              ><select
                v-if="index === config.columns.length - 1"
                v-model="form[index]"
                :name="formFieldName(column, props.section)"
              >
                <option value="draft">草稿</option>
                <option value="pending_configuration">待配置</option>
                <option value="published">已发布</option>
                <option value="active">正常</option>
                <option value="running">运行中</option></select
              ><input
                v-else
                v-model="form[index]"
                :name="formFieldName(column, props.section)"
            /></label>
          </div>
          <footer>
            <span></span
            ><button class="button secondary" @click="drawer = false">
              取消</button
            ><button class="button primary" @click="saveRow">
              <Check :size="14" />保存
            </button>
          </footer>
        </aside>
      </div></Teleport
    >
  </section>
</template>
