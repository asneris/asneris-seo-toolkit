# Frontend Interface Models vs. Unified REST API Alignment Analysis

**Date:** 2026-07-27  
**Status:** ✅ Aligned with one normalization gap noted

---

## Executive Summary

The two frontend data models (`discoverabilityDataModel.js` and `discoverabilityIssueModel.js`) **ARE aligned** with the unified REST API response contract. All core data structures match, and the categorization logic is consistent.

**One Gap Identified:** The frontend CANONICAL_RAW_FIELD_MAP uses a hardcoded local mapping, while the API could theoretically return rawEvidence/rawEvidenceFields in checks. However, since this data is not currently used by the UI, it's a non-blocking architectural difference.

---

## 1. Frontend Data Model: discoverabilityDataModel.js

### Primary Structures

**OVERVIEW_PRIMARY_FIELDS** (7 fields):
```javascript
[
  'HTTP Status',
  'Robots Meta',
  'SEO Title Length',
  'Meta Description Length',
  'H1 Presence',
  'Internal Links',
  'Content Depth (Word Count)',
]
```

**TAB_FIELD_REGISTRY** (8 tabs, ~70 canonical fields):
```javascript
{
  overview: OVERVIEW_PRIMARY_FIELDS,
  searchAppearance: [...],
  indexability: [...],
  contentQuality: [...],
  images: [...],
  links: [...],
  structuredData: [...],
  aiDiscoverability: [...],
}
```

**CANONICAL_RAW_FIELD_MAP** (field-level mapping to raw evidence):
```javascript
{
  overview: {
    'Robots Meta': ['robotsIndex', 'robotsFollow'],
    'HTTP Status': ['httpStatus'],
    'SEO Title': ['metaTitle'],
    // ... ~7 more fields
  },
  searchAppearance: {
    // ... ~9 fields
  },
  // ... 6 more tabs
}
```

### Key Logic

1. **categorizeDiscoverabilityCheck(checkLabel)** 
   - Routes checks to categories: 'search', 'advanced', 'quality', 'links', 'schema', 'images', 'ai'
   - Used to filter which checks appear in which tabs
   - **✅ Maps to REST API check.category**

2. **doesCheckLabelMatchCanonicalField(tabKey, canonicalField, checkLabel)**
   - Matches check.label to canonical field names
   - Handles fuzzy matching (e.g., "SEO Title Length" vs "SEO Title")
   - **✅ Consumes check.label from REST API**

3. **getCanonicalRawFields(tabKey, canonicalField)**
   - Returns hardcoded raw field names for UI lookups
   - **Note:** This is LOCAL data, not from API
   - Not blocking, UI uses for reference lookup

---

## 2. Frontend Data Model: discoverabilityIssueModel.js

### Primary Structures

**mapDiscoverabilityStatus(status, passed)**
```javascript
// Expects: check.status ('pass', 'warning', 'fail')
// Or: check.passed (boolean)
// Returns: 'pass', 'warning', or 'fail'
```

**buildIssueBreakdownRows(checks, options)**
```javascript
// Expects checks array with:
{
  label: string,           // ✅ matches check.label
  status: string,          // ✅ matches check.status
  details: string,         // ✅ matches check.details
  passed: boolean,         // ✅ optional, fallback
}
```

### Key Logic

1. **categorizeDiscoverabilityCheck(check.label)**
   - Called for every check to route to category bucket
   - **✅ Maps to categorizeDiscoverabilityCheck() in data model**

2. **buildTopIssueCategories(checks)**
   - Aggregates checks by category: 'search', 'advanced', 'content' (quality/links/images), 'ai' (ai/schema)
   - **✅ Uses category routing**

3. **buildIssueBreakdownRows(checks, options)**
   - Formats top 8 issues into table rows with impact/recommendation
   - Pattern matches on label for contextual messaging
   - **✅ Consumes check.label, check.status, check.details**

---

## 3. REST API Response Contract (Unified Processor)

### Response Structure

**Response defaults (ASNERISSEO_Page_Diagnostics_Response_Contract::get_payload_defaults()):**
```php
[
  'postId' => 0,
  'seoScore' => 0,
  'aiScore' => 0,
  'health' => 'warning',
  'checks' => [],              // ← Array of check objects
  'issueGroups' => [],         // ← Issue groupings
  'overviewIssueRecords' => [], // ← Canonical field records
  'aiIssueRecords' => [],
  'tabIssueRecords' => [],
  'aiCanonicalSignals' => [],
  'unifiedData' => [],         // ← sourceFlow, sourceEngine, sourceMode
  // ... 30+ other diagnostic fields
]
```

**Check object structure (get_check_defaults()):**
```php
[
  'label' => '',              // ✅ Check label (e.g., "SEO Title Length")
  'category' => '',           // ✅ Category routing (e.g., 'search', 'advanced')
  'status' => 'warning',      // ✅ Status: 'pass', 'warning', 'fail'
  'result' => '',             // ✅ Formatted result string
  'details' => '',            // ✅ Details/context
  'canonicalField' => '',     // ✅ Mapped canonical field name
  'rawEvidence' => [],        // ← Raw data object (optional)
  'rawEvidenceFields' => [],  // ← Field names for evidence lookup
]
```

### Processing Pipeline

```
1. process_retrieved_diagnostics()
   ├─ Apply weightage scores from checks
   ├─ Build overviewItem with health, scores, issueGroups
   └─ Sync overview records into check status/result

2. Response Contract Normalization (build_payload)
   ├─ apply_defaults() - merge payload with schema defaults
   ├─ normalize_checks() - ensure each check has complete schema
   ├─ Add sourceMode
   └─ Add unifiedData envelope (via Data_Interface_Normalizer)

3. REST Response
   └─ return rest_ensure_response(normalized)
```

---

## 4. Alignment Matrix

| Frontend Expectation | REST API Field | Status | Notes |
|---|---|---|---|
| **checks array exists** | response.checks | ✅ | Array of check objects |
| **check.label** | check.label | ✅ | String label |
| **check.status** | check.status | ✅ | 'pass' \| 'warning' \| 'fail' |
| **check.details** | check.details | ✅ | String details/context |
| **check.passed (optional)** | check.status | ✅ | Can use passed=true, else fallback to status |
| **categorizeDiscoverabilityCheck()** | check.category (plus label) | ✅ | Category field provided + label-based routing |
| **Tab field mapping** | check.canonicalField | ✅ | Maps checks to tabs via canonical field |
| **TAB_FIELD_REGISTRY** | check.label matching | ✅ | Frontend matches check.label to canonical fields |
| **issueGroups** | response.issueGroups | ✅ | Aggregated issues by type |
| **health** | response.health | ✅ | 'good' \| 'warning' \| 'critical' |
| **seoScore** | response.seoScore | ✅ | Numeric 0-100 |
| **unifiedData** | response.unifiedData | ✅ | sourceFlow, sourceEngine, sourceMode |

---

## 5. Data Flow Example

### Live /run Endpoint Request

```javascript
// Frontend calls (from PageDiagnosticsPanel.js)
POST /wp-json/asneris-seo/v1/page-diagnostics-v2/run/123?no_store=1
```

### REST API Unified Processing

```php
// class-page-diagnostics-rest-api-migration.php
1. run_page_diagnostics_scan_v2()
   ├─ Call ASNERISSEO_Diagnostics::http_test_checks()
   │  └─ Returns: [checks array with label, status, details]
   ├─ build_draft_overview_item() OR get_published_post_context()
   ├─ process_retrieved_diagnostics()
   │  ├─ apply_weightage_scores_from_checks()
   │  │  └─ Calculate seoScore, aiScore, health, issueGroups
   │  └─ sync_overview_checks_from_records()
   │     └─ Update check.status/result from canonical field records
   └─ build_response(payload, 'live_scan')
      ├─ Response_Contract::build_payload()
      │  ├─ apply_defaults() - ensures all fields present
      │  ├─ normalize_checks() - validates check schema
      │  └─ Add unifiedData envelope
      └─ rest_ensure_response()
```

### REST API Response

```json
{
  "postId": 123,
  "seoScore": 75,
  "aiScore": 82,
  "health": "good",
  "checks": [
    {
      "label": "SEO Title Length",
      "category": "search",
      "status": "pass",
      "result": "55",
      "details": "",
      "canonicalField": "SEO Title Length",
      "rawEvidence": {},
      "rawEvidenceFields": []
    },
    {
      "label": "HTTP Status",
      "category": "advanced",
      "status": "pass",
      "result": "200",
      "details": "",
      "canonicalField": "HTTP Status",
      "rawEvidence": { "httpStatus": 200 },
      "rawEvidenceFields": ["httpStatus"]
    }
  ],
  "issueGroups": [...],
  "overviewIssueRecords": [...],
  "unifiedData": {
    "sourceFlow": "page_diagnostics",
    "sourceEngine": "weightage_policy_v4_1",
    "sourceMode": "live_scan"
  }
}
```

### Frontend Consumption

```javascript
// SeoReadinessPanel.js / PageDiagnosticsPanel.js
const response = await fetch(...); // ← Gets unified response above
const data = response.json();

// discoverabilityIssueModel.js
buildIssueBreakdownRows(data.checks, {})
  ├─ Loop data.checks
  ├─ For each check:
  │  ├─ mapDiscoverabilityStatus(check.status)
  │  ├─ categorizeDiscoverabilityCheck(check.label)
  │  ├─ Extract check.details
  │  └─ Build impact/recommendation row
  └─ Return top 8 issues

// discoverabilityDataModel.js
const fields = getCanonicalFieldsByTab('searchAppearance')
// ← Returns ['SEO Title', 'SEO Title Length', ..., 'Twitter Card']

checks.forEach(check => {
  if (doesCheckLabelMatchCanonicalField('searchAppearance', 'SEO Title Length', check.label)) {
    // This check belongs to searchAppearance tab
    // Build tab content
  }
});
```

---

## 6. Identified Gaps & Notes

### Gap 1: Local vs. API Raw Field Mapping
**Status:** Non-blocking  
**Description:** 
- `CANONICAL_RAW_FIELD_MAP` in frontend is hardcoded local data
- REST API can return `check.rawEvidence` and `check.rawEvidenceFields`
- Frontend doesn't currently consume API-provided evidence

**Impact:** None - frontend uses local reference for lookups  
**Recommendation:** Leave as-is (local reference is more maintainable than API-provided)

### Gap 2: No "passed" Field in API Response
**Status:** Non-blocking  
**Description:**
- Frontend handles both `check.status` (string) and `check.passed` (boolean)
- REST API only provides `check.status`

**Impact:** None - status is the canonical field  
**Recommendation:** Frontend correctly falls back to status-based logic

### Gap 3: Category Routing via Label Pattern Matching
**Status:** Non-blocking  
**Description:**
- Frontend categorizes checks via regex pattern matching on label
- API also provides `check.category` field
- Frontend uses both (label matching first, category as backup)

**Impact:** Slight redundancy but good defensive programming  
**Recommendation:** Continue dual approach for robustness

---

## 7. Verification Checklist

✅ **discoverabilityIssueModel.js**
- ✅ Expects checks array ← API provides it
- ✅ Consumes check.label ← API provides it
- ✅ Consumes check.status ← API provides it
- ✅ Consumes check.details ← API provides it
- ✅ Calls categorizeDiscoverabilityCheck(label) ← Works with check.label
- ✅ Builds top issue categories ← Works with categorization logic

✅ **discoverabilityDataModel.js**
- ✅ OVERVIEW_PRIMARY_FIELDS defined ← Static catalog
- ✅ TAB_FIELD_REGISTRY defined ← Static registry for tab organization
- ✅ CANONICAL_RAW_FIELD_MAP defined ← Local reference data
- ✅ categorizeDiscoverabilityCheck() ← Works with check.label from API
- ✅ doesCheckLabelMatchCanonicalField() ← Matches check.label to tabs
- ✅ getCanonicalRawFields() ← Local lookup (not API-dependent)

✅ **REST API Response Contract**
- ✅ check.label provided ← For categorization
- ✅ check.category provided ← For tab routing
- ✅ check.status provided ← For status display
- ✅ check.details provided ← For impact/recommendation
- ✅ check.canonicalField provided ← For field mapping
- ✅ response.issueGroups ← For aggregation
- ✅ response.unifiedData ← For source tracking

✅ **Data Flow**
- ✅ Live endpoints use unified processor ← Verified earlier
- ✅ Draft endpoints use unified processor ← Verified earlier
- ✅ History endpoints normalize via response contract ← Just implemented
- ✅ Response contract normalizes checks schema ← Verified
- ✅ All checks have required fields ← Via defaults + normalization

---

## Conclusion

**✅ YES, the interface data models ARE properly aligned with the unified REST API.**

All critical data structures match:
- Checks array with label, status, details, category, canonicalField
- Tab field registries and canonical field mapping
- Categorization logic for check routing
- Issue grouping and health scores
- Unified data envelope with source tracking

**One architectural note:** The frontend uses a hardcoded `CANONICAL_RAW_FIELD_MAP` for field lookups rather than consuming this from the API. This is appropriate because:
1. It's a stable reference catalog (not runtime data)
2. It improves performance (no API call needed)
3. It reduces API payload size
4. Frontend and backend can evolve independently

The unified REST API and frontend models form a cohesive system that consistently processes diagnostics data across all 5 consumption flows (live run, draft policy, history fallback, cron background, preview).

---

## Files Referenced

**Frontend Models:**
- [src/app/discoverabilityDataModel.js](src/app/discoverabilityDataModel.js) - Tab registry and field mapping
- [src/app/discoverabilityIssueModel.js](src/app/discoverabilityIssueModel.js) - Issue building and categorization

**REST API Response Contract:**
- [includes/class-page-diagnostics-response-contract.php](includes/class-page-diagnostics-response-contract.php) - Check schema defaults
- [includes/class-page-diagnostics-rest-api-migration.php](includes/class-page-diagnostics-rest-api-migration.php) - Unified processing pipeline

**Storage API (newly migrated):**
- [includes/class-page-diagnostics-history-storage-api.php](includes/class-page-diagnostics-history-storage-api.php) - History retrieval with response contract normalization
