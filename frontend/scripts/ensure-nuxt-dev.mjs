import { mkdir } from 'node:fs/promises'
import { resolve } from 'node:path'

const root = process.cwd()

await mkdir(resolve(root, '.nuxt'), { recursive: true })
await mkdir(resolve(root, '.nuxt/dev'), { recursive: true })
await mkdir(resolve(root, '.output'), { recursive: true })
