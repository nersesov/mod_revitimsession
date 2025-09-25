# Revit IM Session - Moodle Plugin

A comprehensive Moodle plugin for creating and managing practice exams with advanced features including timed sessions, review modes, and detailed performance analytics.

## Table of Contents

1. [How to Install](#how-to-install)
2. [Database Description](#database-description)
3. [Create Practice Code Description](#create-practice-code-description)
4. [Perform Exam Code Description](#perform-exam-code-description)
5. [Perform Study Code Description](#perform-study-code-description)
6. [Performance Stats Code Description](#performance-stats-code-description)

## How to Install

### Prerequisites
- Moodle 4.5.0 or higher
- PHP 8.0 or higher
- MySQL/MariaDB database

### Installation Steps

1. **Download the Plugin**
   ```bash
   # Clone or download the plugin to your Moodle installation
   cd /path/to/moodle/mod/
   git clone [repository-url] revitimsession
   ```

2. **Install via Moodle Admin**
   - Log in to your Moodle site as an administrator
   - Navigate to Site Administration → Plugins → Install plugins
   - Upload the plugin ZIP file or install from directory
   - Follow the installation wizard

3. **Manual Installation**
   ```bash
   # Copy files to mod directory
   cp -r revitimsession /path/to/moodle/mod/
   
   # Set proper permissions
   chmod -R 755 /path/to/moodle/mod/revitimsession
   chown -R www-data:www-data /path/to/moodle/mod/revitimsession
   ```

4. **Database Installation**
   - The plugin will automatically create required database tables
   - Database schema is defined in `db/install.xml`
   - Version tracking is handled in `version.php`

5. **Post-Installation**
   - Clear Moodle cache: Site Administration → Development → Purge all caches
   - Add the activity to a course: Add an activity or resource → Revit IM Session

## Database Description

### Core Tables

#### `revitimsession_practice_exams`
Stores practice exam and study session records for each user.

| Field | Type | Description |
|-------|------|-------------|
| `id` | int(10) | Primary key, auto-increment |
| `userid` | int(10) | User ID (foreign key to user table) |
| `courseid` | int(10) | Course ID (foreign key to course table) |
| `status` | int(1) | Exam status: 0=not finished, 1=finished |
| `timeremaining` | int(10) | Time remaining in seconds (can be negative if exceeded) |
| `totalquestions` | int(10) | Total number of questions in this exam |
| `studysession` | int(1) | Session type: 0=practice exam, 1=study session |
| `timecreated` | int(10) | Timestamp when exam was created |
| `timefinished` | int(10) | Timestamp when exam was finished |

**Indexes:**
- Primary key on `id`
- Foreign keys on `userid` and `courseid`
- Indexes on `status`, `timeremaining`, and composite indexes for performance

#### `revitimsession_practice_exam_questions`
Stores individual questions for each practice exam.

| Field | Type | Description |
|-------|------|-------------|
| `id` | int(10) | Primary key, auto-increment |
| `practiceexamid` | int(10) | Foreign key to practice_exams table |
| `questionid` | int(10) | Foreign key to Moodle question table |
| `questionorder` | int(10) | Order of question in exam (1, 2, 3...) |
| `answer` | int(10) | User's selected answer ID (nullable) |
| `status` | int(1) | Question status: 0=unseen, 1=incomplete, 2=complete |
| `markedforreview` | int(1) | Marked for review: 0=no, 1=yes |
| `correct` | int(1) | Answer correctness: 0=incorrect, 1=correct |

**Indexes:**
- Primary key on `id`
- Foreign keys on `practiceexamid`, `questionid`, and `answer`
- Unique index on `practiceexamid, questionid`
- Indexes on `questionorder`, `status`, and `markedforreview`

### Additional Tables

#### `revitimsession`
Main activity instance table (standard Moodle module table).

#### `revitimsession_participants`
Session participation records.

#### `revitimsession_submissions`
Work submissions by participants.

#### `revitimsession_grades`
Grades for session submissions.

## Create Practice Code Description

### Files Participating in Create Practice Function

- `create_practice_step1.php` - Category selection
- `create_practice_step2.php` - Question count selection  
- `create_practice_step3.php` - Review and confirmation
- `create_practice_step4.php` - Exam creation and database insertion
- `templates/create_practice_step1.mustache` - Category selection template
- `templates/create_practice_step2.mustache` - Question count selection template
- `templates/create_practice_step3.mustache` - Review and confirmation template
- `templates/create_practice_step4.mustache` - Exam creation template
- `javascript/create_practice_step1.js` - Client-side functionality for category selection

## Perform Exam Code Description

### Files Participating in Perform Exam Function

- `perform_exam.php` - Main exam interface and logic
- `javascript/perform_exam.js` - Client-side functionality
- `templates/perform_exam.mustache` - HTML template
- `grade_exam.php` - Grading endpoint
- `styles.css` - Styling for exam interface

## Perform Study Code Description

### Files Participating in Perform Study Function

- `perform_study.php` - Main study session interface and logic
- `javascript/perform_study.js` - Client-side functionality for study sessions
- `templates/perform_study.mustache` - HTML template for study sessions
- `grade_exam.php` - Grading endpoint (shared with practice exams)
- `styles.css` - Styling for study session interface

### Study Session Features

Study sessions share the same core functionality as practice exams but are designed for learning and practice rather than formal assessment:

- **Timed Sessions**: Same timer functionality as practice exams
- **Question Navigation**: Full navigation between questions with review capabilities
- **Review Modes**: Review all, incomplete, or marked questions
- **Calculator**: Built-in calculator for mathematical questions
- **Save & Resume**: Ability to save progress and resume later
- **Section Review**: Comprehensive review of all questions before submission
- **Performance Tracking**: Detailed statistics and performance analysis

### Database Integration

Study sessions use the same database tables as practice exams:
- `revitimsession_practice_exams` with `studysession` field set to `1`
- `revitimsession_practice_exam_questions` for individual question tracking
- Same grading and statistics system as practice exams

## Performance Stats Code Description

### Files Participating in Performance Stats Function

- `stats.php` - Performance analysis and statistics
- `templates/stats.mustache` - Statistics display template
- `styles.css` - Styling for statistics interface

## Support

For issues and questions, please refer to the Moodle plugin documentation or contact the development team.
