# Context Documentation - Revit IM Session Plugin

## Project Overview

This is a **Moodle plugin** called `mod_revitimsession` that provides comprehensive practice exam functionality with advanced features including timed sessions, review modes, detailed performance analytics, and an advanced filter system for study sessions.

## Current State (September 2025)

### ✅ Completed Features
1. **Practice Exam Creation** - 4-step wizard process
2. **Study Session Creation** - 3-step wizard process (ends at step 3)
3. **Exam Performance Interface** - Full exam taking experience
4. **Study Session Interface** - Full study session experience with advanced filtering
5. **Advanced Filter System** - Radio button-based filtering for study sessions
6. **Review Mode** - Section review with different filter options
7. **Grading System** - Automatic answer evaluation and scoring
8. **Statistics Page** - Gleim-like performance analysis with tabs
9. **Timer Management** - Real-time countdown with negative value handling
10. **Calculator/Instructions** - Dynamic button switching based on mode
11. **Database Schema** - Complete with optimization fields and study session support
12. **Status and Correctness Tracking** - Dynamic arrays for real-time status updates

### 🔧 Technical Implementation
- **Database**: 2 core tables (`revitimsession_practice_exams`, `revitimsession_practice_exam_questions`)
- **Frontend**: JavaScript ES6+ with Mustache templates and advanced filtering
- **Backend**: PHP with Moodle standards
- **Styling**: Custom CSS with responsive design and filter menu styling

### 📚 Study Session Implementation
- **Shared Database**: Uses same tables as practice exams with `studysession` flag (0=practice, 1=study)
- **Creation Flow**: 3-step process ending at step 3 (vs 4 steps for practice exams)
- **Interface**: Identical to practice exams but with study-specific messaging
- **Advanced Filtering**: Radio button system with automatic application
- **Files**: Separate PHP, JS, and template files for maintainability
- **URL Parameter**: Uses `type=study` parameter to differentiate creation flows

## Development Patterns & Preferences

### Code Style & Conventions
- **Language**: All code comments and documentation in English
- **CSS**: Place all styles in `styles.css`, not inline in PHP
- **Templates**: Use Mustache templates instead of embedded HTML in PHP
- **Version Updates**: Always increment `version.php` when changing strings or schema
- **Version Format**: YYYYMMDDVV (e.g., 2025091948)
- **JavaScript**: Use global arrays for dynamic data (`status`, `correct`, `markedforreview`)

### File Organization
- **PHP Logic**: Main files in root directory
- **Templates**: Mustache files in `templates/` directory
- **JavaScript**: Client-side logic in `javascript/` directory
- **Styles**: All CSS in `styles.css`
- **Language**: Strings in `lang/en/revitimsession.php`

### Database Patterns
- **Transactions**: Use for critical operations (exam creation, grading)
- **Optimization**: Store calculated values (e.g., `totalquestions`) to avoid repeated queries
- **Time Handling**: Use Unix timestamps consistently
- **Status Fields**: Use integer flags (0/1) for boolean states
- **Dynamic Arrays**: Use global JavaScript arrays for real-time tracking

## Key Technical Decisions

### Advanced Filter System Implementation
```javascript
// Radio button-based filter with single selection and automatic application
function applyFilter() {
    const filterMenu = document.getElementById('filter-menu');
    const selectedRadio = filterMenu.querySelector('input[name="filter-option"]:checked');
    
    if (!selectedRadio || selectedRadio.value === 'none') {
        filteredQuestions = [];
        showAllQuestions();
        return;
    }
    
    const selectedFilter = selectedRadio.value;
    filteredQuestions = [];
    
    for (let i = 1; i <= totalQuestions; i++) {
        const currentStatus = status[i] || 0;
        const currentCorrect = correct[i] || 0;
        const isMarked = SAVED_MARKEDFORREVIEW && SAVED_MARKEDFORREVIEW[i] == 1;
        
        let matchesFilter = false;
        
        // Filter logic based on status and correct arrays
        if (selectedFilter === 'incomplete' && currentStatus === 0) matchesFilter = true;
        if (selectedFilter === 'marked' && isMarked) matchesFilter = true;
        if (selectedFilter === 'incorrect' && currentCorrect === 0 && currentStatus === 2) matchesFilter = true;
        
        if (matchesFilter) {
            filteredQuestions.push(i);
        }
    }
    
    updateSidebarVisibility();
    
    if (filteredQuestions.length > 0) {
        if (!filteredQuestions.includes(currentQuestion)) {
            goToQuestion(filteredQuestions[0]);
        } else {
            showQuestion(currentQuestion);
        }
    } else {
        hideAllQuestions();
    }
}
```

### Dynamic Status and Correctness Tracking
```javascript
// Global arrays for real-time tracking
let status = {}; // Dynamic status array (0=unseen, 1=incomplete, 2=answered)
let correct = {}; // Dynamic correct array (0=incorrect, 1=correct, 2=first-time correct)
let markedforreview = {}; // Dynamic markedforreview array (0=not marked, 1=marked)

// Initialize from database values
function initializeStatusData() {
    // Load from SAVED_STATUS, SAVED_CORRECT, SAVED_MARKEDFORREVIEW
    status = {};
    for (let key in SAVED_STATUS) {
        status[key] = parseInt(SAVED_STATUS[key]) || 0;
    }
    
    correct = {};
    for (let key in SAVED_CORRECT) {
        correct[key] = parseInt(SAVED_CORRECT[key]) || 0;
    }
    
    markedforreview = {};
    for (let key in SAVED_MARKEDFORREVIEW) {
        markedforreview[key] = parseInt(SAVED_MARKEDFORREVIEW[key]) || 0;
    }
}

// Update arrays when user interacts
function showAnswerFeedback(questionNum) {
    const currentStatus = status[questionNum] || 0;
    const currentCorrect = correct[questionNum] || 0;
    
    // Determine correct status based on current values
    if (currentStatus === 0) {
        correct[questionNum] = 2; // First-time correct
    } else if (currentStatus === 2 && currentCorrect === 0) {
        correct[questionNum] = 1; // Now correct (was incorrect before)
    }
    
    status[questionNum] = 2; // Mark as answered
}
```

### Filter Menu Structure
```html
<!-- Radio button-based filter menu -->
<div class="filter-menu" id="filter-menu" style="display: none;">
    <div class="filter-menu-header">
        <span class="filter-menu-title">Set Filters</span>
        <button class="filter-close-btn" id="filter-close-btn" title="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="filter-menu-content">
        <div class="filter-radios">
            <label class="filter-radio-item">
                <input type="radio" id="filter-incomplete" name="filter-option" value="incomplete">
                <span class="filter-radio-text">Unanswered Questions (0)</span>
            </label>
            <label class="filter-radio-item">
                <input type="radio" id="filter-marked" name="filter-option" value="marked">
                <span class="filter-radio-text">Marked Questions (0)</span>
            </label>
            <label class="filter-radio-item">
                <input type="radio" id="filter-incorrect" name="filter-option" value="incorrect">
                <span class="filter-radio-text">Incorrect Questions (0)</span>
            </label>
            <label class="filter-radio-item">
                <input type="radio" id="filter-none" name="filter-option" value="none" checked>
                <span class="filter-radio-text">No Filters</span>
            </label>
        </div>
    </div>
</div>
```

### Status and Correct Logic
```javascript
// Status values:
// 0 = Unseen/Unanswered (not responded to)
// 1 = Incomplete (responded but not fully)
// 2 = Answered (fully responded)

// Correct values:
// 0 = Incorrect answer
// 1 = Correct answer (not first time)
// 2 = Correct answer (first time)

// Filter logic:
// Unanswered Questions: status === 0
// Marked Questions: markedforreview === 1
// Incorrect Questions: correct === 0 AND status === 2 (answered but wrong)
```

### Timer Logic
```php
// Time calculation: (total_questions × 1 minute) - timeremaining = time_taken
$time_taken_seconds = ($total_questions * 60) - $practice_exam->timeremaining;
$time_taken_seconds = max(0, $time_taken_seconds); // Handle negative values
```

### Dynamic Button Switching
```javascript
// Calculator ↔ Instructions based on review mode
function changeButtonToInstructions() {
    const calculatorBtn = document.getElementById('calculator');
    const instructionsBtn = calculatorBtn.cloneNode(true);
    instructionsBtn.onclick = openInstructions;
    instructionsBtn.innerHTML = '<i class="fas fa-info-circle"></i>';
    calculatorBtn.parentNode.replaceChild(instructionsBtn, calculatorBtn);
}
```

### Session Management
```php
// Use Moodle session for multi-step processes
$SESSION->revitimsession_practice_data['selected_categories'] = $selected_categories;
$SESSION->revitimsession_practice_data['question_count'] = $question_count;
```

### Auto-Creation of Resources
```php
// Observer pattern for automatic resource creation
// When a new course is created, automatically add a Revit IM Session resource
public static function course_created(\core\event\course_created $event) {
    // Creates a "Practice Exams" resource in section 0 of new courses
    // Similar to how Moodle creates the Announcements forum automatically
}
```

## Recent Work Context

### Latest Changes (2025091948)
- **Fixed**: Filter logic for "Unanswered Questions" - Now correctly filters by `status === 0`
- **Fixed**: Filter logic for "Incorrect Questions" - Now correctly filters by `correct === 0 AND status === 2`
- **Fixed**: Question marking for review functionality - Now properly saves to database
- **Implemented**: Dynamic arrays for real-time tracking (`status`, `correct`, `markedforreview`)
- **Added**: Global array initialization from database values
- **Fixed**: Navigation button states - Previous/Next buttons disabled on first/last questions
- **Removed**: Section review functionality - Replaced with Test Bank navigation
- **Updated**: All filter counts to reflect correct logic
- **Cleaned**: Removed all debug messages and console.log statements

### Previous Major Changes (2025091940-1947)
- **Added**: Advanced Filter System - Complete filtering functionality for study sessions
- **Created**: Filter Menu - Radio button-based filter system with single selection
- **Added**: Filter Options - No Filters, Unanswered Questions, Marked Questions, Incorrect Questions
- **Implemented**: Filtered Navigation - Previous/Next buttons navigate only through filtered questions
- **Added**: Dynamic Sidebar - Sidebar shows only questions matching the active filter
- **Updated**: `perform_study.mustache` - Added complete filter menu HTML structure with radio buttons
- **Updated**: `styles.css` - Added comprehensive filter menu styling with hover effects and responsive design
- **Updated**: `perform_study.js` - Added filter functionality with `applyFilter()`, `updateFilterCounts()`, `updateSidebarVisibility()`
- **Added**: Filter Logic - `filteredQuestions[]` array stores questions matching active filter
- **Added**: Navigation Functions - `getNextFilteredQuestion()` and `getPreviousFilteredQuestion()` for filtered navigation
- **Updated**: Button States - Previous/Next buttons are enabled/disabled based on filtered question availability
- **Fixed**: Question visibility issues when switching between filters
- **Added**: Utility functions for safe DOM manipulation

### Study Session Implementation (2025091606)
- **Added**: Study Session functionality - complete implementation of study sessions alongside practice exams
- **Created**: `perform_study.php` - Main study session interface (copy of perform_exam.php with adaptations)
- **Created**: `perform_study.js` - JavaScript functionality for study sessions (adapted from perform_exam.js)
- **Created**: `perform_study.mustache` - HTML template for study sessions (adapted from perform_exam.mustache)
- **Updated**: Database schema - Added `studysession` field (INT(1), default 0) to `revitimsession_practice_exams` table
- **Updated**: `view.php` - Added separate "Create Study Session" and "Resume Study Session" buttons with proper logic
- **Updated**: `create_practice_step*.php` - Added `type` parameter support for differentiating practice exams vs study sessions
- **Updated**: `create_practice_step3.php` - Study sessions now complete at step 3 (redirect to perform_study.php)
- **Updated**: `create_practice_step4.php` - Only handles practice exams (study sessions handled in step 3)
- **Updated**: All step templates - Added dynamic titles and conditional "Finish" vs "Next" buttons
- **Updated**: Language strings - Added study session related strings in `lang/en/revitimsession.php`

## Current Working State
- **All major features implemented and functional**
- **Advanced filter system working correctly**
- **Dynamic status and correctness tracking operational**
- **Database schema optimized and complete**
- **UI/UX polished with responsive design**
- **Error handling and security measures in place**
- **Documentation updated and comprehensive**

## Common Issues & Solutions

### Filter Not Showing Correct Questions
- **Cause**: Incorrect filter logic in `applyFilter()` function
- **Solution**: Verify status and correct array values and filter conditions

### Questions Not Saving Marked Status
- **Cause**: Using local arrays instead of global arrays
- **Solution**: Ensure `markedforreview` global array is updated in `toggleReviewFlag()`

### Navigation Buttons Not Working with Filters
- **Cause**: Not using filtered navigation functions
- **Solution**: Use `getNextFilteredQuestion()` and `getPreviousFilteredQuestion()`

### Timer Negative Values
- **Expected Behavior**: Negative values indicate time exceeded
- **Handling**: Use `max(0, $time_taken_seconds)` in calculations

### Calculator Button Not Working
- **Cause**: Missing event listeners or CSS
- **Solution**: Ensure `DOMContentLoaded` event listener and proper CSS classes

## Development Workflow

### When Adding Features
1. Update language strings in `lang/en/revitimsession.php`
2. Increment version in `version.php`
3. Update database schema if needed in `db/install.xml`
4. Test thoroughly before committing
5. Update this context file

### When Fixing Issues
1. Identify the specific file and function
2. Test the fix in isolation
3. Update version if strings or schema changed
4. Document the fix in this context file

### Code Quality Standards
- **No debug messages in production code**
- **Consistent error handling with try-catch blocks**
- **User-friendly error messages via language strings**
- **Security validation for all user inputs**
- **Database transactions for data integrity**
- **Use global arrays for dynamic data tracking**

## File Dependencies

### Core Files (Don't Break)
- `version.php` - Plugin version and dependencies
- `lib.php` - Core Moodle integration
- `db/install.xml` - Database schema
- `lang/en/revitimsession.php` - Language strings

### Feature Files
- **Create Practice**: `create_practice_step*.php` + templates (supports both practice exams and study sessions)
- **Perform Exam**: `perform_exam.php`, `javascript/perform_exam.js`, `templates/perform_exam.mustache`, `grade_exam.php`
- **Perform Study**: `perform_study.php`, `javascript/perform_study.js`, `templates/perform_study.mustache`, `grade_exam.php`
- **Statistics**: `stats.php`, `templates/stats.mustache`

### Filter System Files
- `javascript/perform_study.js` - Contains all filter logic and navigation
- `templates/perform_study.mustache` - Contains filter menu HTML structure
- `styles.css` - Contains filter menu styling

## Testing Scenarios

### Practice Exam Creation
1. Select categories → Step 2
2. Choose question count → Step 3
3. Review selections → Step 4
4. Verify database records created

### Study Session Creation
1. Click "Create Study Session" → Step 1 with type=study
2. Select categories → Step 2 (with type parameter)
3. Choose question count → Step 3 (with type parameter)
4. Click "Finish" → Redirects to perform_study.php
5. Verify database records created with studysession=1

### Study Session with Filters
1. Start study session → Answer some questions
2. Mark some questions for review → Verify marking works
3. Open filter menu → Test each filter option
4. Verify filtered navigation → Previous/Next should respect filters
5. Test "No Filters" → Should show all questions

### Exam Performance
1. Start exam → Timer starts
2. Answer questions → Status updates
3. Use review mode → Button switches
4. Grade exam → Redirects to stats

### Statistics Display
1. Access without examid → Finds latest exam
2. Check all tabs → Grade Report, Study Session, Exam Session, History
3. Verify calculations → Time, scores, percentages

## Migration Instructions

### For New Computer Setup
1. **Copy entire plugin directory** to new Moodle installation
2. **Verify database schema** - Run Moodle upgrade if needed
3. **Check language strings** - Ensure all strings are present
4. **Test filter functionality** - Verify all filter options work
5. **Test study sessions** - Create and complete a study session
6. **Verify statistics** - Check that stats display correctly

### For New Course/Context
1. **Share this CONTEXT.md file** with the new developer
2. **Explain the filter system** - Radio button-based filtering
3. **Explain status arrays** - Global JavaScript arrays for tracking
4. **Show testing scenarios** - Use the testing section above
5. **Explain common issues** - Review the solutions section

### Key Concepts to Understand
1. **Dynamic Arrays**: `status`, `correct`, `markedforreview` are global arrays updated in real-time
2. **Filter Logic**: Uses radio buttons with automatic application
3. **Navigation**: Previous/Next buttons respect active filters
4. **Database**: Study sessions use same tables with `studysession=1` flag
5. **Status Values**: 0=unseen, 1=incomplete, 2=answered
6. **Correct Values**: 0=incorrect, 1=correct, 2=first-time correct

## Future Considerations

### Potential Enhancements
- **Additional Filters**: Correct questions, first-time correct questions
- **Export Features**: PDF reports, CSV data export
- **Advanced Analytics**: Performance trends, weak areas identification
- **Study Session Analytics**: Separate performance tracking for study vs practice sessions
- **Filter Persistence**: Remember last used filter across sessions

### Maintenance Notes
- **Database**: Monitor performance with large question banks
- **JavaScript**: Consider bundling for production
- **CSS**: Maintain responsive design across devices
- **Security**: Regular review of user input validation
- **Filter Logic**: Keep filter conditions synchronized with status arrays

---

## Quick Recovery Commands

```bash
# Check current version
grep "version" version.php

# Verify database schema
cat db/install.xml

# Check language strings
grep -r "get_string" *.php

# Test filter functionality
grep -r "applyFilter" javascript/perform_study.js

# Check status array initialization
grep -r "initializeStatusData" javascript/perform_study.js

# Verify filter menu HTML
grep -r "filter-menu" templates/perform_study.mustache
```

## Critical Files for Migration
- `CONTEXT.md` - This file (complete context)
- `version.php` - Current version: 2025091948
- `javascript/perform_study.js` - Contains all filter and status logic
- `templates/perform_study.mustache` - Contains filter menu HTML
- `styles.css` - Contains filter menu styling
- `perform_study.php` - Main study session interface
- `lang/en/revitimsession.php` - All language strings

This context file should be updated whenever significant changes are made to maintain continuity in development.