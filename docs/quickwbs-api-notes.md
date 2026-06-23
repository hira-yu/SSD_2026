# QuickWBS API Notes

- 更新日時: 2026-06-23 17:22:14
- API Base URL: https://quickwbs.hirayu.jp/api
- Agent Docs URL: https://quickwbs.hirayu.jp/api/agent/docs
- 取得状態: 成功
- 形式: agent-docs-json
- API 名: QuickWBS Agent API
- バージョン: 不明

## Agent Identity

- Agent名: MCP_Codex
- Owner: Choco2807
- Actor Label: Choco2807 のAI (MCP_Codex)

## 取得できたドキュメント

- api: Quick WBS API
- agent-guide: AI Agent Guide

## 判明したAPIエンドポイント

- agent_docs: `GET /api/agent/docs`
- agent_me: `GET /api/agent/me`
- task_list: `GET /api/agent/tasks/available`
- task_context: `GET /api/agent/tasks/{task_id}/context`
- task_create: `POST /api/agent/tasks/{task_id}/children`
- task_update:
  - claim: `POST /api/agent/tasks/{task_id}/claim`
  - start: `POST /api/agent/tasks/{task_id}/start`
  - block: `POST /api/agent/tasks/{task_id}/block`
  - complete: `POST /api/agent/tasks/{task_id}/complete`
  - report: `POST /api/agent/tasks/{task_id}/report`

## 必須パラメータ

- task_list: なし
- task_create.path: task_id
- task_create.body.required: title
- task_create.body.optional: description, priority, estimate_hours, acceptance_criteria
- task_update.path: task_id, action
- task_update.body.optional: message, summary, work_notes, artifacts, next_actions, result_url, blockers, progress

## ステータス指定方法

- `ready` => `claim`
- `in_progress` => `start`
- `blocked` => `block`
- `done` => `complete`
- `report-only` => `report`

## プロジェクト / ボード / リスト指定

- Agent API では project_id / board_id / list_id の直接指定は不要です。
- タスク作成はトップレベル作成ではなく、親タスクID配下の child task 作成です。
- プロジェクト情報は GET /api/agent/tasks/{task_id}/context で取得します。

## レスポンス形式

- GET /api/agent/docs: {agent, documents}
- GET /api/agent/me: {agent}
- GET /api/agent/tasks/available: {tasks: []}

## エラー形式

- 404 時の実測: {"error":{"message":"Task not found.","details":[]}}

## 補足

- 通常の /api/projects や /api/tasks 系は X-User-Token を要求し、Agent Bearer Token では操作対象が異なります。
- Agent ガイドでは docs -> me -> available -> context -> claim/start -> report/complete の順での操作を推奨しています。
- QuickWBS Agent API だけではトップレベルタスクの新規作成は確認できませんでした。
