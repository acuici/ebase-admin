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
  for (const match of source.matchAll(/<option\b[^>]*>/gs)) {
    if (!/\s(?:value|:value)=/.test(match[0])) {
      const line = source.slice(0, match.index).split("\n").length;
      violations.push(`${relative(root, file)}:${line} option 缺少显式 value`);
    }
    const staticValue = match[0].match(/\svalue="([^"]*)"/)?.[1] ?? "";
    if (/[^\x00-\x7F]/.test(staticValue)) {
      const line = source.slice(0, match.index).split("\n").length;
      violations.push(
        `${relative(root, file)}:${line} option value 不得使用中文显示文本`,
      );
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
