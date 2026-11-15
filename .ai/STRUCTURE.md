# .ai/ — AI Context Architecture

## Purpose

This folder is the single source of truth for AI-assisted development context. It gives the same guidance to every coding tool. Conventions live here, not in tool-specific rule files.

## Why centralized?

Each tool has its own configuration format. One context file per tool drifts. This folder holds the context. Pointer files only say where to read it.

## Folder structure

```
.ai/
├── CONTEXT.md                  # Project-wide rules and the index of component contexts
├── STRUCTURE.md                # This file
└── Component/
    └── Module/
        └── CONTEXT.md          # Patterns that exist only in this payment module
```

There is no `Domain/` tree and no copy of PrestaShop core `src/Core`. Core domain and component contexts stay upstream: [PrestaShop/PrestaShop branch 9.2.x](https://github.com/PrestaShop/PrestaShop/tree/9.2.x/.ai).

## File conventions

| File | Purpose | Target size |
|------|---------|-------------|
| `CONTEXT.md` | Conventions, patterns, do/don't rules for a domain or component | < 200 lines |
| `STRUCTURE.md` | This file — architecture documentation | N/A |

### CONTEXT.md template

Every component `CONTEXT.md` follows this structure:

```markdown
# {Domain or Component Name}

## Purpose
[1-2 sentences: what this domain/component does, what it does NOT do]

## Layers
[Table: Layer name | Path — paths only, no class inventories]

## Non-obvious patterns
[Bullet points for things that aren't discoverable by reading the directory structure]

## Canonical examples
- [File path — 1-line description]

## Related
- [Links to related domains, components, or documentation]
```

Do not list class inventories. Do not repeat the coding standards from the root `CONTEXT.md`.

## How an AI agent should navigate this structure

1. Start with `.ai/CONTEXT.md`.
2. Identify the relevant component from the "Which context to read" table.
3. Read that `CONTEXT.md` only when the files being edited match it.
4. For PrestaShop core areas this repository does not vendor (`src/Core/Domain`, Admin API, grids), follow the upstream link in `.ai/CONTEXT.md` instead of expecting a local copy.
