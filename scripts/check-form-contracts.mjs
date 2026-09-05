import { readFileSync, readdirSync, statSync } from "node:fs";
import { join, relative } from "node:path";

const root = new URL("../", import.meta.url).pathname;
const sourceRoot = join(root, "src");
const files = [];

function walk(directory) {
  for (const entry of readdirSync(directory)) {
    const path = join(directory, entry);
    if (statSync(path).isDirectory()) walk(path);
    else if (path.endsWith(".vue")) files.push(path);
  }
}

walk(sourceRoot);
const violations = [];
for (const file of files) {
  const source = readFileSync(file, "utf8");
  for (const match of source.matchAll(/<(input|select|textarea)\b[^>]*>/gs)) {
    if (!/\s(?:name|:name)=/.test(match[0])) {
      const line = source.slice(0, match.index).split("\n").length;
      violations.push(`${relative(root, file)}:${line} 缺少稳定的 name 字段`);
    }
  }
}

if (violations.length) {
  console.error(
    `表单契约检查失败（${violations.length} 项）：\n${violations.join("\n")}`,
  );
  process.exit(1);
}

console.log(`表单契约检查通过：${files.length} 个 Vue 文件。`);
