#!/usr/bin/env node
/**
 * MCP Server — Laravel + Vue Template
 *
 * Expõe ferramentas para IAs navegarem o projeto:
 *   - project_overview   → visão geral da stack e convenções
 *   - list_routes        → todas as rotas Laravel
 *   - list_models        → models e relacionamentos
 *   - list_controllers   → controllers e métodos
 *   - list_vue_pages     → páginas Vue e componentes
 *   - get_auth_flow      → fluxo completo de autenticação
 *   - read_file          → lê qualquer arquivo do projeto
 *   - search_code        → busca texto em arquivos do projeto
 *
 * Uso (stdio):
 *   node dist/index.js
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js'
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js'
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js'
import { execSync } from 'child_process'
import * as fs from 'fs'
import * as path from 'path'

// Root do projeto (dois níveis acima de mcp/server/)
const PROJECT_ROOT = path.resolve(import.meta.dirname, '..', '..')

function readDoc(name: string): string {
  const file = path.join(PROJECT_ROOT, 'mcp', `${name}.md`)
  return fs.existsSync(file) ? fs.readFileSync(file, 'utf-8') : `[Arquivo ${name}.md não encontrado]`
}

function readProjectFile(relativePath: string): string {
  const abs = path.join(PROJECT_ROOT, relativePath)
  if (!fs.existsSync(abs)) return `[Arquivo não encontrado: ${relativePath}]`
  const stat = fs.statSync(abs)
  if (stat.size > 500_000) return `[Arquivo muito grande para leitura inline: ${relativePath} (${stat.size} bytes)]`
  return fs.readFileSync(abs, 'utf-8')
}

function searchCode(pattern: string, dir = '.', ext = ''): string {
  try {
    const extFlag = ext ? `--include="*.${ext}"` : ''
    const cmd = `grep -rn --color=never ${extFlag} -e "${pattern.replace(/"/g, '\\"')}" "${path.join(PROJECT_ROOT, dir)}" --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=.git --exclude-dir=dist 2>/dev/null | head -60`
    return execSync(cmd, { encoding: 'utf-8' }) || '(sem resultados)'
  } catch {
    return '(sem resultados)'
  }
}

function listRoutes(): string {
  const web = readProjectFile('routes/web.php')
  const api = readProjectFile('routes/api.php')
  const settings = readProjectFile('routes/settings.php')
  return `=== routes/web.php ===\n${web}\n\n=== routes/api.php ===\n${api}\n\n=== routes/settings.php ===\n${settings}`
}

function listPhpFiles(dir: string): string[] {
  const abs = path.join(PROJECT_ROOT, dir)
  if (!fs.existsSync(abs)) return []
  const results: string[] = []
  for (const entry of fs.readdirSync(abs, { withFileTypes: true })) {
    const full = path.join(dir, entry.name)
    if (entry.isDirectory()) results.push(...listPhpFiles(full))
    else if (entry.name.endsWith('.php')) results.push(full)
  }
  return results
}

function listVueFiles(dir: string): string[] {
  const abs = path.join(PROJECT_ROOT, dir)
  if (!fs.existsSync(abs)) return []
  const results: string[] = []
  for (const entry of fs.readdirSync(abs, { withFileTypes: true })) {
    const full = path.join(dir, entry.name)
    if (entry.isDirectory()) results.push(...listVueFiles(full))
    else if (entry.name.endsWith('.vue') || entry.name.endsWith('.ts')) results.push(full)
  }
  return results
}

// ─── Server definition ────────────────────────────────────────────────────────

const server = new Server(
  { name: 'laravel-vue-template', version: '1.0.0' },
  { capabilities: { tools: {} } },
)

// ─── List available tools ─────────────────────────────────────────────────────

server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: 'project_overview',
      description:
        'Retorna a visão geral do projeto: stack, estrutura de pastas, convenções e fluxo de autenticação.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
    {
      name: 'list_routes',
      description: 'Lista todas as rotas Laravel (web.php, api.php, settings.php) com middlewares.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
    {
      name: 'list_models',
      description: 'Lista os models PHP do projeto com traits, fillable e relacionamentos.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
    {
      name: 'list_controllers',
      description: 'Lista os controllers PHP com seus métodos públicos.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
    {
      name: 'list_vue_pages',
      description: 'Lista as páginas Vue em resources/js/pages/ e componentes em resources/js/components/.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
    {
      name: 'get_auth_flow',
      description:
        'Descreve o fluxo completo de autenticação: Fortify (sessão) + Sanctum (Bearer token) + SPA.',
      inputSchema: { type: 'object', properties: {}, required: [] },
    },
    {
      name: 'read_file',
      description: 'Lê o conteúdo de um arquivo do projeto (relativo à raiz).',
      inputSchema: {
        type: 'object',
        properties: {
          path: {
            type: 'string',
            description: 'Caminho relativo à raiz do projeto. Ex: "app/Models/User.php"',
          },
        },
        required: ['path'],
      },
    },
    {
      name: 'search_code',
      description: 'Busca um padrão de texto nos arquivos do projeto (grep).',
      inputSchema: {
        type: 'object',
        properties: {
          pattern: {
            type: 'string',
            description: 'Texto ou regex a buscar.',
          },
          dir: {
            type: 'string',
            description: 'Diretório de busca relativo à raiz. Padrão: "." (todo o projeto).',
          },
          ext: {
            type: 'string',
            description: 'Extensão de arquivo para filtrar, sem ponto. Ex: "php" ou "vue".',
          },
        },
        required: ['pattern'],
      },
    },
  ],
}))

// ─── Tool handlers ────────────────────────────────────────────────────────────

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params
  const a = (args ?? {}) as Record<string, string>

  switch (name) {
    // ── project_overview ──────────────────────────────────────────────────────
    case 'project_overview': {
      const readme = readDoc('README')
      const estrutura = readDoc('estrutura')
      return {
        content: [
          {
            type: 'text',
            text: `# Visão Geral do Projeto\n\n${readme}\n\n---\n\n${estrutura}`,
          },
        ],
      }
    }

    // ── list_routes ───────────────────────────────────────────────────────────
    case 'list_routes': {
      return {
        content: [
          {
            type: 'text',
            text: `# Rotas do Projeto\n\n${readDoc('rotas')}\n\n---\n\n## Código-fonte atual\n\n${listRoutes()}`,
          },
        ],
      }
    }

    // ── list_models ───────────────────────────────────────────────────────────
    case 'list_models': {
      const files = listPhpFiles('app/Models')
      const content = files
        .map((f) => `### ${f}\n\`\`\`php\n${readProjectFile(f)}\n\`\`\``)
        .join('\n\n')
      return {
        content: [{ type: 'text', text: `# Models\n\n${content}` }],
      }
    }

    // ── list_controllers ──────────────────────────────────────────────────────
    case 'list_controllers': {
      const files = listPhpFiles('app/Http/Controllers')
      const content = files
        .map((f) => `### ${f}\n\`\`\`php\n${readProjectFile(f)}\n\`\`\``)
        .join('\n\n')
      return {
        content: [{ type: 'text', text: `# Controllers\n\n${readDoc('controllers_models')}\n\n---\n\n## Código-fonte atual\n\n${content}` }],
      }
    }

    // ── list_vue_pages ────────────────────────────────────────────────────────
    case 'list_vue_pages': {
      const pages = listVueFiles('resources/js/pages')
      const components = listVueFiles('resources/js/components')
      const utils = listVueFiles('resources/js/utils')

      const fmt = (files: string[]) =>
        files.map((f) => `  - \`${f}\``).join('\n')

      return {
        content: [
          {
            type: 'text',
            text: [
              `# Páginas e Componentes Vue\n`,
              readDoc('paginas_vue'),
              `\n---\n\n## Arquivos atuais\n\n### Páginas\n${fmt(pages)}`,
              `\n### Componentes\n${fmt(components)}`,
              `\n### Utils\n${fmt(utils)}`,
            ].join('\n'),
          },
        ],
      }
    }

    // ── get_auth_flow ─────────────────────────────────────────────────────────
    case 'get_auth_flow': {
      return {
        content: [
          {
            type: 'text',
            text: readDoc('middlewares'),
          },
        ],
      }
    }

    // ── read_file ─────────────────────────────────────────────────────────────
    case 'read_file': {
      if (!a.path) {
        return { content: [{ type: 'text', text: 'Erro: parâmetro "path" obrigatório.' }] }
      }
      // Prevent path traversal outside project
      const abs = path.resolve(PROJECT_ROOT, a.path)
      if (!abs.startsWith(PROJECT_ROOT)) {
        return { content: [{ type: 'text', text: 'Acesso negado: caminho fora do projeto.' }] }
      }
      return {
        content: [
          {
            type: 'text',
            text: `### ${a.path}\n\`\`\`\n${readProjectFile(a.path)}\n\`\`\``,
          },
        ],
      }
    }

    // ── search_code ───────────────────────────────────────────────────────────
    case 'search_code': {
      if (!a.pattern) {
        return { content: [{ type: 'text', text: 'Erro: parâmetro "pattern" obrigatório.' }] }
      }
      const result = searchCode(a.pattern, a.dir ?? '.', a.ext ?? '')
      return {
        content: [
          {
            type: 'text',
            text: `# Resultados para: "${a.pattern}"\n\n\`\`\`\n${result}\n\`\`\``,
          },
        ],
      }
    }

    default:
      return {
        content: [{ type: 'text', text: `Ferramenta desconhecida: ${name}` }],
        isError: true,
      }
  }
})

// ─── Start ────────────────────────────────────────────────────────────────────

const transport = new StdioServerTransport()
await server.connect(transport)
