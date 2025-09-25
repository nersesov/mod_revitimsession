/**
 * Calculator Module for Revitim Session Plugin
 * 
 * This module provides calculator functionality for both Study Sessions and Practice Exams.
 * It includes basic and scientific calculator modes with full functionality.
 */

// Calculator Module
const Calculator = {
    // Initialize the calculator
    init: function() {
        this.createCalculatorHTML();
        this.addEventListeners();
        this.setMode('basic'); // Default to basic mode
    },

    // Create calculator HTML structure
    createCalculatorHTML: function() {
        // Check if calculator modal already exists
        let modal = document.getElementById('calculator-modal');
        if (modal) {
            return; // Already exists, don't recreate
        }

        // Create modal structure
        modal = document.createElement('div');
        modal.id = 'calculator-modal';
        modal.className = 'calc-modal calc-hidden';
        
        modal.innerHTML = `
            <div class="calc-content">
                <div class="calc-header">
                    <h3>Calculator</h3>
                    <span class="calc-close-btn">&times;</span>
                </div>
                <div class="calc-mode-buttons">
                    <button class="calc-mode-btn active" id="basic-mode-btn">Basic</button>
                    <button class="calc-mode-btn" id="scientific-mode-btn">Scientific</button>
                </div>
                <input type="text" id="calculator-display" class="calc-display" value="0" readonly>
                <div class="calc-buttons">
                    <div id="basic-buttons" class="calc-basic-buttons">
                        <div class="calc-btn-grid clear">C</div>
                        <div class="calc-btn-grid" data-value="(">(</div>
                        <div class="calc-btn-grid" data-value=")">)</div>
                        <div class="calc-btn-grid operator" data-value="÷">÷</div>
                        
                        <div class="calc-btn-grid" data-value="7">7</div>
                        <div class="calc-btn-grid" data-value="8">8</div>
                        <div class="calc-btn-grid" data-value="9">9</div>
                        <div class="calc-btn-grid operator" data-value="×">×</div>
                        
                        <div class="calc-btn-grid" data-value="4">4</div>
                        <div class="calc-btn-grid" data-value="5">5</div>
                        <div class="calc-btn-grid" data-value="6">6</div>
                        <div class="calc-btn-grid operator" data-value="-">-</div>
                        
                        <div class="calc-btn-grid" data-value="1">1</div>
                        <div class="calc-btn-grid" data-value="2">2</div>
                        <div class="calc-btn-grid" data-value="3">3</div>
                        <div class="calc-btn-grid operator" data-value="+">+</div>
                        
                        <div class="calc-btn-grid" data-value="0">0</div>
                        <div class="calc-btn-grid" data-value=".">.</div>
                        <div class="calc-btn-grid equals" data-action="calculate">=</div>
                    </div>
                    
                    <div id="scientific-buttons" class="calc-scientific-buttons">
                        <!-- Scientific functions row 1 -->
                        <div class="calc-btn-grid" data-scientific="sin">sin</div>
                        <div class="calc-btn-grid" data-scientific="cos">cos</div>
                        <div class="calc-btn-grid" data-scientific="tan">tan</div>
                        <div class="calc-btn-grid" data-value="pi">π</div>
                        
                        <!-- Scientific functions row 2 -->
                        <div class="calc-btn-grid" data-scientific="log">log</div>
                        <div class="calc-btn-grid" data-scientific="ln">ln</div>
                        <div class="calc-btn-grid" data-scientific="sqrt">√</div>
                        <div class="calc-btn-grid" data-value="^">^</div>
                        
                        <!-- Scientific functions row 3 -->
                        <div class="calc-btn-grid" data-scientific="factorial">n!</div>
                        <div class="calc-btn-grid" data-value="e">e</div>
                        <div class="calc-btn-grid" data-scientific="abs">|x|</div>
                        <div class="calc-btn-grid" data-scientific="exp">e^x</div>
                        
                        <!-- Scientific functions row 4 -->
                        <div class="calc-btn-grid" data-scientific="pow">x^y</div>
                    </div>
                </div>
            </div>
        `;
        
        // Append to body
        document.body.appendChild(modal);
    },

    // Add all event listeners for calculator functionality
    addEventListeners: function() {
        // Use event delegation for all calculator interactions
        document.addEventListener('click', (event) => {
            const target = event.target;
            
            // Handle calculator mode buttons
            if (target.id === 'basic-mode-btn') {
                event.preventDefault();
                event.stopPropagation();
                this.setMode('basic');
                return;
            }
            
            if (target.id === 'scientific-mode-btn') {
                event.preventDefault();
                event.stopPropagation();
                this.setMode('scientific');
                return;
            }
            
            // Handle calculator buttons
            const button = target.closest('.calc-btn-grid');
            if (button) {
                event.preventDefault();
                event.stopPropagation();
                
                const action = button.getAttribute('data-action');
                const value = button.getAttribute('data-value');
                const scientific = button.getAttribute('data-scientific');
                
                if (action === 'calculate') {
                    this.calculate();
                } else if (scientific) {
                    this.scientificFunction(scientific);
                } else if (button.classList.contains('clear')) {
                    this.clear();
                } else if (value) {
                    this.appendToDisplay(value);
                }
                return;
            }
            
            // Handle close button
            if (target.classList.contains('calc-close-btn') && target.closest('#calculator-modal')) {
                event.preventDefault();
                event.stopPropagation();
                this.close();
                return;
            }
            
            // Handle click outside calculator modal
            if (target.id === 'calculator-modal') {
                event.preventDefault();
                event.stopPropagation();
                this.close();
                return;
            }
        });
    },

    // Open calculator modal
    open: function() {
        const modal = document.getElementById('calculator-modal');
        if (modal) {
            modal.classList.remove('calc-hidden');
            modal.classList.add('calc-visible');
            this.setMode('basic'); // Reset to basic mode when opening
        }
    },

    // Close calculator modal
    close: function() {
        const modal = document.getElementById('calculator-modal');
        if (modal) {
            modal.classList.remove('calc-visible');
            modal.classList.add('calc-hidden');
        }
    },

    // Set calculator mode (basic or scientific)
    setMode: function(mode) {
        const basicBtn = document.getElementById("basic-mode-btn");
        const scientificBtn = document.getElementById("scientific-mode-btn");
        const basicButtons = document.getElementById("basic-buttons");
        const scientificButtons = document.getElementById("scientific-buttons");
        
        // Check if all elements exist
        if (!basicBtn || !scientificBtn || !basicButtons || !scientificButtons) {
            return;
        }
        
        if (mode === "basic") {
            basicBtn.classList.add("active");
            scientificBtn.classList.remove("active");
            basicBtn.setAttribute('aria-selected', 'true');
            scientificBtn.setAttribute('aria-selected', 'false');
            
            // Hide scientific buttons in basic mode
            scientificButtons.classList.remove('calc-show');
        } else {
            scientificBtn.classList.add("active");
            basicBtn.classList.remove("active");
            scientificBtn.setAttribute('aria-selected', 'true');
            basicBtn.setAttribute('aria-selected', 'false');
            
            // Show scientific buttons in scientific mode
            scientificButtons.classList.add('calc-show');
        }
        
        // Basic buttons are always visible (handled by CSS)
    },

    // Append value to calculator display
    appendToDisplay: function(value) {
        const display = document.getElementById('calculator-display');
        if (!display) return;
        
        // Handle special values
        if (value === "pi") {
            value = Math.PI.toString();
        } else if (value === "e") {
            value = Math.E.toString();
        }
        
        if (display.value === "0" && value !== ".") {
            display.value = value;
        } else {
            display.value += value;
        }
    },

    // Clear calculator display
    clear: function() {
        const display = document.getElementById('calculator-display');
        if (display) {
            display.value = "0";
        }
    },

    // Calculate expression
    calculate: function() {
        const display = document.getElementById('calculator-display');
        if (!display) return;
        
        try {
            let expression = display.value.replace(/×/g, "*").replace(/÷/g, "/");
            expression = expression.replace(/\^/g, "**");
            expression = expression.replace(/pi/g, Math.PI);
            expression = expression.replace(/e/g, Math.E);
            
            const result = eval(expression);
            display.value = result;
        } catch (error) {
            display.value = "Error";
        }
    },

    // Handle scientific functions
    scientificFunction: function(func) {
        const display = document.getElementById('calculator-display');
        if (!display) return;
        
        const value = parseFloat(display.value);
        
        try {
            let result;
            switch(func) {
                case "sin":
                    result = Math.sin(value * Math.PI / 180);
                    break;
                case "cos":
                    result = Math.cos(value * Math.PI / 180);
                    break;
                case "tan":
                    result = Math.tan(value * Math.PI / 180);
                    break;
                case "log":
                    result = Math.log10(value);
                    break;
                case "ln":
                    result = Math.log(value);
                    break;
                case "sqrt":
                    result = Math.sqrt(value);
                    break;
                case "factorial":
                    result = this.factorial(value);
                    break;
                case "abs":
                    result = Math.abs(value);
                    break;
                case "exp":
                    result = Math.exp(value);
                    break;
                case "pow":
                    // For x^y, we need to handle this differently
                    // For now, just show the current value
                    result = value;
                    break;
            }
            display.value = result;
        } catch (error) {
            display.value = "Error";
        }
    },

    // Calculate factorial
    factorial: function(n) {
        if (n < 0) return NaN;
        if (n === 0 || n === 1) return 1;
        let result = 1;
        for (let i = 2; i <= n; i++) {
            result *= i;
        }
        return result;
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Calculator.init());
} else {
    Calculator.init();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Calculator;
}
