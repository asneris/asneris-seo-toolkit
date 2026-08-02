# Unified Page Diagnostics Processing - Sequence Diagram

## All Consumption Flows (Aligned to v2)

```mermaid
sequenceDiagram
    participant Sidebar as Sidebar<br/>(SeoReadinessPanel.js)
    participant Panel as Page Diagnostics<br/>Panel.js
    participant BulkBtn as Bulk "Analyze<br/>Selected" Button
    participant EditorPrev as Editor Preview<br/>(index.js)
    participant Cron as Cron Background<br/>(snapshots.php)
    
    participant Endpoint as /page-diagnostics-v2<br/>run | draft-policy
    participant Migration as ASNERISSEO_<br/>Page_Diagnostics_<br/>REST_API_Migration
    participant Processor as process_<br/>retrieved_<br/>diagnostics()
    participant Scorer as apply_weightage_<br/>scores_from_<br/>checks()
    participant Sync as sync_overview_<br/>checks_from_<br/>records()
    participant HttpTest as ASNERISSEO_<br/>Diagnostics::<br/>http_test_checks()
    participant Contract as ASNERISSEO_<br/>Page_Diagnostics_<br/>Response_Contract
    participant Normalizer as ASNERISSEO_<br/>Data_Interface_<br/>Normalizer
    participant SnapStore as Snapshot<br/>Storage<br/>(DB)

    Note over Sidebar,Cron: FRONTEND FLOWS

    rect rgb(200, 220, 255)
    Note over Sidebar: Clean Post Flow
    Sidebar->>Endpoint: POST /run/{postId}?no_store=1
    end

    rect rgb(200, 220, 255)
    Note over Sidebar: Dirty Draft Flow
    Sidebar->>Endpoint: POST /draft-policy {postTitle, content, meta, ...}
    end

    rect rgb(200, 220, 255)
    Note over Panel: Individual Run
    Panel->>Endpoint: POST /run/{postId}
    end

    rect rgb(200, 220, 255)
    Note over BulkBtn: Bulk Loop
    BulkBtn->>Panel: runPostDiagnostics(postId1) x N
    Panel->>Endpoint: POST /run/{postId} each
    end

    rect rgb(200, 220, 255)
    Note over EditorPrev: Editor Preview (Fixed)
    EditorPrev->>Endpoint: POST /run/{postId}?no_store=1
    end

    Note over Cron: BACKGROUND CRON FLOW
    rect rgb(255, 220, 200)
    Note over Cron: Priority Page Scan
    Cron->>HttpTest: http_test_checks(url)
    HttpTest-->>Cron: checks[]
    Cron->>Migration: build_weightage_score_override_for_post()
    Migration-->>Cron: score_override{}
    Cron->>SnapStore: save_snapshot(post, checks, override)
    end

    Note over Migration,Normalizer: UNIFIED BACKEND PROCESSING

    rect rgb(200, 255, 220)
    Note over Endpoint: Both Endpoint Paths
    Endpoint->>Migration: run_callback() OR evaluate_draft_policy()
    end

    rect rgb(200, 255, 220)
    Note over Migration: Normalize Input
    Migration->>Migration: build_overview_item() / build_draft_overview_item()
    end

    rect rgb(200, 255, 220)
    Note over HttpTest: Retrieve Checks
    Migration->>HttpTest: http_test_checks(url)
    HttpTest-->>Migration: checks[]
    end

    rect rgb(200, 255, 220)
    Note over Processor: Core Processing (Single Path)
    Migration->>Processor: process_retrieved_diagnostics()<br/>(post, overview_item, checks, overrides)
    end

    rect rgb(255, 255, 200)
    Note over Scorer,Sync: SHARED CORE LOGIC
    Processor->>Scorer: apply_weightage_scores_from_checks()<br/>(post, overview_item, checks, overrides)
    Scorer->>Scorer: Calculate seoScore, aiScore, health<br/>based on check status/results
    Scorer-->>Processor: overview_item{seoScore, aiScore, health, ...}
    
    Processor->>Sync: sync_overview_checks_from_records()<br/>(checks, overview_item)
    Sync->>Sync: Sync check statuses back to overview
    Sync-->>Processor: normalized_checks[]
    end

    Processor-->>Migration: {overviewItem, checks}

    rect rgb(200, 255, 220)
    Note over Contract: Response Normalization
    Migration->>Contract: build_payload(data, source_mode)
    end

    rect rgb(200, 255, 220)
    Note over Normalizer: Unified Data Envelope
    Contract->>Normalizer: normalize_diagnostics_payload()
    Normalizer->>Normalizer: Add unifiedData{sourceFlow,<br/>sourceEngine, sourceMode}
    Normalizer-->>Contract: payload{unifiedData}
    end

    Contract-->>Migration: normalized_payload
    Migration-->>Endpoint: HTTP 200 JSON

    Endpoint-->>Sidebar: response
    Endpoint-->>Panel: response
    Endpoint-->>EditorPrev: response

    Note over Sidebar,EditorPrev: UI Layer Consumes Unified Response

    rect rgb(200, 220, 255)
    Sidebar->>Sidebar: Extract checks via<br/>getUnifiedChecks()
    Sidebar->>Sidebar: Extract scores via<br/>getUnifiedComputed()
    Sidebar->>Sidebar: Render readiness score
    end

    rect rgb(200, 220, 255)
    Panel->>Panel: Extract checks via<br/>normalizeChecks()
    Panel->>Panel: Compare snapshots<br/>(history flow)
    Panel->>Panel: Render full panel
    end

    rect rgb(200, 220, 255)
    EditorPrev->>EditorPrev: Extract checks via<br/>getUnifiedChecks()
    EditorPrev->>EditorPrev: Render diagnostics preview
    end
```

## Key Design Principles

| Component | Purpose | Used By |
|-----------|---------|---------|
| **Sidebar** | Readiness score in editor sidebar | Live post or draft |
| **Panel** | Full diagnostics history & comparison | Priority/non-priority pages |
| **Bulk Button** | Multi-page analysis in sequence | Loop calls `runPostDiagnostics()` |
| **Editor Preview** | Quick diagnostic preview in editor | Live post only (v2 run) |
| **Cron** | Background priority page scanning | Scheduled every N hours |
| **/run endpoint** | Live post diagnostics + snapshots | Posted content (published/live) |
| **/draft-policy endpoint** | Draft/unsaved content analysis | Editor dirty state |
| **process_retrieved_diagnostics()** | **UNIFIED CORE** | All endpoints converge here |
| **apply_weightage_scores_from_checks()** | **UNIFIED SCORING** | Converts checks → seoScore, aiScore |
| **sync_overview_checks_from_records()** | **UNIFIED SYNC** | Propagates check status → overview |
| **Response Contract** | **UNIFIED SHAPE** | All responses normalized to same schema |
| **Cron Snapshot Storage** | Persistent history for trending | Non-v2 path; uses v2 scorer only |

## Rule Compliance Status

✅ **"Data value difference only at endpoint process" (Orchestration layer)**
- Sidebar chooses endpoint based on dirty state
- Panel chooses endpoint based on editor context
- Bulk loops individual runs (each chooses endpoint)
- Editor Preview always uses /run (clean)
- Cron uses background scoring (v2 only, no legacy fallback)

✅ **"All 3 flows must be same except their screen orchestration"**
1. Sidebar orchestration: Editor state → endpoint choice → render score badge
2. Panel orchestration: History loops, comparisons, pagination → render full details
3. Bulk orchestration: Loop selected items → sequential runs
4. Editor orchestration: Quick preview → minimal render
5. Cron orchestration: Background loop → store snapshots

✅ **Core Feature Identical Across All:**
- `process_retrieved_diagnostics()` always called (no branching)
- `apply_weightage_scores_from_checks()` always called (no branching)
- `sync_overview_checks_from_records()` always called (no branching)
- Response contract always applied (no branching)

✅ **No Legacy Fallbacks:**
- Cron uses v2 scorer only (no legacy REST_API fallback)
- Editor preview migrated to v2 run (no legacy overview)
- Sidebar aligned to v2 endpoints (both dirty and clean)
- Panel inherits v2 endpoints (both runs and draft-policy)
- Bulk reuses `runPostDiagnostics()` (already v2 aligned)
