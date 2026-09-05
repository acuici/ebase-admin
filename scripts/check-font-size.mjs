import { readFileSync, readdirSync, statSync } from 'node:fs'
import { extname, join, relative } from 'node:path'

const root = new URL('../src', import.meta.url).pathname
const files = []

function visit(directory) {
  for (const entry of readdirSync(directory)) {
    const path = join(directory, entry)
    if (statSync(path).isDirectory()) visit(path)
    else if (['.css', '.vue'].includes(extname(path))) files.push(path)
  }
}

visit(root)
const violations = []

for (const file of files) {
  const source = readFileSync(file, 'utf8')
  for (const declaration of source.matchAll(/(?:font-size|font)\s*:\s*([^;{}]+)/g)) {
    for (const size of declaration[1].matchAll(/(\d+(?:\.\d+)?)px/g)) {
      if (Number(size[1]) < 12) {
        const line = source.slice(0, declaration.index).split('\n').length
        violations.push(`${relative(process.cwd(), file)}:${line} 使用了 ${size[1]}px`)
      }
    }
  }
}

if (violations.length) {
  console.error('检测到小于 12px 的字体，违反全站可读性约束：')
  console.error(violations.join('\n'))
  process.exit(1)
}

console.log('字体检查通过：所有显式字号均不小于 12px')
