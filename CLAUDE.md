# PriceBlueprint plugin

## Specialist subagents

This repo defines three subagents for WooCommerce/WordPress development work
(see `.claude/agents/`):

| Agent | Model | Use for |
|---|---|---|
| `pb-swift` | Haiku | Quick lookups, searches, small edits (≤3 files), commit messages, docs |
| `pb-senior` | Sonnet | Standard WP/WooCommerce engineering: bug fixes, feature work |
| `pb-principal` | Opus | Architecture decisions, large refactors, complex debugging, security review |

**Default to pb-senior** for most development tasks.
**Use pb-swift** when the task is clearly read-only or trivially small.
**Escalate to pb-principal** when: root cause is unclear after a quick look, the
change touches more than 5 files, or the task is architecture/performance/security
related.

This routing table replaces what used to be a separate "pb-developer" orchestrator
agent in OpenClaw — pick the right specialist directly using the rules above rather
than delegating the decision to another agent.
