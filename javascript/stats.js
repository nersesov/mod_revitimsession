// Stats page JavaScript functionality
// This file contains all JavaScript logic for the stats page

/**
 * =============================================================================
 * CONSTANTS AND CONFIGURATION
 * =============================================================================
 */

// CSS Selectors
const SELECTORS = {
    // Main containers
    STATS_CONTAINER: '.stats-container',
    PIE_GRAPH: '.pie-graph.large-graph',
    
    // Tab elements
    TAB_BUTTONS: '.tab-button',
    TAB_PANELS: '.tab-panel',
    
    // History elements
    HISTORY_ROWS: '.history-row',
    HISTORY_CHECKBOXES: '#history-panel input[type="checkbox"]',
    DONUT_CHARTS: '.history-donut-chart',
    HISTORY_PERCENTAGE_TEXT: '.history-percentage-text',
    
    // Collapse elements
    COLLAPSE_BUTTONS: '.collapse-options',
    PLUS_ICON: '.fa-plus-square',
    MINUS_ICON: '.fa-minus-square',
    
    // Chart elements
    CHART_PERCENTAGE_TEXT: '.history-percentage-text',
    
    // Study Session elements
    STUDY_SESSION_PROGRESS_INDICATORS: '.progress-indicator'
};

// Element IDs
const ELEMENT_IDS = {
    SHOW_LAST_3: 'show-last-3',
    STUDY_SESSIONS_ONLY: 'study-sessions-only',
    PRACTICE_EXAMS_ONLY: 'practice-exams-only'
};

// CSS Classes
const CSS_CLASSES = {
    // Tab classes
    ACTIVE_TAB: 'active-tab',
    ACTIVE_PANEL: 'active',
    
    // Visibility classes
    VISIBLE: 'visible',
    HIDDEN: 'hidden',
    SHOW: 'show',
    HIDE: 'hide',
    
};

// CSS Custom Properties
const CSS_PROPERTIES = {
    PIE_PROGRESS_DEGREES: '--pie-progress-degrees',
    HISTORY_PROGRESS_DEGREES: '--history-progress-degrees'
};

// Data Attributes
const DATA_ATTRIBUTES = {
    SCORE_PERCENTAGE: 'data-score-percentage',
    TAB: 'data-tab',
    TARGET: 'data-target',
    IS_STUDY: 'isStudy'
};


// Configuration
const CONFIG = {
    MAX_LAST_3_ITEMS: 3,
    DEGREES_PER_PERCENTAGE: 3.6 // 360 / 100
};


/**
 * Apply CSS custom property to an element
 * @param {Element} element - The DOM element
 * @param {string} property - The CSS custom property name
 * @param {string|number} value - The value to set
 */
function setCSSProperty(element, property, value) {
    if (element && property && value !== undefined && value !== null) {
        try {
            element.style.setProperty(property, value);
        } catch (error) {
            console.error('Error setting CSS property:', error);
        }
    }
}


/**
 * Toggle CSS classes on an element
 * @param {Element} element - The DOM element
 * @param {string} addClass - Class to add
 * @param {string} removeClass - Class to remove
 */
function toggleClasses(element, addClass, removeClass) {
    if (element && element.classList) {
        try {
            if (removeClass && typeof removeClass === 'string') {
                element.classList.remove(removeClass);
            }
            if (addClass && typeof addClass === 'string') {
                element.classList.add(addClass);
            }
        } catch (error) {
            console.error('Error toggling CSS classes:', error);
        }
    }
}

/**
 * Show/hide element using CSS classes
 * @param {Element} element - The DOM element
 * @param {boolean} show - Whether to show or hide
 * @param {string} showClass - Class for showing (default: 'visible')
 * @param {string} hideClass - Class for hiding (default: 'hidden')
 */
function toggleVisibility(element, show, showClass = CSS_CLASSES.VISIBLE, hideClass = CSS_CLASSES.HIDDEN) {
    if (element) {
        if (show) {
            toggleClasses(element, showClass, hideClass);
        } else {
            toggleClasses(element, hideClass, showClass);
        }
    }
}

/**
 * Add event listener with error handling
 * @param {Element} element - The DOM element
 * @param {string} event - The event type
 * @param {Function} handler - The event handler
 */
function addEventListenerSafe(element, event, handler) {
    if (element && typeof handler === 'function' && typeof event === 'string') {
        try {
            element.addEventListener(event, handler);
        } catch (error) {
            console.error('Error adding event listener:', error);
        }
    }
}

/**
 * Add event listeners to multiple elements
 * @param {NodeList|Array} elements - The elements
 * @param {string} event - The event type
 * @param {Function} handler - The event handler
 */
function addEventListenerToAll(elements, event, handler) {
    if (elements && elements.forEach && typeof event === 'string' && typeof handler === 'function') {
        try {
            elements.forEach(element => addEventListenerSafe(element, event, handler));
        } catch (error) {
            console.error('Error adding event listeners to multiple elements:', error);
        }
    }
}

/**
 * =============================================================================
 * UTILITY FUNCTIONS
 * =============================================================================
 */

/**
 * Safely get element by ID with validation
 * @param {string} id - The element ID
 * @returns {Element|null} - The element or null if not found
 */
function getElementByIdSafe(id) {
    if (!id || typeof id !== 'string') {
        console.warn('Invalid ID provided to getElementByIdSafe:', id);
        return null;
    }
    
    try {
        return document.getElementById(id);
    } catch (error) {
        console.error('Error getting element by ID:', error);
        return null;
    }
}

/**
 * Safely get elements by selector with validation
 * @param {string} selector - The CSS selector
 * @param {Element} context - The context element (optional)
 * @returns {NodeList} - The elements found (empty if none)
 */
function getElementsBySelectorSafe(selector, context = document) {
    if (!selector || typeof selector !== 'string') {
        console.warn('Invalid selector provided to getElementsBySelectorSafe:', selector);
        return [];
    }
    
    try {
        return context.querySelectorAll(selector);
    } catch (error) {
        console.error('Error getting elements by selector:', error);
        return [];
    }
}

/**
 * Calculate degrees from percentage
 * @param {number} percentage - The percentage value (0-100)
 * @returns {number} - The degrees value (0-360)
 */
function percentageToDegrees(percentage) {
    if (typeof percentage !== 'number' || isNaN(percentage)) {
        console.warn('Invalid percentage value:', percentage);
        return 0;
    }
    
    // Clamp percentage between 0 and 100
    const clampedPercentage = Math.max(0, Math.min(100, percentage));
    return clampedPercentage * CONFIG.DEGREES_PER_PERCENTAGE;
}

/**
 * =============================================================================
 * CHART INITIALIZATION FUNCTIONS
 * =============================================================================
 * 
 * These functions handle the initialization and configuration of various chart
 * elements including pie charts, donut charts, and progress indicators.
 * All functions include error handling and validation for robust operation.
 */

/**
 * Initialize pie chart with score percentage
 * 
 * Sets up the main pie chart display with the provided score percentage.
 * The percentage is converted to degrees and applied as a CSS custom property.
 * 
 * @param {number} scorePercentage - The score percentage to display (0-100)
 * @throws {Error} If the pie graph element is not found or invalid percentage
 * @example
 * initializePieChart(85); // Sets pie chart to 85%
 */
function initializePieChart(scorePercentage) {
    try {
        const pieGraph = document.querySelector(SELECTORS.PIE_GRAPH);
        if (pieGraph && scorePercentage !== undefined && scorePercentage !== null) {
            const degrees = percentageToDegrees(scorePercentage);
            setCSSProperty(pieGraph, CSS_PROPERTIES.PIE_PROGRESS_DEGREES, degrees + 'deg');
        } else if (!pieGraph) {
            console.warn('Pie graph element not found');
        }
    } catch (error) {
        console.error('Error initializing pie chart:', error);
    }
}

/**
 * =============================================================================
 * TAB FUNCTIONALITY
 * =============================================================================
 */

/**
 * Handle tab switching functionality
 */
function initializeTabSwitching() {
    const tabButtons = document.querySelectorAll(SELECTORS.TAB_BUTTONS);
    const tabPanels = document.querySelectorAll(SELECTORS.TAB_PANELS);
    
    addEventListenerToAll(tabButtons, 'click', function() {
        const targetTab = this.getAttribute(DATA_ATTRIBUTES.TAB);
            
            // Remove active class from all tabs and panels
            tabButtons.forEach(btn => {
            btn.classList.remove(CSS_CLASSES.ACTIVE_TAB);
                btn.setAttribute('aria-selected', 'false');
            });
            tabPanels.forEach(panel => {
            panel.classList.remove(CSS_CLASSES.ACTIVE_PANEL);
            });
            
            // Add active class to clicked tab and corresponding panel
        this.classList.add(CSS_CLASSES.ACTIVE_TAB);
            this.setAttribute('aria-selected', 'true');
        const targetPanel = document.getElementById(targetTab + '-panel');
        if (targetPanel) {
            targetPanel.classList.add(CSS_CLASSES.ACTIVE_PANEL);
        }
    });
}

/**
 * =============================================================================
 * COLLAPSE/EXPAND FUNCTIONALITY
 * =============================================================================
 */

/**
 * Handle collapse/expand functionality
 */
function initializeCollapseExpand() {
    const collapseButtons = document.querySelectorAll(SELECTORS.COLLAPSE_BUTTONS);
    
    addEventListenerToAll(collapseButtons, 'click', function() {
        const target = this.getAttribute(DATA_ATTRIBUTES.TARGET);
        const targetElement = document.querySelector(target);
        const plusIcon = this.querySelector(SELECTORS.PLUS_ICON);
        const minusIcon = this.querySelector(SELECTORS.MINUS_ICON);
        
        if (targetElement && targetElement.classList.contains(CSS_CLASSES.SHOW)) {
            targetElement.classList.remove(CSS_CLASSES.SHOW);
            if (plusIcon) plusIcon.classList.remove(CSS_CLASSES.HIDE);
            if (minusIcon) minusIcon.classList.add(CSS_CLASSES.HIDE);
            } else {
            if (targetElement) targetElement.classList.add(CSS_CLASSES.SHOW);
            if (plusIcon) plusIcon.classList.add(CSS_CLASSES.HIDE);
            if (minusIcon) minusIcon.classList.remove(CSS_CLASSES.HIDE);
        }
    });
}

/**
 * =============================================================================
 * HISTORY FILTERS FUNCTIONALITY
 * =============================================================================
 */

/**
 * Apply history filters based on checkbox states
 * 
 * This function implements a two-stage filtering system:
 * 1. First filters by session type (Study Sessions vs Practice Exams)
 * 2. Then applies "Last 3" filter if enabled
 * 
 * @param {NodeList} historyRows - All history row elements
 */
function applyHistoryFilters(historyRows) {
    // Get checkbox elements directly
    const showLast3Checkbox = document.getElementById(ELEMENT_IDS.SHOW_LAST_3);
    const studySessionsCheckbox = document.getElementById(ELEMENT_IDS.STUDY_SESSIONS_ONLY);
    const practiceExamsCheckbox = document.getElementById(ELEMENT_IDS.PRACTICE_EXAMS_ONLY);
    
    // Read current checkbox states
    const showLast3 = showLast3Checkbox?.checked || false;
    const studySessionsOnly = studySessionsCheckbox?.checked || false;
    const practiceExamsOnly = practiceExamsCheckbox?.checked || false;
        
        // STAGE 1: Filter by session type (Study Sessions vs Practice Exams)
        let matchingRows = [];
        historyRows.forEach((row, index) => {
            let shouldShow = true;
            
            // Apply session type filters (mutually exclusive)
            if (studySessionsOnly) {
                // Only show study sessions (isStudy = 'true' or '1')
                shouldShow = row.dataset.isStudy === 'true' || row.dataset.isStudy === '1';
            } else if (practiceExamsOnly) {
                // Only show practice exams (isStudy = 'false' or '0')
                shouldShow = row.dataset.isStudy === 'false' || row.dataset.isStudy === '0';
            } else if (!studySessionsOnly && !practiceExamsOnly) {
                // Neither checkbox checked - show all rows
                shouldShow = true;
            }
            
            if (shouldShow) {
                matchingRows.push(row);
            }
        });
        
        // STAGE 2: Apply "Last 3" filter if enabled
        let rowsToShow = matchingRows;
        if (showLast3 && matchingRows.length > CONFIG.MAX_LAST_3_ITEMS) {
            // Get the last 3 elements from the matching rows
            rowsToShow = matchingRows.slice(-CONFIG.MAX_LAST_3_ITEMS);
        }
    
    // Show/hide rows using CSS classes
    historyRows.forEach(row => {
        const shouldShow = rowsToShow.includes(row);
        toggleVisibility(row, shouldShow);
    });
}

/**
 * Initialize history filters functionality
 */
function initializeHistoryFilters() {
    const historyFilterCheckboxes = document.querySelectorAll(SELECTORS.HISTORY_CHECKBOXES);
    const historyRows = document.querySelectorAll(SELECTORS.HISTORY_ROWS);
    
    // Get checkbox elements directly
    const practiceExamsCheckbox = document.getElementById(ELEMENT_IDS.PRACTICE_EXAMS_ONLY);
    const studySessionsCheckbox = document.getElementById(ELEMENT_IDS.STUDY_SESSIONS_ONLY);
    
    addEventListenerToAll(historyFilterCheckboxes, 'change', function() {
        // Make Study Sessions and Practice Exams mutually exclusive
        if (this.id === ELEMENT_IDS.STUDY_SESSIONS_ONLY && this.checked) {
            if (practiceExamsCheckbox) practiceExamsCheckbox.checked = false;
        } else if (this.id === ELEMENT_IDS.PRACTICE_EXAMS_ONLY && this.checked) {
            if (studySessionsCheckbox) studySessionsCheckbox.checked = false;
        }
        
        applyHistoryFilters(historyRows);
    });
}

/**
 * Initialize a chart element with percentage-based progress
 * @param {Element} chart - The chart element
 * @param {string} cssProperty - The CSS custom property to set
 * @param {string} percentageSelector - Selector to find percentage text
 */
function initializeChartWithPercentage(chart, cssProperty, percentageSelector) {
    const percentText = chart.parentElement.querySelector(percentageSelector);
    if (percentText) {
        const percent = parseInt(percentText.textContent.replace('%', ''));
        const degrees = percentageToDegrees(percent);
        setCSSProperty(chart, cssProperty, degrees + 'deg');
    }
}

/**
 * Initialize a single donut chart from percentage text
 * @param {Element} chart - The donut chart element
 */
function initializeDonutChart(chart) {
    initializeChartWithPercentage(chart, CSS_PROPERTIES.HISTORY_PROGRESS_DEGREES, SELECTORS.HISTORY_PERCENTAGE_TEXT);
}

/**
 * Initialize history donut charts
 */
function initializeHistoryDonutCharts() {
    const donutCharts = document.querySelectorAll(SELECTORS.DONUT_CHARTS);
    donutCharts.forEach(initializeDonutChart);
}

/**
 * =============================================================================
 * STUDY SESSION FUNCTIONALITY
 * =============================================================================
 * 
 * Functions for handling Study Session and Exam Session specific interactions
 * including subcategory toggling, progress indicators, and donut charts.
 * All functions include comprehensive error handling and validation.
 */

/**
 * Toggle subcategories visibility (similar to create_practice_step1)
 * @param {string} parentId - The parent category ID
 */
function toggleSubcategories(parentId) {
    if (!parentId || typeof parentId !== 'string') {
        console.warn('Invalid parentId for toggleSubcategories:', parentId);
        return;
    }
    
    try {
        const subcategories = document.getElementById('sub-' + parentId);
        const icon = document.getElementById('icon-' + parentId);
        
        if (subcategories && icon) {
            if (subcategories.classList.contains('stats-visible')) {
                subcategories.classList.remove('stats-visible');
                icon.textContent = '+';
            } else {
                subcategories.classList.add('stats-visible');
                icon.textContent = '-';
            }
        } else {
            console.warn('Elements not found for parentId:', parentId);
        }
    } catch (error) {
        console.error('Error toggling subcategories:', error);
    }
}

/**
 * Toggle Study Session subcategories visibility
 * @param {string} parentId - The parent category ID
 */
function toggleStudySubcategories(parentId) {
    if (!parentId || typeof parentId !== 'string') {
        console.warn('Invalid parentId for toggleStudySubcategories:', parentId);
        return;
    }
    
    try {
        const subcategories = document.getElementById('study-sub-' + parentId);
        const icon = document.getElementById('study-icon-' + parentId);
        
        if (subcategories && icon) {
            if (subcategories.classList.contains('stats-visible')) {
                subcategories.classList.remove('stats-visible');
                icon.textContent = '+';
            } else {
                subcategories.classList.add('stats-visible');
                icon.textContent = '-';
            }
        } else {
            console.warn('Study Session elements not found for parentId:', parentId);
        }
    } catch (error) {
        console.error('Error toggling Study Session subcategories:', error);
    }
}

/**
 * Toggle Exam Session subcategories visibility
 * @param {string} parentId - The parent category ID
 */
function toggleExamSubcategories(parentId) {
    if (!parentId || typeof parentId !== 'string') {
        console.warn('Invalid parentId for toggleExamSubcategories:', parentId);
        return;
    }
    
    try {
        const subcategories = document.getElementById('exam-sub-' + parentId);
        const icon = document.getElementById('exam-icon-' + parentId);
        
        if (subcategories && icon) {
            if (subcategories.classList.contains('stats-visible')) {
                subcategories.classList.remove('stats-visible');
                icon.textContent = '+';
            } else {
                subcategories.classList.add('stats-visible');
                icon.textContent = '-';
            }
        } else {
            console.warn('Exam Session elements not found for parentId:', parentId);
        }
    } catch (error) {
        console.error('Error toggling Exam Session subcategories:', error);
    }
}

/**
 * Toggle Grade Report "Other Quizzes" section visibility
 */
function toggleGradeOtherQuizzes() {
    try {
        const content = document.getElementById('grade-other-quizzes-content');
        const icon = document.getElementById('grade-icon-other-quizzes');
        
        if (content && icon) {
            if (content.classList.contains('stats-visible')) {
                content.classList.remove('stats-visible');
                icon.textContent = '+';
            } else {
                content.classList.add('stats-visible');
                icon.textContent = '-';
            }
        } else {
            console.warn('Grade Other Quizzes elements not found');
        }
    } catch (error) {
        console.error('Error toggling Grade Other Quizzes:', error);
    }
}

/**
 * Initialize progress indicators with CSS custom properties
 * Optimized with validation and error handling
 */
function initializeStudySessionProgressIndicators() {
    try {
        const progressIndicators = document.querySelectorAll(SELECTORS.STUDY_SESSION_PROGRESS_INDICATORS);
        
        // Only process if indicators are found (silent if none exist)
        if (progressIndicators.length === 0) {
            return; // Silent return - no warning needed
        }
        
        progressIndicators.forEach(indicator => {
            const percentage = indicator.getAttribute('data-percentage');
            if (percentage !== null && percentage !== '') {
                // Validate percentage value
                const numPercentage = parseFloat(percentage);
                if (!isNaN(numPercentage) && numPercentage >= 0 && numPercentage <= 100) {
                    setCSSProperty(indicator, '--progress-width', percentage + '%');
                } else {
                    console.warn('Invalid percentage value for progress indicator:', percentage);
                }
            }
        });
    } catch (error) {
        console.error('Error initializing Study Session progress indicators:', error);
    }
}
    
/**
 * Initialize study session functionality
 */
function initializeStudySession() {
    initializeStudySessionProgressIndicators();
    initializeStudySessionDonutCharts();
    initializeTableHelpModal();
}

/**
 * Initialize donut charts for Study Session
 * Optimized to batch DOM operations and validate data
 */
function initializeStudySessionDonutCharts() {
    try {
        const donutCharts = document.querySelectorAll('.study-session-table .history-donut-chart');
        
        // Only process if charts are found (silent if none exist)
        if (donutCharts.length === 0) {
            return; // Silent return - no warning needed
        }
        
        donutCharts.forEach(chart => {
            const percentageAttr = chart.getAttribute('data-percentage');
            if (percentageAttr !== null) {
                const percentage = parseInt(percentageAttr, 10);
                if (!isNaN(percentage)) {
                    const degrees = percentageToDegrees(percentage);
                    setCSSProperty(chart, '--history-progress-degrees', degrees + 'deg');
                } else {
                    console.warn('Invalid percentage value for chart:', percentageAttr);
                }
            }
        });
    } catch (error) {
        console.error('Error initializing Study Session donut charts:', error);
    }
}

/**
 * Initialize table help modal functionality
 */
function initializeTableHelpModal() {
    const helpLink = document.querySelector('.table-help-link');
    if (helpLink) {
        addEventListenerSafe(helpLink, 'click', function(e) {
            e.preventDefault();
            openTableHelpModal();
        });
    }
    
    // Add click outside modal to close functionality
    const modal = document.getElementById('table-help-modal');
    if (modal) {
        addEventListenerSafe(modal, 'click', function(e) {
            // Close modal if clicking on the overlay (not the modal content)
            if (e.target === modal) {
                closeTableHelpModal();
            }
        });
    }
}

/**
 * Open table help modal
 */
function openTableHelpModal() {
    const modal = document.getElementById('table-help-modal');
    if (modal) {
        modal.classList.add('stats-visible');
    }
}

/**
 * Close table help modal
 */
function closeTableHelpModal() {
    const modal = document.getElementById('table-help-modal');
    if (modal) {
        modal.classList.remove('stats-visible');
    }
}

// Make functions globally available for onclick handlers
window.closeTableHelpModal = closeTableHelpModal;

/**
 * =============================================================================
 * INITIALIZATION FUNCTIONS
 * =============================================================================
 */


/**
 * Main initialization function
 */
function initializeStatsPage() {
    // Initialize pie chart from data attribute
    const statsContainer = document.querySelector(SELECTORS.STATS_CONTAINER);
    if (statsContainer) {
        const scorePercentage = statsContainer.getAttribute(DATA_ATTRIBUTES.SCORE_PERCENTAGE);
        if (scorePercentage) {
            initializePieChart(parseInt(scorePercentage));
        }
    }
    
    // Initialize all components
    initializeTabSwitching();
    initializeCollapseExpand();
    initializeHistoryFilters();
    initializeHistoryDonutCharts();
    initializeStudySession();
}

/**
 * =============================================================================
 * EVENT LISTENERS FOR INLINE JAVASCRIPT REPLACEMENT
 * =============================================================================
 */

/**
 * Initialize event listeners for elements that previously had inline JavaScript
 */
function initializeInlineEventListeners() {
    // View Grade Report Button
    const viewGradeReportBtn = getElementsBySelectorSafe('.view-grade-report-btn')[0];
    if (viewGradeReportBtn) {
        viewGradeReportBtn.addEventListener('click', function() {
            const redirectUrl = this.getAttribute('data-redirect-url');
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        });
    }
    
    // Grade Other Quizzes Toggle
    const gradeOtherQuizzesBtn = getElementByIdSafe('grade-icon-other-quizzes');
    if (gradeOtherQuizzesBtn) {
        gradeOtherQuizzesBtn.addEventListener('click', function() {
            toggleGradeOtherQuizzes();
        });
    }
    
    // Grade Report Subcategories Toggle
    const gradeSubcategoryBtns = getElementsBySelectorSafe('[data-action="toggle-subcategories"]');
    gradeSubcategoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const parentId = this.getAttribute('data-parent-id');
            if (parentId) {
                toggleSubcategories(parentId);
            }
        });
    });
    
    // Study Session Subcategories Toggle
    const studySubcategoryBtns = getElementsBySelectorSafe('[data-action="toggle-study-subcategories"]');
    studySubcategoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const parentId = this.getAttribute('data-parent-id');
            if (parentId) {
                toggleStudySubcategories(parentId);
            }
        });
    });
    
    // Exam Session Subcategories Toggle
    const examSubcategoryBtns = getElementsBySelectorSafe('[data-action="toggle-exam-subcategories"]');
    examSubcategoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const parentId = this.getAttribute('data-parent-id');
            if (parentId) {
                toggleExamSubcategories(parentId);
            }
        });
    });
    
    // Table Help Modal Open Buttons
    const modalOpenBtns = getElementsBySelectorSafe('[data-action="open-table-help-modal"]');
    modalOpenBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            openTableHelpModal();
        });
    });
    
    // Table Help Modal Close Buttons
    const modalCloseBtns = getElementsBySelectorSafe('[data-action="close-table-help-modal"]');
    modalCloseBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            closeTableHelpModal();
        });
    });
}

/**
 * =============================================================================
 * MAIN EXECUTION
 * =============================================================================
 */

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeStatsPage();
    initializeInlineEventListeners();
});
