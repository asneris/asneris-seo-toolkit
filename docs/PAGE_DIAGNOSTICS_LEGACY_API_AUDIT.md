# Page Diagnostics Legacy API Endpoints Audit

## Summary

**Status:** Legacy class still hosts storage/retrieval APIs. Processing endpoints migrated to v2.

---

## Active Endpoints (Still in use)

All endpoints listed here are registered in `class-rest-api.php` and actively called from the frontend.

### 1. History Retrieval ✅ NEEDED
**Route:** `GET /page-diagnostics/history/{id}`  
**Handler:** `ASNERISSEO_REST_API::get_page_diagnostics_history()`  
**Line:** [4803](class-rest-api.php#L4803)  
**Called by:** [PageDiagnosticsPanel.js](src/admin/components/panels/PageDiagnosticsPanel.js#L3274) (`const diagnosticsHistoryBaseUrl`)  
**Purpose:** Retrieve snapshot history for a page (list view with pagination)  
**Action:** ✅ Keep - This is read-only database query, not processing

### 2. Delete History Record ✅ NEEDED
**Route:** `DELETE /page-diagnostics/history/{id}/delete`  
**Handler:** `ASNERISSEO_REST_API::delete_page_diagnostics_history_record()`  
**Line:** [4874](class-rest-api.php#L4874)  
**Called by:** [PageDiagnosticsPanel.js](src/admin/components/panels/PageDiagnosticsPanel.js#L3600) (`deleteHistoryRecord()`)  
**Purpose:** Delete a single history snapshot record  
**Action:** ✅ Keep - Manual history cleanup

### 3. Clear All Records ✅ NEEDED
**Route:** `DELETE /page-diagnostics/records/clear/{id}`  
**Handler:** `ASNERISSEO_REST_API::clear_page_diagnostics_records()`  
**Line:** [4914](class-rest-api.php#L4914)  
**Called by:** [PageDiagnosticsPanel.js](src/admin/components/panels/PageDiagnosticsPanel.js#L3304)  
**Purpose:** Clear all snapshot records for a page  
**Action:** ✅ Keep - Bulk history cleanup

---

## Dead Endpoints (Legacy, No Longer Used)

### 1. Legacy Run Diagnostics ❌ DEAD
**Route:** `POST /page-diagnostics/run/{id}`  
**Handler:** `ASNERISSEO_REST_API::run_page_diagnostics_scan()`  
**Line:** [4265](class-rest-api.php#L4265)  
**Replaced by:** `POST /page-diagnostics-v2/run/{id}` (v2 migration class)  
**Status:** Not called from anywhere in frontend  
**Action:** ⚠️ Consider deprecating (keep for backwards compatibility if external integrations exist)

### 2. Legacy Overview ❌ DEAD
**Route:** `GET /page-diagnostics/overview`  
**Handler:** `ASNERISSEO_REST_API::get_page_diagnostics_overview()`  
**Line:** [1183](class-rest-api.php#L1183)  
**Replaced by:** All endpoints now use v2 run/draft-policy (POST)  
**Status:** Not called from anywhere in frontend  
**Action:** ⚠️ Consider deprecating (keep for backwards compatibility if external integrations exist)

---

## Architecture Map

```
┌─ FRONTEND CONSUMPTION ──────────────────────────────────────┐
│                                                               │
│  Sidebar, Panel, Editor, Cron                               │
│                                                               │
└──────────────────────┬──────────────────────────────────────┘

                       │
        ┌──────────────┴──────────────┐
        │                             │
        ▼                             ▼
   
   ┌─ PROCESSING  ──┐          ┌─ STORAGE/QUERY ──┐
   │  (v2 NEW)      │          │  (v1 LEGACY)     │
   │                │          │                  │
   │ /run/{id}      │          │ /history/{id}    │
   │ /draft-policy  │          │ /history.../del  │
   │                │          │ /records/clear   │
   └────────────────┘          └──────────────────┘
   
   ASNERISSEO_             ASNERISSEO_
   Page_Diagnostics_       REST_API
   REST_API_Migration      (Snapshot DB)
```

---

## Recommendation

| Endpoint | Status | Action |
|----------|--------|--------|
| `GET /page-diagnostics/overview` | Dead | **Deprecate v1** (remove from v2, keep in v1 for compatibility) |
| `POST /page-diagnostics/run/{id}` | Dead | **Deprecate v1** (remove from v2, keep in v1 for compatibility) |
| `POST /page-diagnostics-v2/run/{id}` | Active | ✅ In use |
| `POST /page-diagnostics-v2/draft-policy` | Active | ✅ In use |
| `GET /page-diagnostics/history/{id}` | Active | ✅ Keep in v1 (storage layer) |
| `DELETE /page-diagnostics/history/{id}/delete` | Active | ✅ Keep in v1 (storage layer) |
| `DELETE /page-diagnostics/records/clear/{id}` | Active | ✅ Keep in v1 (storage layer) |

---

## Conclusion

✅ **Processing endpoints successfully migrated to v2**  
✅ **Storage/history endpoints remain in v1 (appropriate isolation)**  
✅ **No conflict — v1 and v2 coexist cleanly**  
⚠️ **Legacy v1 run/overview endpoints are dead code but safe to keep for backwards compatibility**
