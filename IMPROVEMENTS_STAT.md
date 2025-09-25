# Stats.php Optimization Improvements

## Overview
This document outlines potential optimizations for the `stats.php` file to improve performance, maintainability, and code quality.

## 1. 🚀 Performance Optimizations

### A. SQL Query Consolidation
**Current Issue:** Multiple separate queries for statistics (lines 132-247)
- Cumulative stats query
- Last 3 sessions stats query  
- Most recent session stats query
- Date range queries
- Category counts query

**Optimization:** Create single query with CTEs (Common Table Expressions) or subqueries
```sql
WITH session_stats AS (
    -- Consolidated stats query
),
category_data AS (
    -- Category information
)
SELECT * FROM session_stats, category_data
```

**Benefits:**
- Reduce from ~6 queries to 1-2 queries
- 30-50% improvement in page load time
- Reduced database connection overhead

### B. Result Caching
**Current Issue:** Repetitive percentage and CSS class calculations (lines 348-386)
- Same logic repeated for cumulative, most recent, and last 3 sessions
- Percentage class determination duplicated

**Optimization:** Create reusable functions
```php
function calculatePercentageClass($percentage) {
    if ($percentage == 100) return 'perfect';
    if ($percentage >= 80) return 'high';
    if ($percentage >= 60) return 'medium';
    return 'low';
}

function calculatePercentage($correct, $answered) {
    return $answered > 0 ? round(($correct / $answered) * 100) : 0;
}
```

**Benefits:**
- Cleaner, more maintainable code
- Reduced code duplication
- Easier to modify percentage thresholds

### C. Lazy Loading Implementation
**Current Issue:** All data loaded regardless of which tab is accessed
- Study Session data loaded even if user only views Grade Report
- Exam Session data loaded even if not needed
- History data loaded for all users

**Optimization:** Load data only when needed
```php
// Load data based on active tab or user preference
if ($active_tab === 'study_session') {
    $study_data = generateSessionData(1, $DB, $course->id);
}
```

**Benefits:**
- Significant reduction in queries and memory usage
- Faster initial page load
- Better user experience

## 2. 🏗️ Structural Optimizations

### A. Function Refactoring
**Current Issue:** Large functions with multiple responsibilities
- `generateSessionData()` function is too long (600+ lines)
- Mixed data fetching and formatting logic

**Optimization:** Break into smaller, focused functions
```php
class SessionDataProcessor {
    public function getCategoryStats($categoryids, $studysession) { }
    public function getSessionStats($categoryids, $session_ids) { }
    public function formatCategoryData($raw_data) { }
    public function calculateStatistics($data) { }
}
```

**Benefits:**
- Better maintainability
- Easier testing
- Single responsibility principle

### B. Code Deduplication
**Current Issue:** Similar logic for Study/Exam sessions
- Duplicate processing for different session types
- Repeated data structure creation

**Optimization:** Generic processing function
```php
function processSessionData($studysession_type, $DB, $course_id) {
    // Generic logic that works for both study and exam sessions
    // Use $studysession_type parameter to filter data
}
```

**Benefits:**
- ~200 lines of code reduction
- Easier maintenance
- Consistent behavior

### C. Class-Based Architecture
**Current Issue:** Monolithic file with mixed responsibilities (1988 lines)
- Data fetching
- Data processing
- Template preparation
- Business logic

**Optimization:** Separate into classes
```php
class StatsCalculator {
    public function calculateCategoryStats() { }
    public function calculateSessionStats() { }
}

class DataFormatter {
    public function formatCategoryData() { }
    public function formatHistoryData() { }
}

class QueryBuilder {
    public function buildStatsQuery() { }
    public function buildHistoryQuery() { }
}
```

**Benefits:**
- Better organization
- Easier testing
- Reusable components

## 3. 💾 Memory Optimizations

### A. Batch Processing
**Current Issue:** Large arrays loaded into memory simultaneously
- All questions loaded at once (lines 1895-1906)
- All historical data loaded at once
- All category data loaded at once

**Optimization:** Process data in chunks
```php
// Process questions in batches of 50
foreach (array_chunk($questions, 50) as $question_batch) {
    $this->processQuestionBatch($question_batch);
    unset($question_batch); // Free memory
}
```

**Benefits:**
- 20-30% reduction in memory usage
- Better performance for large datasets
- More scalable solution

### B. Variable Cleanup
**Current Issue:** Large variables not cleaned up after use
- Arrays remain in memory after processing
- No explicit memory management

**Optimization:** Explicit cleanup
```php
// After processing large arrays
unset($category_stats);
unset($last_3_sessions_stats);
unset($most_recent_session_stats);
```

**Benefits:**
- Immediate memory release
- Better memory management
- Improved performance

## 4. 🔧 Code Quality Improvements

### A. Magic Numbers Elimination
**Current Issue:** Hardcoded values throughout code
- Percentage thresholds: `60`, `80`, `100`
- Time calculations: `60` (seconds per question)
- Array limits: `3` (last 3 sessions)

**Optimization:** Define constants
```php
class StatsConstants {
    const PERFECT_SCORE = 100;
    const HIGH_SCORE_THRESHOLD = 80;
    const MEDIUM_SCORE_THRESHOLD = 60;
    const SECONDS_PER_QUESTION = 60;
    const LAST_SESSIONS_LIMIT = 3;
}
```

**Benefits:**
- More readable code
- Easier to modify thresholds
- Better maintainability

### B. Input Validation
**Current Issue:** Limited validation of input parameters
- No validation of `$examid` parameter
- No validation of user permissions
- No validation of data integrity

**Optimization:** Add comprehensive validation
```php
function validateExamAccess($examid, $userid) {
    if (!$examid || !is_numeric($examid)) {
        throw new InvalidArgumentException('Invalid exam ID');
    }
    
    $exam = $DB->get_record('revitimsession_practice_exams', ['id' => $examid]);
    if (!$exam || $exam->userid != $userid) {
        throw new UnauthorizedException('Access denied');
    }
    
    return $exam;
}
```

**Benefits:**
- Better security
- More robust error handling
- Improved user experience

### C. Error Handling
**Current Issue:** Limited error handling and logging
- Queries may fail silently
- No logging of performance issues
- No graceful degradation

**Optimization:** Comprehensive error handling
```php
try {
    $stats_data = $this->calculateStats();
} catch (DatabaseException $e) {
    error_log("Stats calculation failed: " . $e->getMessage());
    $stats_data = $this->getFallbackData();
}
```

**Benefits:**
- Better debugging
- Graceful error handling
- Improved reliability

## 5. 📊 Database Optimizations

### A. Index Optimization
**Current Issue:** Complex queries may be slow without proper indexes
- Queries on `questioncategoryid`
- Queries on `studysession` and `timefinished`
- JOIN operations on multiple tables

**Optimization:** Verify and add indexes
```sql
-- Ensure these indexes exist
CREATE INDEX idx_peq_questioncategoryid ON {revitimsession_practice_exam_questions} (questioncategoryid);
CREATE INDEX idx_pe_studysession_timefinished ON {revitimsession_practice_exams} (studysession, timefinished);
CREATE INDEX idx_qbe_questioncategoryid ON {question_bank_entries} (questioncategoryid);
```

**Benefits:**
- Faster query execution
- Better database performance
- Reduced server load

### B. Query Optimization
**Current Issue:** Some queries use inefficient patterns
- `COUNT(*)` instead of `COUNT(1)`
- Multiple `CASE WHEN` statements
- Complex JOIN operations

**Optimization:** Optimize query patterns
```sql
-- More efficient counting
SELECT COUNT(1) as answered_count
FROM {revitimsession_practice_exam_questions} peq
WHERE peq.status != 0

-- Instead of
SELECT COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count
```

**Benefits:**
- Faster query execution
- Reduced database load
- Better scalability

## 6. 🎯 Implementation Priority

### High Priority (Immediate Impact)
1. **SQL Query Consolidation** - 30-50% performance improvement
2. **Result Caching** - Cleaner code, easier maintenance
3. **Code Deduplication** - 200+ lines reduction

### Medium Priority (Quality Improvements)
4. **Function Refactoring** - Better maintainability
5. **Class-Based Architecture** - Better organization
6. **Magic Numbers Elimination** - More readable code

### Low Priority (Nice to Have)
7. **Lazy Loading** - If performance is not critical
8. **Batch Processing** - If memory is not an issue
9. **Advanced Error Handling** - If current error handling is sufficient

## 7. 📈 Expected Impact

### Performance Improvements
- **Page Load Time:** 30-50% faster
- **Memory Usage:** 20-30% reduction
- **Database Queries:** 60-70% reduction

### Code Quality Improvements
- **Lines of Code:** 25-35% reduction
- **Maintainability:** 40-60% improvement
- **Testability:** 50-70% improvement

### User Experience Improvements
- **Faster Response Times:** 30-50% improvement
- **Better Error Handling:** More graceful failures
- **Scalability:** Better performance with large datasets

## 8. 🔄 Implementation Strategy

### Phase 1: Performance (Week 1-2)
1. Consolidate SQL queries
2. Implement result caching
3. Add basic error handling

### Phase 2: Structure (Week 3-4)
1. Refactor large functions
2. Eliminate code duplication
3. Add input validation

### Phase 3: Quality (Week 5-6)
1. Implement class-based architecture
2. Add comprehensive error handling
3. Optimize database queries

### Phase 4: Testing & Optimization (Week 7-8)
1. Performance testing
2. Memory usage optimization
3. Final code review and cleanup

## 9. 📝 Notes

- All optimizations should maintain backward compatibility
- Performance improvements should be measured before and after
- Code changes should be thoroughly tested
- Consider implementing optimizations incrementally
- Monitor database performance after query optimizations

---

**Last Updated:** September 2025
**Status:** Planning Phase
**Estimated Effort:** 6-8 weeks
**Priority:** High
