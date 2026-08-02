# Page Analyzer Store/Retrieve Flow Analysis

## Question
When retrieving processed diagnostic data from DB (stored snapshots), should that data be re-normalized through the unified response contract to match v2 responses?

---

## STORE FLOW (Cron/Background Analyzer)

**Location:** [class-page-diagnostics-snapshots.php::save_snapshot()](class-page-diagnostics-snapshots.php#L536)

### What's Stored:

```php
// 1. DERIVE SCORES FROM CHECKS
$scores = self::derive_scores_from_checks( $checks );
$issue_groups = self::derive_issue_groups_from_checks( $checks );
$ai_score = 0;

// 2. APPLY V2 MIGRATION SCORER OVERRIDE (Line 549)
$score_override = ASNERISSEO_Page_Diagnostics_REST_API_Migration::build_weightage_score_override_for_post( $post, $checks );

// 3. APPLY OVERRIDE IF PROVIDED
if ( is_array( $score_override ) ) {
  if ( isset( $score_override['seoScore'] ) ) {
    $scores['seoScore'] = $score_override['seoScore'];  // ← SCORED
  }
  if ( isset( $score_override['issueGroups'] ) ) {
    $issue_groups = $score_override['issueGroups'];      // ← PROCESSED
  }
  if ( isset( $score_override['overviewIssueRecords'] ) ) {
    $overview_issue_records = $score_override['overviewIssueRecords'];  // ← DERIVED
  }
  // ... aiScore, health, tabIssueRecords, etc.
}

// 4. BUILD COMPLETE REPORT OBJECT
$report = [
  'postId' => $post->ID,
  'url' => $url,
  'seoScore' => (int) $scores['seoScore'],           // PROCESSED
  'aiScore' => (int) $ai_score,                      // PROCESSED
  'health' => $scores['health'],                     // PROCESSED
  'issueGroups' => $issue_groups,                    // PROCESSED
  'overviewIssueRecords' => $overview_issue_records, // PROCESSED
  'aiIssueRecords' => $ai_issue_records,             // PROCESSED
  'aiCanonicalSignals' => $ai_canonical_signals,     // PROCESSED
  'tabIssueRecords' => $tab_issue_records,           // PROCESSED
  'overviewRunId' => $overview_run_id,               // METADATA
  'seoScoreMessage' => $seo_score_message,           // METADATA
  'scoreEngine' => 'weightage_policy_v4_1',          // METADATA
  'checks' => $checks,                               // RAW CHECKS
  'generatedAtGmt' => gmdate( 'c' ),                 // METADATA
];

// 5. STORE AS JSON IN DATABASE
$report_json = wp_json_encode( $report );
$wpdb->insert( $latest_table, [
  'report_json' => $report_json,  // ← ENTIRE PROCESSED REPORT AS JSON
  'seo_score' => $scores['seoScore'],
  'health' => $scores['health'],
]);
```

### Data Stored in DB:
- ✅ Processed (scored, evaluated)
- ✅ Already has v4_1 weightage engine applied
- ✅ Includes issue groups, records, signals
- ❌ **Does NOT have `unifiedData` envelope** (response contract layer)

---

## RETRIEVE FLOW (History API)

**Location:** [class-page-diagnostics-snapshots.php::get_latest_snapshot_report()](class-page-diagnostics-snapshots.php#L704)

```php
public static function get_latest_snapshot_report( $post_id ) {
  $row = self::get_latest_snapshot( $post_id );
  $decoded = json_decode( (string) $row['report_json'], true );  // ← DESERIALIZE
  return self::with_tab_issue_records( $decoded );               // ← MINIMAL TRANSFORM
}
```

**Then in API handler** [class-rest-api.php::get_page_diagnostics_history()](class-rest-api.php#L4803):

```php
$latest_report = ASNERISSEO_Page_Diagnostics_Snapshots::get_latest_snapshot_report( $post_id );

// Directly uses retrieved data:
'seoScore' => isset( $latest_report['seoScore'] ) ? (int) $latest_report['seoScore'] : 0,
'health' => isset( $latest_report['health'] ) ? sanitize_key( $latest_report['health'] ) : 'warning',
'issueGroups' => isset( $latest_report['issueGroups'] ) && is_array( $latest_report['issueGroups'] ) 
  ? $latest_report['issueGroups'] 
  : [],
'checks' => isset( $latest_report['checks'] ) && is_array( $latest_report['checks'] ) 
  ? array_values( $latest_report['checks'] ) 
  : [],
```

### Data Retrieved from DB:
- ✅ Processed (was scored when stored)
- ✅ Already evaluated
- ❌ **Missing `unifiedData` envelope** (response contract)
- ❌ **May have schema drift** (if DB was created with old schema)

---

## Two Data Groups in Snapshot Record

### Group 1: Processed Diagnostics Data
```json
{
  "seoScore": 75,
  "aiScore": 82,
  "health": "good",
  "issueGroups": [...],
  "overviewIssueRecords": [...],
  "aiIssueRecords": [...],
  "aiCanonicalSignals": [...],
  "tabIssueRecords": [...],
  "checks": [...],
  "scoreEngine": "weightage_policy_v4_1"
}
```
**Status:** ✅ Processed via `process_retrieved_diagnostics()` when **stored**

### Group 2: History Metadata
```json
{
  "postId": 123,
  "url": "https://...",
  "generatedAtGmt": "2026-07-27T10:43:00+00:00",
  "createdAt": "2026-07-27T10:43:00+00:00",
  "overviewRunId": "snapshot-2026-0727-104300"
}
```
**Status:** ✅ Metadata only

---

## ANSWER: Should Stored Data Be Re-Normalized on Retrieve?

### Current Implementation: **NO** ❌
Stored data is returned as-is from DB without response contract processing.

### Recommended: **YES** ✅
When retrieving historical snapshots, apply response contract to ensure:

1. **Schema Consistency:**
   - Stored snapshot may use old schema (before unified contract)
   - Retrieved data should match v2 response schema
   - Prevents UI from breaking when schema changes

2. **Unified Data Envelope:**
   - Live v2 responses include `unifiedData` with sourceFlow/sourceEngine/sourceMode
   - Historical snapshots currently lack this envelope
   - History API should add it for consistency

3. **Migration Safety:**
   - If snapshot data was stored before v2 unification, it may be incomplete
   - Normalizing on retrieve ensures all snapshots are "normalized to latest schema"

---

## Proposed Solution

### Option A: Normalize on Retrieve (Recommended)
```php
// In get_page_diagnostics_history()
$latest_report = ASNERISSEO_Page_Diagnostics_Snapshots::get_latest_snapshot_report( $post_id );

// Apply response contract normalization
if ( class_exists( 'ASNERISSEO_Page_Diagnostics_Response_Contract' ) ) {
  $normalized_report = ASNERISSEO_Page_Diagnostics_Response_Contract::build_payload(
    $latest_report,
    'snapshot-skip'  // source mode
  );
}

// Return normalized report in history list
```

**Pros:**
- Ensures all historical data matches current v2 schema
- Adds `unifiedData` envelope for consistency
- Safe schema migrations for future changes

**Cons:**
- Slight performance overhead on history retrieval
- Caching may be needed for large histories

### Option B: Normalize on Store (Alternative)
Store the complete normalized payload (with `unifiedData` envelope) when saving snapshot.

**Pros:**
- No overhead on retrieve
- Already-normalized data in DB

**Cons:**
- More data stored per snapshot
- Harder to debug (serialized unifiedData)

---

## Recommendation Matrix

| Scenario | Action |
|----------|--------|
| **History API retrieves snapshot** | ✅ Apply response contract |
| **Cron stores new snapshot** | ✅ Already processed via v2 scorer |
| **Live v2 run endpoint returns** | ✅ Already normalized via response contract |
| **Draft policy endpoint returns** | ✅ Already normalized via response contract |
| **UI compares old vs new snapshot** | ✅ Both should go through response contract |

---

## Conclusion

**Data stored in DB:**
- **Group 1 (Diagnostics):** Already **processed** via `process_retrieved_diagnostics()` during cron
- **Group 2 (Metadata):** Timestamps, URLs, metadata only

**Data when retrieved:**
- Should pass through `ASNERISSEO_Page_Diagnostics_Response_Contract::build_payload()` to ensure schema consistency and add `unifiedData` envelope
- This keeps historical snapshots aligned with current v2 response contract

**Action:** Modify `get_page_diagnostics_history()` to normalize Group 1 via response contract before returning.
