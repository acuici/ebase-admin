const fieldNames: Record<string, string> = {
  站点名称: "site_name",
  主域名: "primary_domain",
  语言: "locale",
  币种: "currency",
  主题: "theme",
  最近发布: "last_published_at",
  状态: "status",
  角色名称: "role_name",
  角色描述: "role_description",
  企业名称: "company_name",
  企业简称: "company_short_name",
  统一社会信用代码: "unified_social_credit_code",
  所属行业: "industry",
  企业地址: "company_address",
};

function stableHash(value: string): string {
  let hash = 2166136261;
  for (const character of value) {
    hash ^= character.codePointAt(0) ?? 0;
    hash = Math.imul(hash, 16777619);
  }
  return (hash >>> 0).toString(36);
}

/**
 * 将本地化显示标签转换为稳定、只含 ASCII 的表单字段名。
 * 新增后端字段时应优先补充显式映射；哈希仅用于尚未接入 API 的原型字段。
 */
export function formFieldName(label: string, scope = "field"): string {
  return fieldNames[label] ?? `${scope}_${stableHash(label)}`;
}
