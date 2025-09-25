// Perform Exam JavaScript

// Force initialization if DOM is already loaded
if (document.readyState === 'loading') {
    // DOM still loading, will initialize on DOMContentLoaded
} else {
    // DOM already loaded, initializing immediately
    setTimeout(() => {
        if (!eventListenersInitialized) {
            initializeVariables();
            initializeExam();
            initializeEventListeners();
        }
    }, 100);
}

// Global variables that will be set from PHP template
let TOTAL_QUESTIONS_PLACEHOLDER = 0;
let TIME_REMAINING_PLACEHOLDER = 0;
let EXAM_ID_PLACEHOLDER = 0;
let EXAM_GRADED_SUCCESSFULLY = 'Exam graded successfully';
let EXAM_GRADING_ERROR = 'Error grading exam';
let SAVED_STATUS = {};
let SAVED_MARKEDFORREVIEW = {};
let SAVED_CORRECT = {};

// Language strings object
let LANG_STRINGS = {};

// DOM Cache for performance optimization
const DOM_CACHE = {
    // Navigation elements
    questionNavigation: null,
    reviewNavigation: null,
    reviewQuestionNavigation: null,
    
    // Modal elements
    gradeModal: null,
    instructionsModal: null,
    calculatorModal: null,
    navigatorModal: null,
    
    // Control elements
    calculatorBtn: null,
    calculatorDisplay: null,
    reviewFlag: null,
    reviewText: null,
    
    // Timer and display elements
    timeDisplay: null,
    questionCounter: null,
    practiceExamStats: null,
    
    // Utility function to get cached element or query and cache it
    get: function(id, selector) {
        if (!this[id]) {
            this[id] = selector.startsWith('#') ? 
                document.getElementById(selector.substring(1)) : 
                document.querySelector(selector);
        }
        return this[id];
    },
    
    // Function to clear cache (useful for dynamic elements)
    clear: function(id) {
        if (id) {
            this[id] = null;
        } else {
            // Clear all cache
            Object.keys(this).forEach(key => {
                if (typeof this[key] !== 'function') {
                    this[key] = null;
                }
            });
        }
    }
};

// Function to get question data from template data
function getQuestionData(questionNum) {
    // Get question data from the data attribute
    const questionDataElement = DOM_CACHE.get('questionData', '#question-data');
    
    if (questionDataElement) {
        const dataAttribute = questionDataElement.getAttribute('data-questions');
        
        try {
            const questionsData = JSON.parse(dataAttribute);
            const questionData = questionsData[questionNum] || null;
            return questionData;
        } catch (e) {
            return null;
        }
    }
    return null;
}

// Utility function to get language string with fallback
function getString(key, fallback = '') {
    return LANG_STRINGS[key] || fallback;
}

// Utility function to safely get element by ID with caching
function getElementById(id) {
    return DOM_CACHE.get(id, '#' + id);
}

// Utility function to safely query selector with caching
function querySelector(selector) {
    return DOM_CACHE.get(selector.replace(/[^a-zA-Z0-9]/g, '_'), selector);
}

// Utility function to safely query all selectors
function querySelectorAll(selector) {
    return document.querySelectorAll(selector);
}

// Event delegation handler for all data-action elements
function handleEventDelegation(event) {
    const target = event.target;
    const action = target.getAttribute('data-action');
    
    
    if (!action) return;
    
    switch (action) {
        case 'open-instructions':
            openInstructions();
            break;
        case 'close-instructions':
            closeInstructions();
            break;
        case 'toggle-review-flag':
            toggleReviewFlag();
            break;
        case 'toggle-practice-exam':
            togglePracticeExam();
            break;
        case 'go-to-question':
            const questionNum = parseInt(target.getAttribute('data-question'));
            if (questionNum) goToQuestion(questionNum);
            break;
        case 'open-navigator':
            openNavigator();
            break;
        case 'close-navigator':
            closeNavigator();
            break;
        case 'end-review':
            endReview();
            break;
        case 'review-all':
            reviewAll();
            break;
        case 'review-incomplete':
            reviewIncomplete();
            break;
        case 'review-marked':
            reviewMarked();
            break;
        case 'go-to-review-screen':
            goToReviewScreen();
            break;
        // Calculator events are now handled directly by calculator.js module
        case 'toggle-marked':
            const markedQuestion = parseInt(target.getAttribute('data-question'));
            if (markedQuestion) toggleMarked(markedQuestion);
            break;
        case 'close-grade-confirmation':
            closeGradeConfirmation();
            break;
        case 'confirm-grade':
            confirmGrade();
            break;
    }
}

// Initialize variables from PHP template
function initializeVariables() {
    // Get variables from data attributes or global variables
    const examContainer = querySelector('.exam-container');
    if (examContainer) {
        TOTAL_QUESTIONS_PLACEHOLDER = parseInt(examContainer.dataset.totalQuestions) || 0;
        TIME_REMAINING_PLACEHOLDER = parseInt(examContainer.dataset.timeRemaining) || 0;
        EXAM_ID_PLACEHOLDER = parseInt(examContainer.dataset.examId) || 0;
    }
    
    // Initialize language strings from template
    initializeLanguageStrings();
    
    // Initialize marked for review data from template
    initializeMarkedForReviewData();
    initializeStatusData();
    
    // Get language strings from data attributes
    const gradeModal = DOM_CACHE.get('gradeModal', '#grade-confirmation-modal');
    if (gradeModal) {
        EXAM_GRADED_SUCCESSFULLY = gradeModal.dataset.gradedSuccessfully || 'Exam graded successfully';
        EXAM_GRADING_ERROR = gradeModal.dataset.gradingError || 'Error grading exam';
    }
    
    // SAVED_STATUS is now populated directly by the PHP template
    // Don't override it here
    
    // SAVED_MARKEDFORREVIEW is now populated directly by the PHP template
    // Don't override it here
}

// Initialize language strings from template
function initializeLanguageStrings() {
    const stringsDataElement = DOM_CACHE.get('langStrings', '#lang-strings');
    if (stringsDataElement && stringsDataElement.dataset.lang) {
        try {
            LANG_STRINGS = JSON.parse(stringsDataElement.dataset.lang);
        } catch (error) {
            console.error('Error parsing language strings:', error);
            LANG_STRINGS = {};
        }
    }
}

// Initialize marked for review data from template
function initializeMarkedForReviewData() {
    const markedDataElement = DOM_CACHE.get('markedData', '#marked-data');
    if (markedDataElement && markedDataElement.dataset.marked) {
        try {
            SAVED_MARKEDFORREVIEW = JSON.parse(markedDataElement.dataset.marked);
        } catch (error) {
            console.error('Error parsing marked data:', error);
            SAVED_MARKEDFORREVIEW = {};
        }
    } else {
        SAVED_MARKEDFORREVIEW = {};
    }
}

// Exam state variables
let currentQuestion = 1;
let totalQuestions = 0;
let timeRemaining = 0;
let timerInterval;
let alertShown = false; // Flag to prevent multiple alerts
let reviewMode = false;
let reviewType = ''; // 'all', 'incomplete', 'marked'
const seenQuestions = new Set();


// Global arrays for tracking question states (like perform_study.js)
let status = {};           // 0=unseen, 1=incomplete, 2=answered
let correct = {};          // 0=incorrect, 1=correct, 2=first-time correct  
let markedforreview = {};  // 0=not marked, 1=marked


// Initialize status data from template
function initializeStatusData() {
    const statusDataElement = DOM_CACHE.get('statusData', '#status-data');
    
    if (statusDataElement && statusDataElement.dataset.status) {
        try {
            SAVED_STATUS = JSON.parse(statusDataElement.dataset.status);
            // Load saved correct values from question data first
            SAVED_CORRECT = {};
            const questionDataElement = DOM_CACHE.get('questionData', '#question-data');
            if (questionDataElement && questionDataElement.dataset.questions) {
                try {
                    const questions = JSON.parse(questionDataElement.dataset.questions);
                    
                    if (Array.isArray(questions)) {
                        questions.forEach(question => {
                            if (question.saved_correct !== undefined) {
                                SAVED_CORRECT[question.questionorder] = question.saved_correct;
                            }
                        });
                    } else if (typeof questions === 'object' && questions !== null) {
                        // Handle object format
                        Object.keys(questions).forEach(key => {
                            const question = questions[key];
                            if (question && question.saved_correct !== undefined) {
                                SAVED_CORRECT[question.questionorder] = question.saved_correct;
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error parsing questions data:', error);
                    SAVED_CORRECT = {};
                }
            }
            
            // Initialize dynamic status array with saved status values (convert strings to numbers)
            status = {};
            for (let key in SAVED_STATUS) {
                status[key] = parseInt(SAVED_STATUS[key]) || 0;
            }
            
            // Initialize dynamic correct array with saved correct values (convert strings to numbers)
            correct = {};
            for (let key in SAVED_CORRECT) {
                correct[key] = parseInt(SAVED_CORRECT[key]) || 0;
            }
            
            // Initialize dynamic markedforreview array with saved markedforreview values (convert strings to numbers)
            markedforreview = {};
            for (let key in SAVED_MARKEDFORREVIEW) {
                markedforreview[key] = parseInt(SAVED_MARKEDFORREVIEW[key]) || 0;
            }
            
        } catch (error) {
            console.error('Error parsing status data:', error);
            SAVED_STATUS = {};
            SAVED_CORRECT = {};
            status = {};
            correct = {};
            markedforreview = {};
        }
    } else {
        SAVED_STATUS = {};
        SAVED_CORRECT = {};
        status = {};
        correct = {};
        markedforreview = {};
    }
}



function ensureNavigationStructure() {
    const questionNav = DOM_CACHE.get('questionNavigation', '#question-navigation');
    
    if (questionNav && !questionNav.querySelector('.navigation-left')) {
        // Preserve the original className
        const originalClassName = questionNav.className;
        
        // Recreate the structure if it's missing
        const leftDiv = document.createElement('div');
        leftDiv.className = 'navigation-left';
        leftDiv.innerHTML = '<!-- Empty left section for normal navigation -->';
        
        const rightDiv = document.createElement('div');
        rightDiv.className = 'navigation-right';
        rightDiv.innerHTML = questionNav.innerHTML;
        
        questionNav.innerHTML = '';
        questionNav.className = originalClassName; // Restore the original className
        questionNav.appendChild(leftDiv);
        questionNav.appendChild(rightDiv);
    }
}

function showQuestion(questionNum) {
    // Update currentQuestion to match the parameter
    currentQuestion = questionNum;
    
    // Hide all questions
    querySelectorAll(".question").forEach(q => q.classList.remove("active"));
    
    // Show selected question
    DOM_CACHE.get('question' + questionNum, '#question-' + questionNum).classList.add("active");
    
    // Update question numbers
    querySelectorAll(".question-number").forEach(qn => qn.classList.remove("active"));
    querySelector("[data-question=\"" + questionNum + "\"]").classList.add("active");
    
    // Update navigation buttons
    if (reviewMode) {
        // In review mode, update review navigation buttons
        
        // Previous button logic based on review type
        let prevDisabled = false;
        if (reviewType === 'all') {
            // In review all mode, disable on first question
            prevDisabled = (questionNum == 1);
        } else if (reviewType === 'incomplete') {
            // In review incomplete mode, check if there are previous incomplete questions
            let hasPreviousIncomplete = false;
            for (let i = questionNum - 1; i >= 1; i--) {
                const statusCell = querySelector(`#status-${i}`);
                if (statusCell && statusCell.textContent === "Incomplete") {
                    hasPreviousIncomplete = true;
                    break;
                }
            }
            prevDisabled = !hasPreviousIncomplete;
        } else if (reviewType === 'marked') {
            // In review marked mode, check if there are previous marked questions
            let hasPreviousMarked = false;
            for (let i = questionNum - 1; i >= 1; i--) {
                const checkbox = getElementById(`marked-${i}`);
                if (checkbox && checkbox.checked) {
                    hasPreviousMarked = true;
                    break;
                }
            }
            prevDisabled = !hasPreviousMarked;
        } else {
            // Default behavior
            prevDisabled = (questionNum == 1);
        }
        
        DOM_CACHE.get('reviewPrevBtn', '#review-prev-btn').disabled = prevDisabled;
        
        // Next button logic based on review type
        let nextDisabled = false;
        if (reviewType === 'all') {
            // In review all mode, always enabled (will return to section review on last question)
            nextDisabled = false;
        } else if (reviewType === 'incomplete') {
            // In review incomplete mode, always enabled (will return to section review when no more incomplete)
            nextDisabled = false;
        } else if (reviewType === 'marked') {
            // In review marked mode, always enabled (will return to section review when no more marked)
            nextDisabled = false;
        } else {
            // Default behavior - always enabled in review mode
            nextDisabled = false;
        }
        
        DOM_CACHE.get('reviewNextBtn', '#review-next-btn').disabled = nextDisabled;
    } else {
        // In normal mode, update question navigation buttons
        DOM_CACHE.get('prevBtn', '#prev-btn').disabled = (questionNum == 1);
        DOM_CACHE.get('nextBtn', '#next-btn').disabled = false; // Always enabled to allow going to Section Review
    }
    
    // Update question counter
    DOM_CACHE.get('questionCounter', '#question-counter').textContent = questionNum + "/" + totalQuestions;
    
    // Mark question as seen (Incomplete) if it was previously Unseen AND has no answer
    const statusCell = querySelector("#status-" + questionNum);
    if (statusCell && statusCell.textContent === "Unseen") {
        // Check if question has an answer
        const radioButtons = querySelectorAll(`input[name="answer-${questionNum}"]`);
        let hasAnswer = false;
        
        radioButtons.forEach(radio => {
            if (radio.checked) {
                hasAnswer = true;
            }
        });
        
        if (hasAnswer) {
            // Question has an answer, mark as Complete
            statusCell.className = "status-complete";
            statusCell.textContent = LANG_STRINGS.complete || "Complete";
            status[questionNum] = 2; // Update global status array
        } else {
            // Question has no answer, mark as Incomplete
            statusCell.className = "status-incomplete";
            statusCell.textContent = LANG_STRINGS.incomplete || "Incomplete";
            status[questionNum] = 1; // Update global status array
        }
    }
    
    // Update review flag for current question
    updateReviewFlag();
    
    // Show the review flag section (Marked for review button)
    const reviewSection = querySelector(".review-section");
    if (reviewSection) {
        reviewSection.className = "practice-exam-show";
    }
    
    // Show appropriate navigation based on review mode
    if (reviewMode) {
        // In review mode, show review question navigation (with Previous/Next)
        const questionNav = DOM_CACHE.get('questionNavigation', '#question-navigation');
        const reviewNav = DOM_CACHE.get('reviewNavigation', '#review-navigation');
        const reviewQuestionNav = DOM_CACHE.get('reviewQuestionNavigation', '#review-question-navigation');
        
        if (questionNav) {
            questionNav.className = "navigation practice-exam-hide";
        }
        if (reviewNav) {
            reviewNav.className = "navigation practice-exam-hide";
        }
        if (reviewQuestionNav) {
            reviewQuestionNav.className = "navigation practice-exam-show";
        }
        
        // Hide review-specific buttons when viewing individual questions
        const reviewButtons = querySelectorAll('#review-question-navigation .nav-button');
        reviewButtons.forEach(button => {
            const buttonText = button.textContent.trim();
            if (buttonText === 'End Review' || buttonText === 'Review All' || 
                buttonText === 'Review Incomplete' || buttonText === 'Review Marked') {
                button.className = 'practice-exam-hide';
            }
        });
    } else {
        // In normal mode, show question navigation
        const questionNav = DOM_CACHE.get('questionNavigation', '#question-navigation');
        const reviewNav = DOM_CACHE.get('reviewNavigation', '#review-navigation');
        const reviewQuestionNav = DOM_CACHE.get('reviewQuestionNavigation', '#review-question-navigation');
        
        if (questionNav) {
            questionNav.className = "navigation practice-exam-show";
        }
        if (reviewNav) {
            reviewNav.className = "navigation practice-exam-hide";
        }
        if (reviewQuestionNav) {
            reviewQuestionNav.className = "navigation practice-exam-hide";
        }
    }
    
    // Hide Section Review when showing any question
    const sectionReview = DOM_CACHE.get('sectionReview', '#section-review');
    sectionReview.className = "practice-exam-hide";
}

function showSectionReview() {
    // Set review mode to true
    reviewMode = true;
    
    // Hide all questions
    querySelectorAll(".question").forEach(q => q.classList.remove("active"));
    
    // Hide question numbers
    querySelectorAll(".question-number").forEach(qn => qn.classList.remove("active"));
    
    // Hide the review flag section (Marked for review button)
    const reviewSection = querySelector(".review-section");
    if (reviewSection) {
        reviewSection.className = "practice-exam-hide";
    }
    
    // Show Section Review
    const sectionReview = DOM_CACHE.get('sectionReview', '#section-review');
    sectionReview.className = "section-review practice-exam-show";
    
    // Show the question list expanded by default
    const questionList = DOM_CACHE.get('questionList', '#question-list');
    const toggleIcon = DOM_CACHE.get('toggleIcon', '#toggle-icon');
    questionList.className = "question-list practice-exam-grid";
    toggleIcon.textContent = "-";
    
    // Hide question navigation and show review navigation (without Previous/Next)
    const questionNav = DOM_CACHE.get('questionNavigation', '#question-navigation');
    const reviewNav = DOM_CACHE.get('reviewNavigation', '#review-navigation');
    const reviewQuestionNav = DOM_CACHE.get('reviewQuestionNavigation', '#review-question-navigation');
    
    if (questionNav) {
        questionNav.className = "navigation practice-exam-hide";
    }
    if (reviewNav) {
        reviewNav.className = "navigation practice-exam-show";
    }
    if (reviewQuestionNav) {
        reviewQuestionNav.className = "navigation practice-exam-hide";
    }
    
    // Add direct event listeners to review buttons
    const reviewButtons = reviewNav.querySelectorAll('button[data-action]');
    reviewButtons.forEach((btn) => {
        btn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            const action = this.getAttribute('data-action');
            
            switch (action) {
                case 'end-review':
                    endReview();
                    break;
                case 'review-all':
                    reviewAll();
                    break;
                case 'review-incomplete':
                    reviewIncomplete();
                    break;
                case 'review-marked':
                    reviewMarked();
                    break;
            }
        });
    });
    
    // Add direct event listeners to review question navigation buttons
    const reviewQuestionNavButtons = DOM_CACHE.get('reviewQuestionNavigation', '#review-question-navigation');
    if (reviewQuestionNavButtons) {
        const reviewQuestionButtons = reviewQuestionNavButtons.querySelectorAll('button[data-action]');
        reviewQuestionButtons.forEach((btn) => {
            btn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const action = this.getAttribute('data-action');
                
                switch (action) {
                    case 'go-to-review-screen':
                        goToReviewScreen();
                        break;
                    case 'end-review':
                        endReview();
                        break;
                    case 'review-all':
                        reviewAll();
                        break;
                    case 'review-incomplete':
                        reviewIncomplete();
                        break;
                    case 'review-marked':
                        reviewMarked();
                        break;
                }
            });
        });
    }
    
    // Update question counter to show "Review"
    DOM_CACHE.get('questionCounter', '#question-counter').textContent = LANG_STRINGS.review || "Review";
    
    // Update review flags based on actual question status
    updateReviewFlagsFromStatus();
    
    // Update practice exam statistics
    updatePracticeExamStats();
    
    // Change calculator button to instructions button
    changeButtonToInstructions();
}

function togglePracticeExam() {
    const questionList = DOM_CACHE.get('questionList', '#question-list');
    const toggleIcon = DOM_CACHE.get('toggleIcon', '#toggle-icon');
    
    if (questionList.className === "practice-exam-hide") {
        questionList.className = "practice-exam-grid";
        toggleIcon.textContent = "-";
    } else {
        questionList.className = "practice-exam-hide";
        toggleIcon.textContent = "+";
    }
}

function openInstructions() {
    DOM_CACHE.get('instructionsModal', '#instructions-modal').className = "practice-exam-modal-show";
}

function closeInstructions() {
    DOM_CACHE.get('instructionsModal', '#instructions-modal').className = "practice-exam-modal-hide";
}

function updateReviewFlagsFromStatus() {
    for (let i = 1; i <= totalQuestions; i++) {
        const flagElement = getElementById(`flag-${i}`);
        if (flagElement) {
            // Check if this question is marked for review in the navigator
            const navigatorCheckbox = getElementById(`marked-${i}`);
            if (navigatorCheckbox && navigatorCheckbox.checked) {
                flagElement.textContent = "🏁";
                flagElement.classList.remove("unmarked");
                flagElement.classList.add("marked");
            } else {
                flagElement.textContent = "🏳️";
                flagElement.classList.remove("marked");
                flagElement.classList.add("unmarked");
            }
        }
    }
}

function updatePracticeExamStats() {
    let complete = 0;
    let incomplete = 0;
    let unseen = 0;
    
    // Count based on the status array instead of DOM text
    for (let i = 1; i <= totalQuestions; i++) {
        const questionStatus = status[i] || 0; // Default to 0 (unseen)
        
        if (questionStatus == 2) {
            complete++;
        } else if (questionStatus == 1) {
            incomplete++;
        } else if (questionStatus == 0) {
            unseen++;
        }
    }
    
    
    // Update the stats display
    const statsElement = DOM_CACHE.get('practiceExamStats', '#practice-exam-stats');
    if (statsElement) {
        statsElement.textContent = `Complete: ${complete}, Incomplete: ${incomplete}, Unseen: ${unseen}`;
    }
}

function changeButtonToInstructions() {
    const calculatorBtn = DOM_CACHE.get('calculatorBtn', '#calculator');
    if (calculatorBtn) {
        calculatorBtn.innerHTML = LANG_STRINGS.instructions || 'Instructions';
        calculatorBtn.className = 'calculator-btn';
        calculatorBtn.onclick = function() {
            openInstructions();
        };
        // Remove the old event listener by cloning and replacing the element
        const newBtn = calculatorBtn.cloneNode(true);
        newBtn.innerHTML = LANG_STRINGS.instructions || 'Instructions';
        newBtn.className = 'calculator-btn';
        newBtn.onclick = function() {
            openInstructions();
        };
        calculatorBtn.parentNode.replaceChild(newBtn, calculatorBtn);
    }
}

function changeButtonToCalculator() {
    const calculatorBtn = DOM_CACHE.get('calculatorBtn', '#calculator');
    if (calculatorBtn) {
        calculatorBtn.innerHTML = '<i class="fas fa-calculator"></i>';
        calculatorBtn.className = 'calculator-button';
        calculatorBtn.onclick = function(event) {
            event.preventDefault();
            event.stopPropagation();
            Calculator.open();
        };
    }
}

function updateQuestionStatus(questionNum) {
    const statusCell = querySelector(`#status-${questionNum}`);
    if (statusCell) {
        // Check if any answer is selected for this question
        const radioButtons = querySelectorAll(`input[name="answer-${questionNum}"]`);
        let hasAnswer = false;
        
        radioButtons.forEach(radio => {
            if (radio.checked) {
                hasAnswer = true;
            }
        });
        
        if (hasAnswer) {
            // Question has an answer, mark as Complete
            statusCell.className = "status-complete";
            statusCell.textContent = LANG_STRINGS.complete || "Complete";
            status[questionNum] = 2; // Update global status array
        } else {
            // Question has no answer, mark as Incomplete
            statusCell.className = "status-incomplete";
            statusCell.textContent = LANG_STRINGS.incomplete || "Incomplete";
            status[questionNum] = 1; // Update global status array
        }
    }
}

// Function to update correct array without showing visual feedback (Practice Exam only)
function updateCorrectStatus(selectedRadio) {
    const questionNum = selectedRadio.name.split('-')[1];
    const answerId = selectedRadio.value;
    
    
    // Determine correct first, then update status (NO VISUAL FEEDBACK)
    const questionDataForFeedback = getQuestionData(questionNum);
    
    if (questionDataForFeedback) {
        const selectedAnswerData = questionDataForFeedback.answers.find(answer => answer.id == answerId);
        
        if (selectedAnswerData && selectedAnswerData.fraction > 0) {
            // Answer is correct - FIRST calculate correct based on OLD status
            const currentCorrect = correct[questionNum] || 0;
            const currentStatus = status[questionNum] || 0;
            
            if (currentStatus == 1) {
                // First time answering - first-time correct
                correct[questionNum] = 2;
            } else if (currentStatus == 2) {
                // Already answered before - now correct
                correct[questionNum] = 1;
            }
            // If currentCorrect is already 1 or 2, keep it unchanged
        } else {
            // Answer is incorrect
            correct[questionNum] = 0;
        }
    }
    
    // NOW update status to Complete (2) - AFTER calculating correct based on OLD status
    status[questionNum] = 2;
    
    // NOTE: No visual feedback shown in Practice Exam - only internal tracking
}

// Review navigation functions
function endReview() {
    // Show grade confirmation dialog
    showGradeConfirmation();
}

function showGradeConfirmation() {
    // Show the modal
    const modal = DOM_CACHE.get('gradeModal', '#grade-confirmation-modal');
    modal.className = 'practice-exam-modal-show';
    
    // Add direct event listeners to modal buttons
    const closeBtns = modal.querySelectorAll('[data-action="close-grade-confirmation"]');
    const confirmBtn = modal.querySelector('[data-action="confirm-grade"]');
    
    closeBtns.forEach((closeBtn) => {
        closeBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            closeGradeConfirmation();
        });
    });
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            confirmGrade();
        });
    }
}

function closeGradeConfirmation() {
    DOM_CACHE.get('gradeModal', '#grade-confirmation-modal').className = 'practice-exam-modal-hide';
}

function confirmGrade() {
    // Close the modal
    closeGradeConfirmation();

    // Prepare data for grading
    const answers = {};
    for (let i = 1; i <= totalQuestions; i++) {
        const radioButtons = querySelectorAll(`input[name="answer-${i}"]`);
        let selectedAnswer = null;
        radioButtons.forEach(radio => {
            if (radio.checked) {
                selectedAnswer = radio.value;
            }
        });
        if (selectedAnswer) {
            answers[i] = selectedAnswer;
        }
    }

    // Get current time remaining with correct sign (same logic as Save & Logout)
    let currentTimeRemaining = timeRemaining;
    
    // If timer has expired (alertShown is true), make the value negative
    if (alertShown) {
        currentTimeRemaining = -currentTimeRemaining;
    }
    
    // Show loading state
    const gradeButton = querySelector('#grade-confirmation-modal button[onclick="confirmGrade()"]');
    if (gradeButton) {
        gradeButton.textContent = LANG_STRINGS.grading || 'Grading...';
        gradeButton.disabled = true;
    }
    
    // Get studyunit string from the hidden form field
    const studyunitField = DOM_CACHE.get('studyunitInput', '#studyunit-input');
    const studyunitValue = studyunitField ? studyunitField.value : '';
    
    // Send grading request
    fetch('grade_exam.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'id': querySelector('input[name="id"]').value,
            'examid': querySelector('input[name="examid"]').value,
            'timeremaining': currentTimeRemaining,
            'answers': JSON.stringify(answers),
            'status': JSON.stringify(status),
            'markedforreview': JSON.stringify(markedforreview),
            'correct': JSON.stringify(correct),
            'studyunit': studyunitValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to stats.php without showing alert
            window.location.href = 'stats.php?id=' + querySelector('input[name="id"]').value + '&examid=' + querySelector('input[name="examid"]').value;
        } else {
            // Show error message
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(EXAM_GRADING_ERROR);
    })
    .finally(() => {
        // Reset button state
        if (gradeButton) {
            gradeButton.textContent = LANG_STRINGS.grade || 'Grade';
            gradeButton.disabled = false;
        }
    });
}

function reviewAll() {
    // Show all questions in sequence starting from question 1
    reviewType = 'all';
    currentQuestion = 1; // Explicitly set to 1
    showQuestion(1);
}

function reviewIncomplete() {
    // Find the first incomplete question and go to it
    reviewType = 'incomplete';
    
    for (let i = 1; i <= totalQuestions; i++) {
        const statusCell = querySelector(`#status-${i}`);
        
        if (statusCell && statusCell.textContent === "Incomplete") {
            showQuestion(i);
            return;
        }
    }
    
    // If no incomplete questions, show alert and stay in section review
    alert(LANG_STRINGS.no_incomplete_questions || "There are no incomplete questions to review.");
    // Stay in section review mode - no need to change reviewMode or showQuestion
}

function reviewMarked() {
    // Find the first marked question and go to it
    reviewType = 'marked';
    for (let i = 1; i <= totalQuestions; i++) {
        const checkbox = getElementById(`marked-${i}`);
        if (checkbox && checkbox.checked) {
            showQuestion(i);
            return;
        }
    }
    // If no marked questions, show alert and stay in section review
    alert(LANG_STRINGS.no_marked_questions || "No questions have been marked for review. Please make your selections and try again.");
    // Stay in section review mode - no need to change reviewMode or showQuestion
}

function goToReviewScreen() {
    // Go directly to the section review screen
    showSectionReview();
}

function nextQuestion() {
    if (reviewMode) {
        nextQuestionInReview();
    } else {
        if (currentQuestion < totalQuestions) {
            currentQuestion++;
            showQuestion(currentQuestion);
        } else if (currentQuestion === totalQuestions) {
            // Show Section Review after clicking Next on the last question
            showSectionReview();
        }
    }
}

function nextQuestionInReview() {
    switch (reviewType) {
        case 'all':
            // Go to next question in sequence
            if (currentQuestion < totalQuestions) {
                currentQuestion++;
                showQuestion(currentQuestion);
            } else {
                // At last question, return to section review
                showSectionReview();
            }
            break;
            
        case 'incomplete':
            // Find next incomplete question
            for (let i = currentQuestion + 1; i <= totalQuestions; i++) {
                const statusCell = querySelector(`#status-${i}`);
                if (statusCell && statusCell.textContent === "Incomplete") {
                    currentQuestion = i;
                    showQuestion(currentQuestion);
                    return;
                }
            }
            // No more incomplete questions, return to section review
            showSectionReview();
            break;
            
        case 'marked':
            // Find next marked question
            for (let i = currentQuestion + 1; i <= totalQuestions; i++) {
                const checkbox = getElementById(`marked-${i}`);
                if (checkbox && checkbox.checked) {
                    currentQuestion = i;
                    showQuestion(currentQuestion);
                    return;
                }
            }
            // No more marked questions, return to section review
            showSectionReview();
            break;
            
        default:
            // Default behavior - go to next question
            if (currentQuestion < totalQuestions) {
                currentQuestion++;
                showQuestion(currentQuestion);
            } else {
                // At last question, return to section review
                showSectionReview();
            }
    }
}

function previousQuestionInReview() {
    switch (reviewType) {
        case 'all':
            // Go to previous question in sequence
            if (currentQuestion > 1) {
                currentQuestion--;
                showQuestion(currentQuestion);
            }
            break;
            
        case 'incomplete':
            // Find previous incomplete question
            for (let i = currentQuestion - 1; i >= 1; i--) {
                const statusCell = querySelector(`#status-${i}`);
                if (statusCell && statusCell.textContent === "Incomplete") {
                    currentQuestion = i;
                    showQuestion(currentQuestion);
                    return;
                }
            }
            // No previous incomplete questions, stay on current question
            break;
            
        case 'marked':
            // Find previous marked question
            for (let i = currentQuestion - 1; i >= 1; i--) {
                const checkbox = getElementById(`marked-${i}`);
                if (checkbox && checkbox.checked) {
                    currentQuestion = i;
                    showQuestion(currentQuestion);
                    return;
                }
            }
            // No previous marked questions, stay on current question
            break;
            
        default:
            // Default behavior - go to previous question
            if (currentQuestion > 1) {
                currentQuestion--;
                showQuestion(currentQuestion);
            }
    }
}

function previousQuestion() {
    if (reviewMode) {
        previousQuestionInReview();
    } else {
        // Check if we're currently in Section Review
        const sectionReview = DOM_CACHE.get('sectionReview', '#section-review');
        if (sectionReview.className === "practice-exam-show") {
            // Go back to the last question from Section Review
            showQuestion(totalQuestions);
            return;
        }
        
        if (currentQuestion > 1) {
            currentQuestion--;
            showQuestion(currentQuestion);
        }
    }
}

function updateTimer() {
    // Show alert when time reaches exactly 00:00 (before decrementing)
    if (timeRemaining === 0 && !alertShown) {
        // Pause the timer
        clearInterval(timerInterval);
        
        // Show alert and wait for user response
        alert(LANG_STRINGS.time_expired || "Time for this Practice Exam has expired. We have suggested a budgeted time of 1 minute per question. Time will now count up so you can evaluate your time management.");
        
        // Set flag to prevent multiple alerts
        alertShown = true;
        
        // Resume timer after user clicks OK - start counting forward
        getElementById("time-display").className = "practice-exam-time-expired";
        timeRemaining = 0; // Start from 0, will become 1 on next increment
        timerInterval = setInterval(updateTimer, 1000);
        return; // Exit early to avoid the decrement below
    }
    
    if (alertShown) {
        // After alert: increment (count up the extra time)
        timeRemaining++;
    } else {
        // Before alert: decrement (count down)
        timeRemaining--;
    }
    
    // Display time (always positive format, red color indicates expired)
    const displayMinutes = Math.abs(Math.floor(timeRemaining / 60));
    const displaySeconds = Math.abs(timeRemaining % 60);
    const timeString = displayMinutes + ":" + (displaySeconds < 10 ? "0" : "") + displaySeconds;
    getElementById("time-display").textContent = timeString;
}

function finishExam() {
    if (confirm(LANG_STRINGS.confirm_finish || "Are you sure you want to finish the exam?")) {
        // Here you would submit the answers
        alert(LANG_STRINGS.exam_submitted || "Exam submitted!");
        window.location.href = "view.php?id=" + EXAM_ID_PLACEHOLDER;
    }
}

function prepareSaveAndLogout(event) {
    if (!confirm(LANG_STRINGS.confirm_save_logout || "Are you sure you want to save your progress and logout? Your answers and remaining time will be saved.")) {
        event.preventDefault();
        return false;
    }
    
    // Collect all answers, status, marked for review, and correct status
    const answers = {};
    const status = {};
    const markedforreview = {};
    const correctData = {};
    
    for (let i = 1; i <= totalQuestions; i++) {
        const selectedAnswer = querySelector(`input[name="answer-${i}"]:checked`);
        if (selectedAnswer) {
            answers[i] = selectedAnswer.value;
            status[i] = 2; // Complete
        } else {
            // Check if question has been seen (has status cell in navigator)
            const statusCell = querySelector(`#status-${i}`);
            if (statusCell && statusCell.textContent !== "Unseen") {
                status[i] = 1; // Incomplete
            } else {
                status[i] = 0; // Unseen
            }
        }
        
        // Check if question is marked for review
        const checkbox = querySelector(`#marked-${i}`);
        if (checkbox && checkbox.checked) {
            markedforreview[i] = 1; // Marked for review
        } else {
            markedforreview[i] = 0; // Not marked for review
        }
        
        // Use the correct value from the global array
        correctData[i] = correct[i] || 0;
    }
    
    // Save the actual timeRemaining value (with correct sign)
    let actualSeconds = timeRemaining;
    
    // If timer has expired (alertShown is true), make the value negative
    if (alertShown) {
        actualSeconds = -actualSeconds;
    }
    

    
    // Update hidden form fields with current values
    const timeremainingInput = DOM_CACHE.get('timeremainingInput', '#timeremaining-input');
    const answersInput = DOM_CACHE.get('answersInput', '#answers-input');
    const statusInput = DOM_CACHE.get('statusInput', '#status-input');
    const markedforreviewInput = DOM_CACHE.get('markedforreviewInput', '#markedforreview-input');
    const correctInput = DOM_CACHE.get('correctInput', '#correct-input');
    
    if (timeremainingInput) {
        timeremainingInput.value = actualSeconds;
    }
    
    if (answersInput) {
        answersInput.value = JSON.stringify(answers);
    }
    
    if (statusInput) {
        statusInput.value = JSON.stringify(status);
    }
    
    if (markedforreviewInput) {
        markedforreviewInput.value = JSON.stringify(markedforreview);
    }
    
    if (correctInput) {
        correctInput.value = JSON.stringify(correctData);
    }
    
    // Submit form
    const form = timeremainingInput.form;
    form.submit();
    
    // Prevent default form submission
    event.preventDefault();
}

// Flag to prevent duplicate event listeners
let eventListenersInitialized = false;

// Function to initialize event listeners (can be called multiple times)
function initializeEventListeners() {
    if (eventListenersInitialized) {
        return;
    }
    
    // Event delegation for all data-action elements
    document.addEventListener('click', handleEventDelegation);
    
    // Question number click handlers
    querySelectorAll(".question-number").forEach(qn => {
        qn.addEventListener("click", handleQuestionClick);
    });

    // Answer selection handlers
    const radioButtons = querySelectorAll("input[type=\"radio\"]");
    radioButtons.forEach((radio, index) => {
        radio.addEventListener("change", handleRadioChange);
    });

    // Save & Logout button handler
    const saveLogoutBtn = getElementById('save-logout-btn');
    if (saveLogoutBtn) {
        saveLogoutBtn.addEventListener('click', handleSaveLogout);
    }

    // Review buttons
    const reviewButtons = querySelectorAll('.review-btn');
    reviewButtons.forEach(btn => {
        btn.addEventListener('click', handleReviewClick);
    });

    // Navigation buttons
    const navButtons = querySelectorAll('.nav-button');
    navButtons.forEach(btn => {
        btn.addEventListener('click', handleNavClick);
    });
    
    // Calculator button
    const calculatorBtn = getElementById('calculator');
    if (calculatorBtn) {
        calculatorBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            Calculator.open();
        });
    }
    
    // Close calculator modal when clicking outside
    const calculatorModal = getElementById("calculator-modal");
    if (calculatorModal) {
        calculatorModal.addEventListener('click', function(event) {
            if (event.target === calculatorModal) {
                closeCalculator();
            }
        });
    }
    
    eventListenersInitialized = true;
    
    // Add direct event listener for Navigator button (backup for event delegation)
    const navigatorBtn = querySelector('[data-action="open-navigator"]');
    if (navigatorBtn) {
        navigatorBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            openNavigator();
        });
    }
}

// Event handler functions
function handleQuestionClick() {
    const questionNum = parseInt(this.getAttribute("data-question"));
    currentQuestion = questionNum;
    showQuestion(questionNum);
}

function handleRadioChange() {
    
    if (this.name.startsWith('answer-')) {
        updateCorrectStatus(this);
        // Only update question status when there's an actual answer change
        updateQuestionStatus(currentQuestion);
    } else {
    }
}

function handleSaveLogout(event) {
    // Save and logout logic
    prepareSaveAndLogout(event);
}

function handleReviewClick() {
    const reviewType = this.getAttribute('data-review-type');
    if (reviewType === 'all') {
        reviewAll();
    } else if (reviewType === 'incomplete') {
        reviewIncomplete();
    } else if (reviewType === 'marked') {
        reviewMarked();
    }
}

function handleNavClick(event) {
    // Prevent default behavior and stop propagation
    event.preventDefault();
    event.stopPropagation();
    
    // Prevent multiple rapid clicks
    if (this.disabled) {
        return;
    }
    
    // Temporarily disable button to prevent rapid clicks
    this.disabled = true;
    setTimeout(() => {
        this.disabled = false;
    }, 100);
    
    if (this.id === 'prev-btn') {
        previousQuestion();
    } else if (this.id === 'next-btn') {
        nextQuestion();
    } else if (this.id === 'review-prev-btn') {
        previousQuestionInReview();
    } else if (this.id === 'review-next-btn') {
        nextQuestionInReview();
    }
}

// Function to initialize exam (can be called multiple times)
function initializeExam() {
    // Set the actual values after initialization
    totalQuestions = TOTAL_QUESTIONS_PLACEHOLDER;
    timeRemaining = TIME_REMAINING_PLACEHOLDER;
    
    // Initialize global status arrays
    initializeStatusData();
    
    // If timeRemaining is negative, it means the timer has already expired
    if (timeRemaining < 0) {
        alertShown = true;
        getElementById("time-display").className = "practice-exam-time-expired";
        // Convert negative value to positive for display (timer will count forward)
        timeRemaining = Math.abs(timeRemaining);
    }

    // Function to update correct status for already selected answers on page load
    function updateExistingCorrectStatus() {
        // Find all checked radio buttons
        const checkedRadios = querySelectorAll("input[type=\"radio\"]:checked");
        
        checkedRadios.forEach((radio, index) => {
            // Only process answer radio buttons (not other radio buttons on the page)
            if (radio.name.startsWith('answer-')) {
                updateCorrectStatus(radio);
            }
        });
    }

    // Update correct status for already selected answers on page load
    updateExistingCorrectStatus();

    // Initialize hidden form fields
    const timeremainingInput = getElementById('timeremaining-input');
    if (timeremainingInput) {
        timeremainingInput.value = timeRemaining;
    }
    
    
    // Initialize question status and marked for review from saved data
    initializeQuestionStatus();
    initializeMarkedForReview();
    
    // Calculator event listeners are now handled by calculator.js module
    
    // Update review flag for current question (first question)
    updateReviewFlag();

    // Start timer
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    timerInterval = setInterval(updateTimer, 1000);

    // Keyboard navigation
    document.removeEventListener("keydown", handleKeyboardNavigation);
    document.addEventListener("keydown", handleKeyboardNavigation);
}

function handleKeyboardNavigation(e) {
    if (e.key === "ArrowLeft" && currentQuestion > 1) {
        previousQuestion();
    } else if (e.key === "ArrowRight" && currentQuestion < totalQuestions) {
        nextQuestion();
    }
}

// Question number click handlers
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if not already done by dynamic loading
    if (!eventListenersInitialized) {
        initializeVariables();
        initializeExam();
        initializeEventListeners();
    }
});

// Calculator functions moved to calculator.js module

// Add calculator event listeners
// Calculator event listeners are now handled by calculator.js module

// Close modals when clicking outside
window.onclick = function(event) {
    const navigatorModal = getElementById("navigator-modal");
    const instructionsModal = getElementById("instructions-modal");
    
    if (event.target === navigatorModal) {
        closeNavigator();
    }
    if (event.target === instructionsModal) {
        closeInstructions();
    }
}

// Navigator functions
function openNavigator() {
    const modal = getElementById("navigator-modal");
    
    if (!modal) {
        console.error('Navigator modal not found!');
        return;
    }
    
    modal.className = "practice-exam-modal-show";
    updateNavigatorStatus();
}

function closeNavigator() {
    getElementById("navigator-modal").className = "practice-exam-modal-hide";
}

function goToQuestion(questionNum) {
    if (questionNum >= 1 && questionNum <= totalQuestions) {
        currentQuestion = questionNum;
        showQuestion(questionNum);
        
        // Close navigator modal when a question is selected
        closeNavigator();
    }
}

function updateNavigatorStatus() {
    // Mark current question and all previously viewed questions as seen
    seenQuestions.add(currentQuestion);
    
    // Check all questions that have been viewed (have status other than "Unseen")
    querySelectorAll(".navigator-table tbody tr").forEach(row => {
        const statusCell = row.querySelector("td:nth-child(2)");
        if (statusCell && statusCell.textContent !== "Unseen") {
            const questionNum = statusCell.id.split("-")[1];
            seenQuestions.add(parseInt(questionNum));
        }
    });
    
    // Reset all statuses to Unseen first
    querySelectorAll(".navigator-table tbody tr").forEach(row => {
        const statusCell = row.querySelector("td:nth-child(2)");
        if (statusCell) {
            statusCell.className = "status-unseen";
            statusCell.textContent = LANG_STRINGS.unseen || "Unseen";
        }
    });
    
    // Mark seen questions as Incomplete
    seenQuestions.forEach(questionNum => {
        const statusCell = querySelector("#status-" + questionNum);
        if (statusCell) {
            statusCell.className = "status-incomplete";
            statusCell.textContent = LANG_STRINGS.incomplete || "Incomplete";
        }
    });
    
    // Update answered questions
    querySelectorAll("input[type=\"radio\"]:checked").forEach(radio => {
        const questionNum = radio.name.split("-")[1];
        const statusCell = querySelector("#status-" + questionNum);
        if (statusCell) {
            statusCell.className = "status-complete";
            statusCell.textContent = LANG_STRINGS.complete || "Complete";
        }
    });
}

function toggleMarked(questionNum) {
    const checkbox = getElementById("marked-" + questionNum);
    const row = checkbox.closest("tr");
    
    if (checkbox.checked) {
        row.classList.add("marked-for-review");
        markedforreview[questionNum] = 1; // Update global array
    } else {
        row.classList.remove("marked-for-review");
        markedforreview[questionNum] = 0; // Update global array
    }
    
    // If this is the current question, update the review flag visual immediately
    if (questionNum == currentQuestion) {
        updateReviewFlag();
    }
}


// Review flag functions
function toggleReviewFlag() {
    const flag = getElementById("review-flag");
    const text = getElementById("review-text");
    const checkbox = getElementById("marked-" + currentQuestion);
    
    if (flag.classList.contains("unmarked")) {
        // Mark for review
        flag.classList.remove("unmarked");
        flag.classList.add("marked");
        flag.textContent = "🏁";
        text.classList.add("marked");
        if (checkbox) {
            checkbox.checked = true;
            checkbox.closest("tr").classList.add("marked-for-review");
        }
        markedforreview[currentQuestion] = 1; // Update global array
    } else {
        // Unmark from review
        flag.classList.remove("marked");
        flag.classList.add("unmarked");
        flag.textContent = "🏳️";
        text.classList.remove("marked");
        if (checkbox) {
            checkbox.checked = false;
            checkbox.closest("tr").classList.remove("marked-for-review");
        }
        markedforreview[currentQuestion] = 0; // Update global array
    }
}

function updateReviewFlag() {
    const flag = getElementById("review-flag");
    const text = getElementById("review-text");
    const checkbox = getElementById("marked-" + currentQuestion);
    
    if (checkbox && checkbox.checked) {
        flag.classList.remove("unmarked");
        flag.classList.add("marked");
        flag.textContent = "🏁";
        text.classList.add("marked");
    } else {
        flag.classList.remove("marked");
        flag.classList.add("unmarked");
        flag.textContent = "🏳️";
        text.classList.remove("marked");
    }
}

function initializeQuestionStatus() {
    
    // Load status data from hidden div (same as initializeStatusData)
    const statusDataElement = getElementById('status-data');
    if (statusDataElement) {
        const dataAttribute = statusDataElement.getAttribute('data-status');
        try {
            const statusData = JSON.parse(dataAttribute);
            
            for (let questionNum in statusData) {
                const questionStatus = statusData[questionNum];
                const statusCell = querySelector(`#status-${questionNum}`);
                
                if (statusCell) {
                    if (questionStatus === 0) {
                        statusCell.className = "status-unseen";
                        statusCell.textContent = LANG_STRINGS.unseen || "Unseen";
                    } else if (questionStatus === 1) {
                        statusCell.className = "status-incomplete";
                        statusCell.textContent = LANG_STRINGS.incomplete || "Incomplete";
                        seenQuestions.add(parseInt(questionNum));
                    } else if (questionStatus === 2) {
                        statusCell.className = "status-complete";
                        statusCell.textContent = LANG_STRINGS.complete || "Complete";
                        seenQuestions.add(parseInt(questionNum));
                    }
                }
            }
        } catch (e) {
        }
    } else {
    }
}

function initializeMarkedForReview() {
    // Use the markedforreview array that was loaded in initializeStatusData()
    for (let questionNum in markedforreview) {
        const marked = markedforreview[questionNum];
        const checkbox = querySelector(`#marked-${questionNum}`);
        
        if (checkbox) {
            if (marked === 1) {
                checkbox.checked = true;
                checkbox.closest("tr").classList.add("marked-for-review");
            } else {
                checkbox.checked = false;
                checkbox.closest("tr").classList.remove("marked-for-review");
            }
        }
    }
}

// Calculator button event listener moved to initializeEventListeners()
