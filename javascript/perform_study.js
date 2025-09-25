// Perform Study Session JavaScript

// Global variables that will be set from PHP template
let TOTAL_QUESTIONS_PLACEHOLDER = 0;
let TIME_REMAINING_PLACEHOLDER = 0;
let EXAM_ID_PLACEHOLDER = 0;
let EXAM_GRADED_SUCCESSFULLY = 'Study session graded successfully';
let EXAM_GRADING_ERROR = 'Error grading study session';
let SAVED_STATUS = {};
let SAVED_MARKEDFORREVIEW = {};

// Language strings loaded from PHP
let LANG_STRINGS = {};

// DOM Cache for performance optimization
const DOM_CACHE = {
    // Navigation elements
    questionNavigation: null,
    navigationRight: null,
    
    // Modal elements
    gradeModal: null,
    instructionsModal: null,
    calculatorModal: null,
    deleteModal: null,
    
    // Control elements
    filterMenu: null,
    searchInput: null,
    calculatorBtn: null,
    calculatorDisplay: null,
    
    // Timer and display elements
    timeDisplay: null,
    pauseIcon: null,
    pauseBtn: null,
    
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
    const questionDataElement = document.getElementById('question-data');
    
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

// Initialize variables from PHP template
function initializeVariables() {
    // Get variables from data attributes or global variables
    const examContainer = document.querySelector('.exam-container');
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
    const gradeModal = document.getElementById('grade-confirmation-modal');
    if (gradeModal) {
        EXAM_GRADED_SUCCESSFULLY = gradeModal.dataset.gradedSuccessfully || 'Study session graded successfully';
        EXAM_GRADING_ERROR = gradeModal.dataset.gradingError || 'Error grading study session';
    }
    
    // SAVED_STATUS is now populated directly by the PHP template
    // Don't override it here
    
    // SAVED_MARKEDFORREVIEW is now populated directly by the PHP template
    // Don't override it here
}

// Initialize language strings from template
function initializeLanguageStrings() {
    const stringsDataElement = document.getElementById('js-strings-data');
    if (stringsDataElement && stringsDataElement.dataset.strings) {
        try {
            LANG_STRINGS = JSON.parse(stringsDataElement.dataset.strings);
        } catch (error) {
            console.error('Error parsing language strings:', error);
            LANG_STRINGS = {};
        }
    }
}

// Utility function to get language string with fallback
function getString(key, fallback = '') {
    return LANG_STRINGS[key] || fallback;
}

// Initialize marked for review data from template
function initializeMarkedForReviewData() {
    const markedDataElement = document.getElementById('marked-data');
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

// Initialize status data from template
function initializeStatusData() {
    const statusDataElement = document.getElementById('status-data');
    
    if (statusDataElement && statusDataElement.dataset.status) {
        try {
            SAVED_STATUS = JSON.parse(statusDataElement.dataset.status);
            // Load saved correct values from question data first
            SAVED_CORRECT = {};
            const questionDataElement = document.getElementById('question-data');
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
            status = {};
            SAVED_CORRECT = {};
            correct = {};
            markedforreview = {};
        }
    } else {
        SAVED_STATUS = {};
        status = {};
        SAVED_CORRECT = {};
        correct = {};
        markedforreview = {};
    }
}

// Study session state variables
let currentQuestion = 1;
let totalQuestions = 0;
let timeRemaining = 0;
let timerInterval;
let alertShown = false; // Flag to prevent multiple alerts
let isPaused = false; // Flag for pause functionality
const seenQuestions = new Set();
let status = {}; // Dynamic status array that gets updated as user answers questions
let correct = {}; // Dynamic correct array that gets updated as user answers questions
let markedforreview = {}; // Dynamic markedforreview array that gets updated as user marks questions
let SAVED_CORRECT = {}; // Saved correct values from database



function ensureNavigationStructure() {
    const questionNav = DOM_CACHE.get('questionNavigation', '#question-navigation');
    
    if (questionNav && !questionNav.querySelector('.navigation-left')) {
        // Recreate the structure if it's missing
        const leftDiv = document.createElement('div');
        leftDiv.className = 'navigation-left';
        leftDiv.innerHTML = '<!-- Empty left section for normal navigation -->';
        
        const rightDiv = document.createElement('div');
        rightDiv.className = 'navigation-right';
        rightDiv.innerHTML = questionNav.innerHTML;
        
        questionNav.innerHTML = '';
        questionNav.appendChild(leftDiv);
        questionNav.appendChild(rightDiv);
    }
}

function showQuestion(questionNum) {
    // Update currentQuestion to match the parameter
    currentQuestion = questionNum;
    
    // Hide all questions
    document.querySelectorAll(".question").forEach(q => {
        q.classList.remove("active");
        q.classList.add('js-element-hidden');
    });
    
    // Show selected question
    const questionElement = document.getElementById("question-" + questionNum);
    questionElement.classList.add("active");
    questionElement.classList.remove('js-element-hidden');
    questionElement.classList.add('js-element-visible');
    
    // Update question numbers
    document.querySelectorAll(".question-number").forEach(qn => qn.classList.remove("active"));
    document.querySelector("[data-question=\"" + questionNum + "\"]").classList.add("active");
    
    // Update navigation buttons
    const prevQ = getPreviousFilteredQuestion(questionNum);
    const nextQ = getNextFilteredQuestion(questionNum);
    
    document.getElementById("prev-btn").disabled = (prevQ === null || questionNum === 1);
    document.getElementById("next-btn").disabled = (nextQ === null || questionNum === totalQuestions);
    
    // Question counter removed
    
    // Mark question as seen (Incomplete) if it was previously Unseen
    const statusCell = document.querySelector("#status-" + questionNum);
    if (statusCell && statusCell.textContent === "Unseen") {
        statusCell.className = "status-incomplete";
        statusCell.textContent = getString('status_incomplete', 'Incomplete');
    }
    
    // Update review flag for current question
    updateReviewFlag();
    
    // Show the review flag section (Marked for review button)
    const reviewSection = document.querySelector(".review-section");
    if (reviewSection) {
        reviewSection.classList.remove('js-element-hidden');
        reviewSection.classList.add('js-element-visible');
    }
    
    // Show question navigation
    const questionNavigation = DOM_CACHE.get('questionNavigation', '#question-navigation');
    if (questionNavigation) {
        questionNavigation.classList.remove('js-element-hidden');
        questionNavigation.classList.add('js-element-visible');
    }
    
    // Ensure the navigation structure is maintained
    ensureNavigationStructure();
    
    // Sync sidebar height with the active question content
    syncSidebarHeight();
    
}

// Function to synchronize sidebar height with active question content
function syncSidebarHeight() {
    const activeQuestion = document.querySelector('.question.active');
    const sidebar = document.querySelector('.question-numbers-sidebar');
    
    if (!activeQuestion || !sidebar) {
        return;
    }
    
    // Use setTimeout to ensure DOM has updated after feedback animation
    setTimeout(() => {
        // First, reset sidebar to auto height to get accurate question measurement
        sidebar.style.height = 'auto';
        
        // Force a reflow to ensure the auto height is applied
        sidebar.offsetHeight;
        
        // Use the actual scrollHeight but limit it to reasonable bounds
        let questionHeight = activeQuestion.scrollHeight;
        
        // Get visible feedback to adjust calculation
        const visibleFeedback = activeQuestion.querySelector('.answer-feedback.show, .answer-feedback.js-element-visible');
        
        // If there's visible feedback, use scrollHeight directly with small margin
        // If no feedback, use a more conservative calculation
        if (visibleFeedback) {
            // Add minimal margin for questions with feedback to ensure perfect alignment
            questionHeight = questionHeight + 1; // Add 1px margin for perfect alignment
        } else {
            // For questions without feedback, use a more conservative approach
            const questionTitle = activeQuestion.querySelector('h3');
            const questionText = activeQuestion.querySelector('.question-text');
            const answerOptions = activeQuestion.querySelector('.answer-options');
            
            let calculatedHeight = 40; // Base padding
            if (questionTitle) calculatedHeight += questionTitle.offsetHeight + 15; // title + margin
            if (questionText) calculatedHeight += questionText.offsetHeight + 10; // text + margin  
            if (answerOptions) calculatedHeight += answerOptions.offsetHeight + 10; // options + margin
            
            // Use the smaller of scrollHeight or calculated height for questions without feedback
            questionHeight = Math.min(questionHeight, calculatedHeight);
        }
        
        const minHeight = 500; // Minimum height from CSS
        const finalHeight = Math.max(questionHeight, minHeight);
        
                // Apply transition and set the calculated height
                sidebar.style.transition = 'height 0.3s ease';
                sidebar.style.height = finalHeight + 'px';
    }, 100); // Increased delay to ensure feedback is fully rendered
}

function openInstructions() {
    const modal = DOM_CACHE.get('instructionsModal', '#instructions-modal');
    if (modal) {
        modal.classList.remove('js-element-hidden');
        modal.classList.add('js-element-visible');
    }
}

function closeInstructions() {
    const modal = DOM_CACHE.get('instructionsModal', '#instructions-modal');
    if (modal) {
        modal.classList.remove('js-element-visible');
        modal.classList.add('js-element-hidden');
    }
}





function updateQuestionStatus(questionNum) {
    const statusCell = document.querySelector(`#status-${questionNum}`);
    if (statusCell) {
        // Check if any answer is selected for this question
        const radioButtons = document.querySelectorAll(`input[name="answer-${questionNum}"]`);
        let hasAnswer = false;
        
        radioButtons.forEach(radio => {
            if (radio.checked) {
                hasAnswer = true;
            }
        });
        
        if (hasAnswer) {
            // Question has an answer, mark as Complete
            statusCell.className = "status-complete";
            statusCell.textContent = getString('status_complete', 'Complete');
        } else {
            // Question has no answer, mark as Incomplete
            statusCell.className = "status-incomplete";
            statusCell.textContent = getString('status_incomplete', 'Incomplete');
        }
    }
}


function showGradeConfirmation() {
    // Show the modal
    const modal = DOM_CACHE.get('gradeModal', '#grade-confirmation-modal');
    if (modal) {
        modal.classList.remove('js-element-hidden');
        modal.classList.add('js-element-visible');
    }
}

function closeGradeConfirmation() {
    const modal = DOM_CACHE.get('gradeModal', '#grade-confirmation-modal');
    if (modal) {
        modal.classList.remove('js-element-visible');
        modal.classList.add('js-element-hidden');
    }
}

function confirmGrade() {
    // Close the modal
    closeGradeConfirmation();

    // Prepare data for grading - collect all current answers
    const answers = {};
    for (let i = 1; i <= totalQuestions; i++) {
        const radioButtons = document.querySelectorAll(`input[name="answer-${i}"]`);
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

    // For study sessions, use elapsed time (always positive)
    let currentTimeRemaining = timeRemaining;
    
    // Show loading state
    const gradeButton = document.querySelector('#grade-confirmation-modal button[onclick="confirmGrade()"]');
    if (gradeButton) {
        gradeButton.textContent = getString('button_grading', 'Grading...');
        gradeButton.disabled = true;
    }
    
    // Get studyunit string from the hidden form field
    const studyunitField = document.getElementById('studyunit-input');
    const studyunitValue = studyunitField ? studyunitField.value : '';
    
    // Send grading request
    fetch('grade_exam.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'id': document.querySelector('input[name="id"]').value,
            'examid': document.querySelector('input[name="examid"]').value,
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
            window.location.href = 'stats.php?id=' + document.querySelector('input[name="id"]').value + '&examid=' + document.querySelector('input[name="examid"]').value;
        } else {
            // Show error message
            alert(getString('error_prefix', 'Error: ') + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(EXAM_GRADING_ERROR);
    })
    .finally(() => {
        // Reset button state
        if (gradeButton) {
            gradeButton.textContent = getString('js:button:grade', 'Grade');
            gradeButton.disabled = false;
        }
    });
}


function nextQuestion() {
    const nextQ = getNextFilteredQuestion(currentQuestion);
    if (nextQ !== null) {
        currentQuestion = nextQ;
        showQuestion(currentQuestion);
    } else {
        // No more filtered questions, do nothing (button should be disabled)
        return;
    }
}


function previousQuestion() {
    const prevQ = getPreviousFilteredQuestion(currentQuestion);
    if (prevQ !== null) {
        currentQuestion = prevQ;
        showQuestion(currentQuestion);
    }
}

function updateTimer() {
    // Only update if not paused
    if (!isPaused) {
        timeRemaining++;
    }
    
    // Display time in MM:SS format
    const displayMinutes = Math.floor(timeRemaining / 60);
    const displaySeconds = timeRemaining % 60;
    const timeString = (displayMinutes < 10 ? "0" : "") + displayMinutes + ":" + (displaySeconds < 10 ? "0" : "") + displaySeconds;
    
    const timeDisplay = DOM_CACHE.get('timeDisplay', '#time-display');
    if (timeDisplay) {
        timeDisplay.textContent = timeString;
    } else {
        console.error("time-display element not found");
    }
    
    // Timer update complete
}

// New functions for study session controls
function togglePause() {
    isPaused = !isPaused;
    const pauseIcon = DOM_CACHE.get('pauseIcon', '#pause-icon');
    
    if (isPaused) {
        // Show pause overlay
        showPauseOverlay();
        pauseIcon.className = 'fas fa-play';
        pauseIcon.parentElement.title = getString('button_resume', 'Resume');
    } else {
        // Hide pause overlay
        hidePauseOverlay();
        pauseIcon.className = 'fas fa-pause';
        pauseIcon.parentElement.title = getString('button_pause', 'Pause');
    }
}

function showPauseOverlay() {
    // Create pause overlay if it doesn't exist
    let overlay = document.getElementById('pause-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'pause-overlay';
        overlay.innerHTML = `
            <div class="pause-overlay-content">
                <div class="pause-header">
                    <a href="#" class="test-bank-link" id="pause-test-bank-link">${getString('link_test_bank', 'Test Bank Home Screen')}</a>
                </div>
                <div class="pause-message">
                    <p>${getString('pause_message', 'Quiz is Paused. Click Unpause to continue.')}</p>
                </div>
                <div class="pause-actions">
                    <button id="pause-unpause-btn" class="unpause-btn">${getString('pause_unpause', 'Unpause')}</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        
        // Add event listeners after creating the overlay
        const testBankLink = document.getElementById('pause-test-bank-link');
        if (testBankLink) {
            testBankLink.addEventListener('click', saveAndGoHomeFromPause);
        }
        
        const unpauseBtn = document.getElementById('pause-unpause-btn');
        if (unpauseBtn) {
            unpauseBtn.addEventListener('click', togglePause);
        }
    }
    overlay.classList.remove('js-element-hidden');
    overlay.classList.add('js-element-flex');
}

function hidePauseOverlay() {
    const overlay = document.getElementById('pause-overlay');
    if (overlay) {
        overlay.classList.remove('js-element-flex');
        overlay.classList.add('js-element-hidden');
    }
}

function saveAndGoHomeFromPause(event) {
    event.preventDefault();
    
    // Use exactly the same logic as prepareSaveAndGoHome
    // Collect all answers, status and marked for review
    const answers = {};
    // Don't reset status, correct, and markedforreview - use global arrays
    // But we still need to collect the correct values for questions that have answers
    for (let i = 1; i <= totalQuestions; i++) {
        const selectedAnswer = document.querySelector(`input[name="answer-${i}"]:checked`);
        if (selectedAnswer) {
            // Only process if this is a new answer (different from saved answer)
            const questionDataForSave = getQuestionData(i);
            const savedAnswer = questionDataForSave ? questionDataForSave.saved_answer : null;
            const isNewAnswer = savedAnswer !== selectedAnswer.value;
            
            if (!isNewAnswer) {
                // Answer hasn't changed, skip processing
                continue;
            }
            answers[i] = selectedAnswer.value;
            status[i] = 2; // Complete
        } else {
            // Check if question has been seen (has status cell in navigator)
            const statusCell = document.querySelector(`#status-${i}`);
            if (statusCell && statusCell.textContent !== "Unseen") {
                status[i] = 1; // Incomplete
            } else {
                status[i] = 0; // Unseen
            }
        }
        
        // Use global markedforreview array (already updated by toggleReviewFlag)
    }
    
    // For study sessions, save the elapsed time (always positive)
    let actualSeconds = timeRemaining;
    
    // Create a form to submit the data
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'perform_study.php';
    form.classList.add('js-element-hidden');
    
    // Add form fields
    const examContainer = document.querySelector('.exam-container');
    const courseModuleId = examContainer ? examContainer.dataset.id : '';
    const examId = examContainer ? examContainer.dataset.examId : '';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = courseModuleId;
    form.appendChild(idInput);
    
    const examidInput = document.createElement('input');
    examidInput.type = 'hidden';
    examidInput.name = 'examid';
    examidInput.value = examId;
    form.appendChild(examidInput);
    
    const timeremainingInput = document.createElement('input');
    timeremainingInput.type = 'hidden';
    timeremainingInput.name = 'timeremaining';
    timeremainingInput.value = actualSeconds;
    form.appendChild(timeremainingInput);
    
    const answersInput = document.createElement('input');
    answersInput.type = 'hidden';
    answersInput.name = 'answers';
    answersInput.value = JSON.stringify(answers);
    form.appendChild(answersInput);
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = JSON.stringify(status);
    form.appendChild(statusInput);
    
    const markedforreviewInput = document.createElement('input');
    markedforreviewInput.type = 'hidden';
    markedforreviewInput.name = 'markedforreview';
    markedforreviewInput.value = JSON.stringify(markedforreview);
    form.appendChild(markedforreviewInput);
    
    // Debug: Show final correct array before sending
    
    const correctInput = document.createElement('input');
    correctInput.type = 'hidden';
    correctInput.name = 'correct';
    correctInput.value = JSON.stringify(correct);
    form.appendChild(correctInput);
    
    // Get studyunit string from the hidden form field
    const studyunitField = document.getElementById('studyunit-input');
    const studyunitInput = document.createElement('input');
    studyunitInput.type = 'hidden';
    studyunitInput.name = 'studyunit';
    studyunitInput.value = studyunitField ? studyunitField.value : '';
    
    
    form.appendChild(studyunitInput);
    
    const redirectInput = document.createElement('input');
    redirectInput.type = 'hidden';
    redirectInput.name = 'redirect_to_view';
    redirectInput.value = '1';
    form.appendChild(redirectInput);
    
    // Add form to document and submit
    document.body.appendChild(form);
    form.submit();
    
    // Prevent default link behavior
    event.preventDefault();
    return false;
}

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Global variables for search functionality
let searchResults = [];
let currentSearchIndex = -1;
let searchTerm = '';

// Function to clear search results and input field
function clearSearchResults() {
    // Clear search input field
    const searchInput = DOM_CACHE.get('searchInput', '#question-search');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Clear search state
    searchResults = [];
    currentSearchIndex = -1;
    searchTerm = '';
    
    // Clear highlights
    clearSearchHighlights();
    
    // Update counter
    updateSearchCounter();
}

// Create debounced version of search function (300ms delay)
const debouncedSearchQuestions = debounce(searchQuestions, 300);

function searchQuestions() {
    const searchInput = DOM_CACHE.get('searchInput', '#question-search');
    searchTerm = searchInput.value; // Don't trim to allow spaces
    
    // Clear previous highlights
    clearSearchHighlights();
    
    if (searchTerm === '' || searchTerm.length < 3) {
        // Clear search results but don't change question display
        searchResults = [];
        currentSearchIndex = -1;
        updateSearchCounter();
        return;
    }
    
    searchResults = [];
    currentSearchIndex = -1;
    
    // Determine which questions to search in
    let questionsToSearch = [];
    if (isFilterActive) {
        // If filter is active, search only in filtered questions (may be empty)
        questionsToSearch = filteredQuestions;
    } else {
        // If no filter is active, search in all questions
        for (let i = 1; i <= totalQuestions; i++) {
            questionsToSearch.push(i);
        }
    }
    
    // If no questions to search in, show appropriate message and exit
    if (questionsToSearch.length === 0) {
        searchResults = [];
        currentSearchIndex = -1;
        updateSearchCounter();
        
        // Show message that no questions are available to search
        const counter = document.getElementById('search-counter');
        if (counter) {
            counter.textContent = 'No questions available to search in current filter';
        }
        return;
    }
    
    // Search only in the relevant questions
    questionsToSearch.forEach(questionNum => {
        const question = document.getElementById(`question-${questionNum}`);
        if (!question) return;
        
        const questionIndex = questionNum - 1; // Convert to 0-based index for compatibility
        const questionText = question.querySelector('.question-text');
        const questionName = question.querySelector('h3');
        const answerOptions = question.querySelectorAll('.answer-option');
        
        if (questionText && questionName) {
            const textContent = questionText.textContent;
            const nameContent = questionName.textContent;
            
            // Search in question text
            const textMatches = findMatches(textContent, searchTerm);
            const nameMatches = findMatches(nameContent, searchTerm);
            
            // Search in answer options
            let answerMatches = [];
            answerOptions.forEach((answerOption, answerIndex) => {
                const label = answerOption.querySelector('label');
                if (label) {
                    const answerText = label.textContent;
                    const matches = findMatches(answerText, searchTerm);
                    if (matches.length > 0) {
                        answerMatches.push({
                            element: label,
                            matches: matches,
                            answerIndex: answerIndex
                        });
                    }
                }
            });
            
            if (textMatches.length > 0 || nameMatches.length > 0 || answerMatches.length > 0) {
                // Don't change question display, just highlight matches
                
                // Highlight matches in question text
                if (textMatches.length > 0) {
                    highlightMatches(questionText, textMatches);
                }
                
                // Highlight matches in question name
                if (nameMatches.length > 0) {
                    highlightMatches(questionName, nameMatches);
                }
                
                // Highlight matches in answer options
                answerMatches.forEach(answerMatch => {
                    highlightMatches(answerMatch.element, answerMatch.matches);
                });
                
                // Add to search results (using correct questionNum)
                textMatches.forEach(match => {
                    searchResults.push({
                        questionIndex: questionIndex,
                        questionNum: questionNum,
                        element: questionText,
                        match: match
                    });
                });
                
                nameMatches.forEach(match => {
                    searchResults.push({
                        questionIndex: questionIndex,
                        questionNum: questionNum,
                        element: questionName,
                        match: match
                    });
                });
                
                answerMatches.forEach(answerMatch => {
                    answerMatch.matches.forEach(match => {
                        searchResults.push({
                            questionIndex: questionIndex,
                            questionNum: questionNum,
                            element: answerMatch.element,
                            match: match
                        });
                    });
                });
            }
        }
    });
    
    updateSearchCounter();
    
    // If we have results, go to the first one
    if (searchResults.length > 0) {
        currentSearchIndex = 0;
        navigateToSearchResult();
    }
}

function normalizeText(text) {
    return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function findMatches(text, searchTerm) {
    const matches = [];
    const normalizedText = normalizeText(text);
    const normalizedSearchTerm = normalizeText(searchTerm);
    const regex = new RegExp(normalizedSearchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
    let match;
    
    while ((match = regex.exec(normalizedText)) !== null) {
        // Find the original text position to preserve highlighting
        const originalStart = match.index;
        const originalEnd = match.index + match[0].length;
        
        matches.push({
            start: originalStart,
            end: originalEnd,
            text: text.substring(originalStart, originalEnd)
        });
    }
    
    return matches;
}

function highlightMatches(element, matches) {
    const text = element.textContent;
    let highlightedText = '';
    let lastIndex = 0;
    
    matches.forEach(match => {
        highlightedText += text.substring(lastIndex, match.start);
        highlightedText += `<mark class="search-highlight">${match.text}</mark>`;
        lastIndex = match.end;
    });
    
    highlightedText += text.substring(lastIndex);
    element.innerHTML = highlightedText;
}

function clearSearchHighlights() {
    // Remove all search highlights
    document.querySelectorAll('.search-highlight').forEach(highlight => {
        const parent = highlight.parentNode;
        parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
        parent.normalize();
    });
}

function updateSearchCounter() {
    const searchInput = DOM_CACHE.get('searchInput', '#question-search');
    const counter = document.getElementById('search-counter');
    
    if (searchTerm === '') {
        if (counter) {
            counter.textContent = '';
        }
        return;
    }
    
    if (searchResults.length === 0) {
        if (counter) {
            counter.textContent = getString('search_no_matches', 'No matches found');
        }
    } else {
        if (counter) {
            counter.textContent = `${currentSearchIndex + 1} of ${searchResults.length}`;
        }
    }
}

function navigateToSearchResult() {
    if (searchResults.length === 0 || currentSearchIndex < 0) {
        return;
    }
    
    const result = searchResults[currentSearchIndex];
    const question = document.getElementById(`question-${result.questionNum}`);
    
    if (question) {
        // Navigate to the question containing the search result
        goToQuestion(result.questionNum);
        
        // Scroll to the highlighted element
        result.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Highlight the current match
        const highlights = result.element.querySelectorAll('.search-highlight');
        highlights.forEach((highlight, index) => {
            if (index === 0) { // Highlight the first match in this element
                highlight.classList.add('current-search-highlight');
            } else {
                highlight.classList.remove('current-search-highlight');
            }
        });
    }
    
    updateSearchCounter();
}

function nextSearchResult() {
    if (searchResults.length === 0) {
        return;
    }
    
    currentSearchIndex = (currentSearchIndex + 1) % searchResults.length;
    navigateToSearchResult();
}

function previousSearchResult() {
    if (searchResults.length === 0) {
        return;
    }
    
    currentSearchIndex = currentSearchIndex <= 0 ? searchResults.length - 1 : currentSearchIndex - 1;
    navigateToSearchResult();
}

function deleteSession() {
    showDeleteConfirmationModal();
}

function showDeleteConfirmationModal() {
    // Create modal if it doesn't exist
    let modal = DOM_CACHE.get('deleteModal', '#delete-confirmation-modal');
    if (!modal) {
        // Clear cache since we're creating a new element
        DOM_CACHE.clear('deleteModal');
        modal = document.createElement('div');
        modal.id = 'delete-confirmation-modal';
        modal.className = '';
        modal.innerHTML = `
            <div class="modal-content delete-modal-content">
                <div class="modal-header">
                    <h3>${getString('delete_title', 'DISCARD SESSION')}</h3>
                    <span class="modal-close-btn" id="delete-modal-close">&times;</span>
                </div>
                <div class="modal-body">
                    <p>${getString('delete_message', 'Are you sure you want to discard this quiz?')}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn-go-back" id="delete-modal-go-back">${getString('delete_go_back', 'Go Back')}</button>
                    <button class="btn-discard" id="delete-modal-discard">${getString('delete_discard', 'Discard')}</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Update cache with new element
        DOM_CACHE.deleteModal = modal;
        
        // Add event listeners
        const closeBtn = document.getElementById('delete-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeDeleteConfirmationModal);
        }
        
        const goBackBtn = document.getElementById('delete-modal-go-back');
        if (goBackBtn) {
            goBackBtn.addEventListener('click', closeDeleteConfirmationModal);
        }
        
        const discardBtn = document.getElementById('delete-modal-discard');
        if (discardBtn) {
            discardBtn.addEventListener('click', confirmDeleteSession);
        }
    }
    modal.classList.remove('js-element-hidden');
    modal.classList.add('js-element-visible');
}

function closeDeleteConfirmationModal() {
    const modal = DOM_CACHE.get('deleteModal', '#delete-confirmation-modal');
    if (modal) {
        modal.classList.remove('js-element-visible');
        modal.classList.add('js-element-hidden');
    }
}

function confirmDeleteSession() {
    // Get course module ID and exam ID
    const examContainer = document.querySelector('.exam-container');
    const courseModuleId = examContainer ? examContainer.dataset.id : '';
    const examId = examContainer ? examContainer.dataset.examId : '';
    
    // Create form data to delete the session
    const formData = new FormData();
    formData.append('id', courseModuleId);
    formData.append('examid', examId);
    formData.append('delete_session', '1');
    
    // Submit delete request
    fetch('perform_study.php', {
        method: 'POST',
        body: formData
    }).then(() => {
        // Redirect to view.php after successful deletion
        window.location.href = 'view.php?id=' + courseModuleId;
    }).catch(error => {
        console.error('Error deleting session:', error);
        // Still redirect even if delete fails
        window.location.href = 'view.php?id=' + courseModuleId;
    });
}


function prepareSaveAndLogout(event) {
    if (!confirm(getString('confirm_save_logout', 'Are you sure you want to save your progress and logout? Your answers and remaining time will be saved.'))) {
        event.preventDefault();
        return false;
    }
    
    // Collect all answers, status, marked for review, and correct status
    const answers = {};
    // Don't reset status, correct, and markedforreview - use global arrays
    // But we still need to collect the correct values for questions that have answers
    
    
    
    
    for (let i = 1; i <= totalQuestions; i++) {
        const selectedAnswer = document.querySelector(`input[name="answer-${i}"]:checked`);
        if (selectedAnswer) {
            // Only process if this is a new answer (different from saved answer)
            const questionDataForSave = getQuestionData(i);
            const savedAnswer = questionDataForSave ? questionDataForSave.saved_answer : null;
            const isNewAnswer = savedAnswer !== selectedAnswer.value;
            
            if (!isNewAnswer) {
                // Answer hasn't changed, skip processing
                continue;
            }
            answers[i] = selectedAnswer.value;
            
            // correct and status are already updated dynamically in showAnswerFeedback
            // No need to determine them here - just use the global arrays
            
            const questionData = getQuestionData(i);
            if (questionData) {
                const selectedAnswerData = questionData.answers.find(answer => answer.id == selectedAnswer.value);
                
                if (selectedAnswerData && selectedAnswerData.fraction > 0) {
                    // Answer is correct
                    // correct value already determined in showAnswerFeedback
                }
            }
            
            // NOW update status to Complete
            status[i] = 2; // Complete
            
        } else {
            // Check if question has been seen (has status cell in navigator)
            const statusCell = document.querySelector(`#status-${i}`);
            if (statusCell && statusCell.textContent !== "Unseen") {
                status[i] = 1; // Incomplete
            } else {
                status[i] = 0; // Unseen
            }
            correct[i] = 0; // No answer = incorrect
        }
        
        // Use global markedforreview array (already updated by toggleReviewFlag)
    }
    
    // For study sessions, save the elapsed time (always positive)
    let actualSeconds = timeRemaining;
    

    
    // Update hidden form fields with current values
    const timeremainingInput = document.getElementById('timeremaining-input');
    const answersInput = document.getElementById('answers-input');
    const statusInput = document.getElementById('status-input');
    const markedforreviewInput = document.getElementById('markedforreview-input');
    const correctInput = document.getElementById('correct-input');
    
    if (timeremainingInput) {
        timeremainingInput.value = actualSeconds;
    }
    
    if (answersInput) {
        answersInput.value = JSON.stringify(answers);
    }
    
    if (statusInput) {
        statusInput.value = JSON.stringify(status);
    }
    
    if (correctInput) {
        correctInput.value = JSON.stringify(correct);
    }
    
    if (markedforreviewInput) {
        markedforreviewInput.value = JSON.stringify(markedforreview);
    }
    
    // Submit form
    const form = timeremainingInput.form;
    form.submit();
    
    // Prevent default form submission
    event.preventDefault();
}

function prepareSaveAndGoHome(event) {
    // Prevent default form submission
    event.preventDefault();
    
    // Collect all answers, status, marked for review, and correct status
    const answers = {};
    // Don't reset status, correct, and markedforreview - use global arrays
    // But we still need to collect the correct values for questions that have answers
    
    
    
    for (let i = 1; i <= totalQuestions; i++) {
        const selectedAnswer = document.querySelector(`input[name="answer-${i}"]:checked`);
        if (selectedAnswer) {
            // Only process if this is a new answer (different from saved answer)
            const questionDataForSave = getQuestionData(i);
            const savedAnswer = questionDataForSave ? questionDataForSave.saved_answer : null;
            const isNewAnswer = savedAnswer !== selectedAnswer.value;
            
            if (!isNewAnswer) {
                // Answer hasn't changed, skip processing
                continue;
            }
            answers[i] = selectedAnswer.value;
            
            // correct and status are already updated dynamically in showAnswerFeedback
            // No need to determine them here - just use the global arrays
            
            const questionData = getQuestionData(i);
            if (questionData) {
                const selectedAnswerData = questionData.answers.find(answer => answer.id == selectedAnswer.value);
                
                if (selectedAnswerData && selectedAnswerData.fraction > 0) {
                    // Answer is correct
                    // correct value already determined in showAnswerFeedback
                }
            }
            
            // NOW update status to Complete
            status[i] = 2; // Complete
            
        } else {
            // Check if question has been seen (has status cell in navigator)
            const statusCell = document.querySelector(`#status-${i}`);
            if (statusCell && statusCell.textContent !== "Unseen") {
                status[i] = 1; // Incomplete
            } else {
                status[i] = 0; // Unseen
            }
            correct[i] = 0; // No answer = incorrect
        }
        
        // Use global markedforreview array (already updated by toggleReviewFlag)
    }
    
    // For study sessions, save the elapsed time (always positive)
    let actualSeconds = timeRemaining;
    
    // Create a form to submit the data
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'perform_study.php';
    form.classList.add('js-element-hidden');
    
    // Add form fields
    const examContainer = document.querySelector('.exam-container');
    const courseModuleId = examContainer ? examContainer.dataset.id : '';
    const examId = examContainer ? examContainer.dataset.examId : '';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = courseModuleId;
    form.appendChild(idInput);
    
    const examidInput = document.createElement('input');
    examidInput.type = 'hidden';
    examidInput.name = 'examid';
    examidInput.value = examId;
    form.appendChild(examidInput);
    
    const timeremainingInput = document.createElement('input');
    timeremainingInput.type = 'hidden';
    timeremainingInput.name = 'timeremaining';
    timeremainingInput.value = actualSeconds;
    form.appendChild(timeremainingInput);
    
    const answersInput = document.createElement('input');
    answersInput.type = 'hidden';
    answersInput.name = 'answers';
    answersInput.value = JSON.stringify(answers);
    form.appendChild(answersInput);
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = JSON.stringify(status);
    form.appendChild(statusInput);
    
    const markedforreviewInput = document.createElement('input');
    markedforreviewInput.type = 'hidden';
    markedforreviewInput.name = 'markedforreview';
    markedforreviewInput.value = JSON.stringify(markedforreview);
    form.appendChild(markedforreviewInput);
    
    // Debug: Show final correct array before sending
    
    const correctInput = document.createElement('input');
    correctInput.type = 'hidden';
    correctInput.name = 'correct';
    correctInput.value = JSON.stringify(correct);
    form.appendChild(correctInput);
    
    // Get studyunit string from the hidden form field
    const studyunitField = document.getElementById('studyunit-input');
    const studyunitInput = document.createElement('input');
    studyunitInput.type = 'hidden';
    studyunitInput.name = 'studyunit';
    studyunitInput.value = studyunitField ? studyunitField.value : '';
    
    
    form.appendChild(studyunitInput);
    
    const redirectInput = document.createElement('input');
    redirectInput.type = 'hidden';
    redirectInput.name = 'redirect_to_view';
    redirectInput.value = '1';
    form.appendChild(redirectInput);
    
    // Add form to document and submit
    document.body.appendChild(form);
    form.submit();
    
    // Prevent default link behavior
    event.preventDefault();
    return false;
}

// Event listener functions
function addMainControlEventListeners() {
    // Pause button
    const pauseBtn = document.getElementById('pause-btn');
    if (pauseBtn) {
        pauseBtn.addEventListener('click', togglePause);
    }
    
    // Search button
    const searchBtn = document.getElementById('search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', searchQuestions);
    }
    
    // Search input field
    const searchInput = DOM_CACHE.get('searchInput', '#question-search');
    if (searchInput) {
        searchInput.addEventListener('input', debouncedSearchQuestions);
        searchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (searchResults.length > 0) {
                    nextSearchResult();
                } else {
                    searchQuestions(); // Use immediate search on Enter
                }
            } else if (event.key === 'F3') {
                event.preventDefault();
                if (event.shiftKey) {
                    previousSearchResult();
                } else {
                    nextSearchResult();
                }
            }
        });
    }
    
    // Search navigation buttons
    const nextSearchBtn = document.getElementById('next-search-btn');
    if (nextSearchBtn) {
        nextSearchBtn.addEventListener('click', nextSearchResult);
    }
    
    const prevSearchBtn = document.getElementById('prev-search-btn');
    if (prevSearchBtn) {
        prevSearchBtn.addEventListener('click', previousSearchResult);
    }
    
    // Delete button
    const deleteBtn = document.getElementById('delete-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', deleteSession);
    }
    
    // Grade button
    const gradeBtn = document.getElementById('grade-btn');
    if (gradeBtn) {
        gradeBtn.addEventListener('click', showGradeConfirmation);
    }
    
    // Test Bank Home Screen link
    const testBankLink = document.getElementById('test-bank-link');
    if (testBankLink) {
        testBankLink.addEventListener('click', prepareSaveAndGoHome);
    }
    
    // Review flag
    const reviewFlag = document.getElementById('review-flag');
    if (reviewFlag) {
        reviewFlag.addEventListener('click', toggleReviewFlag);
    }
}

function addNavigationEventListeners() {
    // Previous button
    const prevBtn = document.getElementById('prev-btn');
    if (prevBtn) {
        prevBtn.addEventListener('click', previousQuestion);
    }
    
    // Next button
    const nextBtn = document.getElementById('next-btn');
    if (nextBtn) {
        nextBtn.addEventListener('click', nextQuestion);
    }
}

function addFilterEventListeners() {
    // Filter button
    const filterBtn = document.getElementById('filter-btn');
    const filterMenu = DOM_CACHE.get('filterMenu', '#filter-menu');
    const filterCloseBtn = document.getElementById('filter-close-btn');
    
    if (filterBtn && filterMenu) {
        filterBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (filterMenu.classList.contains('js-element-hidden')) {
                filterMenu.classList.remove('js-element-hidden');
                filterMenu.classList.add('js-element-visible');
                updateFilterCounts();
            } else {
                filterMenu.classList.remove('js-element-visible');
                filterMenu.classList.add('js-element-hidden');
            }
        });
    }
    
    if (filterCloseBtn && filterMenu) {
        filterCloseBtn.addEventListener('click', function() {
            filterMenu.classList.remove('js-element-visible');
            filterMenu.classList.add('js-element-hidden');
        });
    }
    
    // Auto-apply filter when radio button is clicked
    const filterRadios = document.querySelectorAll('input[name="filter-option"]');
    filterRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            applyFilter();
            // Menu stays open - no automatic closing
        });
    });
    
    // Close filter menu when clicking outside
    document.addEventListener('click', function(e) {
        if (filterMenu && !filterMenu.contains(e.target) && !filterBtn.contains(e.target)) {
            filterMenu.classList.remove('js-element-visible');
            filterMenu.classList.add('js-element-hidden');
        }
    });
}

function updateFilterCounts() {
    // Count questions by status
    let incompleteCount = 0;
    let markedCount = 0;
    let incorrectCount = 0;
    
    // Count questions based on current data
    for (let i = 1; i <= totalQuestions; i++) {
        const currentStatus = status[i] || 0;
        const currentCorrect = correct[i] || 0;
        const isMarked = SAVED_MARKEDFORREVIEW && SAVED_MARKEDFORREVIEW[i] == 1;
        
        // Count by status (unanswered questions have status = 0)
        if (currentStatus === 0) {
            incompleteCount++;
        }
        
        // Count by marked status
        if (isMarked) {
            markedCount++;
        }
        
        // Count by correctness (incorrect questions: correct = 0 and status = 2)
        if (currentCorrect === 0 && currentStatus === 2) {
            incorrectCount++;
        }
    }
    
    // Update the count displays
    const incompleteText = document.querySelector('#filter-incomplete .filter-radio-text');
    const markedText = document.querySelector('#filter-marked .filter-radio-text');
    const incorrectText = document.querySelector('#filter-incorrect .filter-radio-text');
    
    if (incompleteText) incompleteText.textContent = `Unanswered Questions (${incompleteCount})`;
    if (markedText) markedText.textContent = `Marked Questions (${markedCount})`;
    if (incorrectText) incorrectText.textContent = `Incorrect Questions (${incorrectCount})`;
}

// Global variable to store filtered question numbers
let filteredQuestions = [];
let isFilterActive = false; // Track if a filter is currently applied

function applyFilter() {
    // Clear search when filter changes
    clearSearchResults();
    
    const filterMenu = DOM_CACHE.get('filterMenu', '#filter-menu');
    const selectedRadio = filterMenu.querySelector('input[name="filter-option"]:checked');
    
    if (!selectedRadio || selectedRadio.value === 'none') {
        // No filters selected, show all questions
        filteredQuestions = [];
        isFilterActive = false; // No filter is active
        showAllQuestions();
        return;
    }
    
    const selectedFilter = selectedRadio.value;
    isFilterActive = true; // A filter is now active
    
    // Find questions that match the selected filter
    filteredQuestions = [];
    for (let i = 1; i <= totalQuestions; i++) {
        const currentStatus = status[i] || 0;
        const currentCorrect = correct[i] || 0;
        const isMarked = SAVED_MARKEDFORREVIEW && SAVED_MARKEDFORREVIEW[i] == 1;
        
        let matchesFilter = false;
        
        // Check status filters
        if (selectedFilter === 'incomplete' && currentStatus === 0) matchesFilter = true;
        
        // Check marked filter
        if (selectedFilter === 'marked' && isMarked) matchesFilter = true;
        
        // Check correctness filters
        if (selectedFilter === 'incorrect' && currentCorrect === 0 && currentStatus === 2) matchesFilter = true;
        
        if (matchesFilter) {
            filteredQuestions.push(i);
        }
    }
    
    // Update sidebar visibility based on filter
    updateSidebarVisibility();
    
    if (filteredQuestions.length > 0) {
        // If current question is not in filtered list, go to first filtered question
        if (!filteredQuestions.includes(currentQuestion)) {
            goToQuestion(filteredQuestions[0]);
        } else {
            // Current question is in filtered list, ensure it's displayed
            showQuestion(currentQuestion);
        }
    } else {
        // No questions match the filter, hide all questions
        hideAllQuestions();
    }
}

function updateSidebarVisibility() {
    // Hide all sidebar items first
    for (let i = 1; i <= totalQuestions; i++) {
        const sidebarElement = document.querySelector(`[data-question="${i}"]`);
        if (sidebarElement) {
            sidebarElement.classList.remove('js-element-visible');
            sidebarElement.classList.add('js-element-hidden');
        }
    }
    
    // Show only filtered questions in sidebar
    if (filteredQuestions.length > 0) {
        filteredQuestions.forEach(questionNum => {
            const sidebarElement = document.querySelector(`[data-question="${questionNum}"]`);
            if (sidebarElement) {
                sidebarElement.classList.remove('js-element-hidden');
                sidebarElement.classList.add('js-element-visible');
            }
        });
    }
}

function showAllQuestions() {
    // Show all sidebar items
    for (let i = 1; i <= totalQuestions; i++) {
        const sidebarElement = document.querySelector(`[data-question="${i}"]`);
        if (sidebarElement) {
            sidebarElement.classList.remove('js-element-hidden');
            sidebarElement.classList.add('js-element-visible');
        }
    }
    
    // Only show the current question (maintain one question per screen)
    showQuestion(currentQuestion);
    
    // Ensure navigation buttons are properly enabled when showing all questions
    // showQuestion() will handle the proper enabled/disabled state based on current position
}

function hideAllQuestions() {
    // Hide all questions in the main area
    document.querySelectorAll(".question").forEach(q => {
        q.classList.remove("active");
        q.classList.remove('js-element-visible');
        q.classList.add('js-element-hidden');
    });
    
    // Disable navigation buttons when no questions are visible
    const prevBtn = document.getElementById("prev-btn");
    const nextBtn = document.getElementById("next-btn");
    if (prevBtn) prevBtn.disabled = true;
    if (nextBtn) nextBtn.disabled = true;
}

function getNextFilteredQuestion(currentQ) {
    if (filteredQuestions.length === 0) {
        // No filter applied, use normal navigation
        return currentQ + 1;
    }
    
    const currentIndex = filteredQuestions.indexOf(currentQ);
    if (currentIndex === -1 || currentIndex === filteredQuestions.length - 1) {
        return null; // No next question
    }
    
    return filteredQuestions[currentIndex + 1];
}

function getPreviousFilteredQuestion(currentQ) {
    if (filteredQuestions.length === 0) {
        // No filter applied, use normal navigation
        return currentQ - 1;
    }
    
    const currentIndex = filteredQuestions.indexOf(currentQ);
    if (currentIndex === -1 || currentIndex === 0) {
        return null; // No previous question
    }
    
    return filteredQuestions[currentIndex - 1];
}

function addReviewEventListeners() {
    // Question links in review (if any exist)
    document.querySelectorAll('.question-link').forEach(link => {
        link.addEventListener('click', function() {
            const questionNum = parseInt(this.getAttribute('data-question'));
            goToQuestion(questionNum);
        });
    });
}

// Calculator functions moved to calculator.js module

function addModalEventListeners() {
    // Instructions modal close
    const instructionsModal = document.getElementById('instructions-modal');
    if (instructionsModal) {
        const closeBtn = instructionsModal.querySelector('.modal-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeInstructions);
        }
    }
    
    // Calculator modal close
    const calculatorModal = document.getElementById('calculator-modal');
    if (calculatorModal) {
        const closeBtn = calculatorModal.querySelector('.modal-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => Calculator.close());
        }
    }
    
    // Grade confirmation modal
    const closeGradeModalBtn = document.getElementById('close-grade-modal-btn');
    if (closeGradeModalBtn) {
        closeGradeModalBtn.addEventListener('click', closeGradeConfirmation);
    }
    
    const goBackBtn = document.getElementById('go-back-btn');
    if (goBackBtn) {
        goBackBtn.addEventListener('click', closeGradeConfirmation);
    }
    
    const confirmGradeBtn = document.getElementById('confirm-grade-btn');
    if (confirmGradeBtn) {
        confirmGradeBtn.addEventListener('click', confirmGrade);
    }
}

// Question number click handlers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables from PHP template
    initializeVariables();
    
    // Set the actual values after initialization
    totalQuestions = TOTAL_QUESTIONS_PLACEHOLDER;
    // For study sessions, use saved elapsed time or start from 0
    timeRemaining = TIME_REMAINING_PLACEHOLDER || 0;
    
    // Initial sidebar height synchronization
    setTimeout(() => {
        syncSidebarHeight();
    }, 200);
    document.querySelectorAll(".question-number").forEach(qn => {
        qn.addEventListener("click", function() {
            const questionNum = parseInt(this.getAttribute("data-question"));
            currentQuestion = questionNum;
            showQuestion(questionNum);
        });
    });

    // Function to show answer feedback
    function showAnswerFeedback(selectedRadio) {
        const questionNum = selectedRadio.name.split('-')[1];
        const answerId = selectedRadio.value;
        const feedbackDiv = document.getElementById(`feedback-${questionNum}-${answerId}`);
        
        if (!feedbackDiv) {
            return;
        }
        
        
        // Determine correct first, then update status
        const questionDataForFeedback = getQuestionData(questionNum);
        if (questionDataForFeedback) {
            const selectedAnswerData = questionDataForFeedback.answers.find(answer => answer.id == answerId);
            
            if (selectedAnswerData && selectedAnswerData.fraction > 0) {
                // Answer is correct
                const currentCorrect = correct[questionNum] || 0;
                const currentStatus = status[questionNum] || 0;
                
                if (currentStatus === 0) {
                    // First time answering - first-time correct
                    correct[questionNum] = 2;
                } else if (currentStatus === 2 && currentCorrect === 0) {
                    // Already answered before but was incorrect - now correct
                    correct[questionNum] = 1;
                }
                // If currentCorrect is already 1 or 2, keep it unchanged
            } else {
                // Answer is incorrect
                correct[questionNum] = 0;
            }
        }
        
        // NOW update status to Complete (2) - any answer marks as complete
        status[questionNum] = 2;
        
        // Hide feedback for other answers in the same question
        const questionElement = document.getElementById(`question-${questionNum}`);
        if (questionElement) {
            const allFeedbackDivs = questionElement.querySelectorAll('.answer-feedback');
            allFeedbackDivs.forEach(feedback => {
                feedback.classList.add('js-element-hidden');
                feedback.classList.remove('show');
            });
        }
        
        // Get the question data from the template
        const questionData = getQuestionData(questionNum);
        
        if (!questionData) {
            return;
        }
        
        // Find the selected answer data
        const selectedAnswer = questionData.answers.find(answer => answer.id == answerId);
        
        if (!selectedAnswer) {
            return;
        }
        
        // Determine if the answer is correct (fraction > 0)
        const isCorrect = selectedAnswer.fraction > 0;
        
        // Check if this is first-time correct based on current dynamic correct value
        const isFirstTimeCorrect = correct[questionNum] === 2;
        
        // Update feedback display
        const feedbackIconCorrect = feedbackDiv.querySelector('.feedback-icon-correct');
        const feedbackIconIncorrect = feedbackDiv.querySelector('.feedback-icon-incorrect');
        const feedbackResult = feedbackDiv.querySelector('.feedback-result');
        const feedbackMessage = feedbackDiv.querySelector('.feedback-message');
        
        // Clear previous classes
        feedbackDiv.className = 'answer-feedback';
        
        // Set correct/incorrect styling
        if (isCorrect) {
            feedbackDiv.classList.add('correct');
            feedbackIconCorrect.classList.add('js-element-visible');
            feedbackIconCorrect.classList.remove('js-element-hidden');
            feedbackIconIncorrect.classList.add('js-element-hidden');
            feedbackIconIncorrect.classList.remove('js-element-visible');
            feedbackResult.textContent = getString('feedback_correct', 'Correct!');
            feedbackResult.className = 'feedback-result correct';
        } else {
            feedbackDiv.classList.add('incorrect');
            feedbackIconCorrect.classList.add('js-element-hidden');
            feedbackIconCorrect.classList.remove('js-element-visible');
            feedbackIconIncorrect.classList.add('js-element-visible');
            feedbackIconIncorrect.classList.remove('js-element-hidden');
            feedbackResult.textContent = getString('feedback_incorrect', 'Incorrect');
            feedbackResult.className = 'feedback-result incorrect';
        }
        
        // Set feedback message
        feedbackMessage.innerHTML = selectedAnswer.feedback || getString('feedback_no_feedback', 'No feedback available.');
        
        // Show the feedback
        feedbackDiv.classList.add('show');
        feedbackDiv.classList.remove('js-element-hidden');
        feedbackDiv.classList.add('js-element-visible');
        
        // Update sidebar feedback icon
        updateSidebarFeedback(questionNum, isCorrect, isFirstTimeCorrect);
        
        // Sync sidebar height with question content after feedback is shown
        syncSidebarHeight();
    }
    
    

    // Function to update sidebar feedback icon
    function updateSidebarFeedback(questionNum, isCorrect, isFirstTimeCorrect = false) {
        const sidebarFeedbackIcon = document.getElementById(`sidebar-feedback-${questionNum}`);
        
        if (!sidebarFeedbackIcon) {
            return;
        }
        
        const correctIcon = sidebarFeedbackIcon.querySelector('.sidebar-feedback-correct');
        const incorrectIcon = sidebarFeedbackIcon.querySelector('.sidebar-feedback-incorrect');
        const firstTimeIcon = sidebarFeedbackIcon.querySelector('.sidebar-feedback-first-time');
        
        // Show the feedback icon container
        sidebarFeedbackIcon.classList.remove('sidebar-feedback-hidden');
        
        // Hide all icons first
        correctIcon.classList.remove('js-element-visible');
        incorrectIcon.classList.remove('js-element-visible');
        firstTimeIcon.classList.remove('js-element-visible');
        
        // Show appropriate icon
        if (isCorrect) {
            if (isFirstTimeCorrect) {
                firstTimeIcon.classList.add('js-element-visible');
            } else {
                correctIcon.classList.add('js-element-visible');
            }
        } else {
            incorrectIcon.classList.add('js-element-visible');
        }
    }

    // Function to show feedback for already selected answers on page load
    function showExistingFeedback() {
        // Find all checked radio buttons
        const checkedRadios = document.querySelectorAll("input[type=\"radio\"]:checked");
        
        checkedRadios.forEach((radio, index) => {
            // Only process answer radio buttons (not other radio buttons on the page)
            if (radio.name.startsWith('answer-')) {
                showAnswerFeedback(radio);
            }
        });
    }

    // Answer selection handlers
    const radioButtons = document.querySelectorAll("input[type=\"radio\"]");
    
    radioButtons.forEach((radio, index) => {
        // Add event listeners to radio buttons
        radio.addEventListener("change", function() {
            // Update question status when an answer is selected
            updateQuestionStatus(currentQuestion);
            
            // Show feedback for the selected answer
            showAnswerFeedback(this);
        });
    });
    
    // Show feedback for already selected answers on page load
    showExistingFeedback();

    // Sidebar toggle functionality
    const toggleArrowBtn = document.getElementById('toggle-arrow-btn');
    const toggleArrowBtnNav = document.getElementById('toggle-arrow-btn-nav');
    const questionsLayout = document.querySelector('.questions-layout');
    const toggleArrowIcon = document.getElementById('toggle-arrow-icon');
    const toggleArrowIconNav = document.getElementById('toggle-arrow-icon-nav');
    
    
    function toggleSidebar() {
        // Toggle the sidebar-collapsed class
        questionsLayout.classList.toggle('sidebar-collapsed');
        
        
        // Update the arrow icon direction
        if (questionsLayout.classList.contains('sidebar-collapsed')) {
            if (toggleArrowIcon) {
                toggleArrowIcon.className = 'fas fa-chevron-left';
            }
            if (toggleArrowIconNav) {
                toggleArrowIconNav.className = 'fas fa-chevron-left';
            }
            if (toggleArrowBtn) {
                toggleArrowBtn.title = 'Show sidebar';
            }
            if (toggleArrowBtnNav) {
                toggleArrowBtnNav.title = 'Show sidebar';
                // Use CSS classes instead of inline styles
                toggleArrowBtnNav.classList.add('js-sidebar-toggle-visible');
                
                // Also apply class to parent container
                const parentContainer = toggleArrowBtnNav.parentElement;
                if (parentContainer) {
                    parentContainer.classList.add('js-parent-container-visible');
                }
                
                // Adjust navigation buttons position when arrow is shown
                const navigationRight = DOM_CACHE.get('navigationRight', '.navigation-right');
                if (navigationRight) {
                    navigationRight.classList.add('js-navigation-flex-end');
                }
            }
        } else {
            if (toggleArrowIcon) {
                toggleArrowIcon.className = 'fas fa-chevron-right';
            }
            if (toggleArrowIconNav) {
                toggleArrowIconNav.className = 'fas fa-chevron-right';
            }
            if (toggleArrowBtn) {
                toggleArrowBtn.title = 'Hide sidebar';
            }
            if (toggleArrowBtnNav) {
                toggleArrowBtnNav.title = 'Hide sidebar';
                // Use CSS classes instead of inline styles
                toggleArrowBtnNav.classList.remove('js-sidebar-toggle-visible');
                toggleArrowBtnNav.classList.add('js-sidebar-toggle-hidden');
            }
            
            // Adjust navigation buttons position when arrow is hidden
            const navigationRight = DOM_CACHE.get('navigationRight', '.navigation-right');
            if (navigationRight) {
                navigationRight.classList.add('js-navigation-flex-end');
            }
        }
    }
    
    if (toggleArrowBtn && questionsLayout) {
        toggleArrowBtn.addEventListener('click', toggleSidebar);
    }
    
    if (toggleArrowBtnNav && questionsLayout) {
        toggleArrowBtnNav.addEventListener('click', toggleSidebar);
    }

    // Save & Logout button handler
    const saveLogoutBtn = document.getElementById('save-logout-btn');
    if (saveLogoutBtn) {
        saveLogoutBtn.addEventListener('click', function(event) {
            prepareSaveAndLogout(event);
        });
    }

    // Initialize hidden form fields
    const timeremainingInput = document.getElementById('timeremaining-input');
    if (timeremainingInput) {
        timeremainingInput.value = timeRemaining;
    }
    
    // Initialize question status and marked for review from saved data
    initializeQuestionStatus();
    initializeMarkedForReview();
    
    // Update review flag for current question (first question)
    // Wait a bit to ensure DOM is fully loaded
    setTimeout(() => {
        updateReviewFlag();
    }, 100);

    // Start timer
    timerInterval = setInterval(updateTimer, 1000);


    // Keyboard navigation
    document.addEventListener("keydown", function(e) {
        if (e.key === "ArrowLeft" && currentQuestion > 1) {
            previousQuestion();
        } else if (e.key === "ArrowRight" && currentQuestion < totalQuestions) {
            nextQuestion();
        }
    });

    // Add event listeners for main control buttons
    addMainControlEventListeners();
    
    // Add event listeners for navigation buttons
    addNavigationEventListeners();
    
    // Add event listeners for review buttons
    addReviewEventListeners();
    
    // Add event listeners for filter menu
    addFilterEventListeners();
    
    // Calculator event listeners are now handled by calculator.js module
    
    // Add event listeners for modal click outside
    addModalClickListeners();
    
    // Add event listeners for modal buttons
    addModalEventListeners();
    
    // Calculator button event listener (consolidated from second DOMContentLoaded)
    const calculatorBtn = DOM_CACHE.get('calculatorBtn', '#calculator');
    if (calculatorBtn) {
        calculatorBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            Calculator.open();
        });
    }
});

// Calculator functions moved to calculator.js module

// Close modals when clicking outside
function addModalClickListeners() {
    const instructionsModal = DOM_CACHE.get('instructionsModal', '#instructions-modal');
    
    if (instructionsModal) {
        instructionsModal.addEventListener('click', function(event) {
            if (event.target === instructionsModal) {
                closeInstructions();
            }
        });
    }
}



function goToQuestion(questionNum) {
    if (questionNum >= 1 && questionNum <= totalQuestions) {
        currentQuestion = questionNum;
        showQuestion(questionNum);
    }
}




// Review flag functions
function toggleReviewFlag() {
    const flag = document.getElementById("review-flag");
    const text = document.getElementById("review-text");
    const checkbox = document.getElementById("marked-" + currentQuestion);
    
    if (flag.classList.contains("unmarked")) {
        // Mark for review
        flag.classList.remove("unmarked");
        flag.classList.add("marked");
        flag.textContent = "🏁";
        if (text) {
            text.classList.add("marked");
        }
        if (checkbox) {
            checkbox.checked = true;
            checkbox.closest("tr").classList.add("marked-for-review");
        }
        // Update saved state
        if (!SAVED_MARKEDFORREVIEW) {
            SAVED_MARKEDFORREVIEW = {};
        }
        SAVED_MARKEDFORREVIEW[currentQuestion] = true;
        // Update dynamic array
        markedforreview[currentQuestion] = 1;
        // Update sidebar - add flag to current question
        updateSidebarReviewFlag(currentQuestion, true);
    } else {
        // Unmark from review
        flag.classList.remove("marked");
        flag.classList.add("unmarked");
        flag.textContent = "🏳️";
        if (text) {
            text.classList.remove("marked");
        }
        if (checkbox) {
            checkbox.checked = false;
            checkbox.closest("tr").classList.remove("marked-for-review");
        }
        // Update saved state
        if (!SAVED_MARKEDFORREVIEW) {
            SAVED_MARKEDFORREVIEW = {};
        }
        SAVED_MARKEDFORREVIEW[currentQuestion] = false;
        // Update dynamic array
        markedforreview[currentQuestion] = 0;
        // Update sidebar - remove flag from current question
        updateSidebarReviewFlag(currentQuestion, false);
    }
}

function updateReviewFlag() {
    const flag = document.getElementById("review-flag");
    const text = document.getElementById("review-text");
    const checkbox = document.getElementById("marked-" + currentQuestion);
    
    if (!flag) {
        console.error("review-flag element not found");
        return;
    }
    
    // Check if current question is marked for review using saved data
    const isMarked = SAVED_MARKEDFORREVIEW && SAVED_MARKEDFORREVIEW[currentQuestion] == 1;
    
    if (isMarked) {
        flag.classList.remove("unmarked");
        flag.classList.add("marked");
        flag.textContent = "🏁";
        if (text) {
            text.classList.add("marked");
        }
        if (checkbox) {
            checkbox.checked = true;
        }
        // Update sidebar - add flag to current question
        updateSidebarReviewFlag(currentQuestion, true);
    } else {
        flag.classList.remove("marked");
        flag.classList.add("unmarked");
        flag.textContent = "🏳️";
        if (text) {
            text.classList.remove("marked");
        }
        if (checkbox) {
            checkbox.checked = false;
        }
        // Update sidebar - remove flag from current question
        updateSidebarReviewFlag(currentQuestion, false);
    }
}

function updateSidebarReviewFlag(questionNum, isMarked) {
    // Find the question number element in the sidebar
    const sidebarQuestion = document.querySelector(`.question-numbers-sidebar .question-number[data-question="${questionNum}"]`);
    
    if (sidebarQuestion) {
        if (isMarked) {
            // Add flag emoji to the sidebar question
            sidebarQuestion.classList.add('marked-for-review');
            // Check if flag is already there, if not add it
            if (!sidebarQuestion.querySelector('.review-flag-sidebar')) {
                const flagSpan = document.createElement('span');
                flagSpan.className = 'review-flag-sidebar js-review-flag-sidebar';
                flagSpan.textContent = '🏁';
                sidebarQuestion.appendChild(flagSpan);
            }
        } else {
            // Remove flag from sidebar question
            sidebarQuestion.classList.remove('marked-for-review');
            const existingFlag = sidebarQuestion.querySelector('.review-flag-sidebar');
            if (existingFlag) {
                existingFlag.remove();
            }
        }
    }
}

function initializeQuestionStatus() {
    // Check if we have saved status data
    if (typeof SAVED_STATUS !== 'undefined' && Object.keys(SAVED_STATUS).length > 0) {
        for (let questionNum in SAVED_STATUS) {
            const status = SAVED_STATUS[questionNum];
            const statusCell = document.querySelector(`#status-${questionNum}`);
            
            if (statusCell) {
                if (status === 0) {
                    statusCell.className = "status-unseen";
                    statusCell.textContent = getString('status_unseen', 'Unseen');
                } else if (status === 1) {
                    statusCell.className = "status-incomplete";
                    statusCell.textContent = getString('status_incomplete', 'Incomplete');
                    seenQuestions.add(parseInt(questionNum));
                } else if (status === 2) {
                    statusCell.className = "status-complete";
                    statusCell.textContent = getString('status_complete', 'Complete');
                    seenQuestions.add(parseInt(questionNum));
                }
            }
        }
    }
}

function initializeMarkedForReview() {
    // Check if we have saved marked for review data
    if (typeof SAVED_MARKEDFORREVIEW !== 'undefined' && Object.keys(SAVED_MARKEDFORREVIEW).length > 0) {
        for (let questionNum in SAVED_MARKEDFORREVIEW) {
            const marked = SAVED_MARKEDFORREVIEW[questionNum];
            const checkbox = document.querySelector(`#marked-${questionNum}`);
            
            if (checkbox) {
                if (marked === 1) {
                    checkbox.checked = true;
                    checkbox.closest("tr").classList.add("marked-for-review");
                } else {
                    checkbox.checked = false;
                    checkbox.closest("tr").classList.remove("marked-for-review");
                }
            }
            
            // Update sidebar for all questions (regardless of checkbox existence)
            updateSidebarReviewFlag(questionNum, marked === 1);
        }
    }
}

