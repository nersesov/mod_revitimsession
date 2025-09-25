/**
 * JavaScript for create practice exam step 1 page
 * 
 * Handles expand/collapse functionality and checkbox interactions
 * for the category selection interface.
 */

function toggleSubcategories(parentId) {
    var subcategories = document.getElementById("sub-" + parentId);
    var icon = document.getElementById("icon-" + parentId);
    
    if (subcategories.style.display === "none") {
        subcategories.style.display = "block";
        icon.textContent = "-";
    } else {
        subcategories.style.display = "none";
        icon.textContent = "+";
    }
}

// Handle parent checkbox changes
document.addEventListener("DOMContentLoaded", function() {
    // Select All checkbox event listener
    var selectAllCheckbox = document.getElementById("select-all");
    selectAllCheckbox.addEventListener("change", function() {
        var allCheckboxes = document.querySelectorAll(".category-checkbox, .parent-checkbox, .subcategory-checkbox");
        allCheckboxes.forEach(function(checkbox) {
            checkbox.checked = selectAllCheckbox.checked;
        });
    });
    
    // Parent checkbox event listeners
    var parentCheckboxes = document.querySelectorAll(".parent-checkbox");
    parentCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener("change", function() {
            var parentId = this.getAttribute("data-parent");
            var subcategoryCheckboxes = document.querySelectorAll(".subcategory-checkbox[data-parent=\"" + parentId + "\"]");
            
            subcategoryCheckboxes.forEach(function(subCheckbox) {
                subCheckbox.checked = checkbox.checked;
            });
            
            updateSelectAllState();
        });
    });
    
    // Subcategory checkbox event listeners
    var subcategoryCheckboxes = document.querySelectorAll(".subcategory-checkbox");
    subcategoryCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener("change", function() {
            var parentId = this.getAttribute("data-parent");
            var parentCheckbox = document.getElementById("parent-" + parentId);
            var allSubCheckboxes = document.querySelectorAll(".subcategory-checkbox[data-parent=\"" + parentId + "\"]");
            var checkedSubCheckboxes = document.querySelectorAll(".subcategory-checkbox[data-parent=\"" + parentId + "\"]:checked");
            
            // Update parent checkbox based on subcategory states
            if (checkedSubCheckboxes.length === 0) {
                parentCheckbox.checked = false;
                parentCheckbox.indeterminate = false;
            } else if (checkedSubCheckboxes.length === allSubCheckboxes.length) {
                parentCheckbox.checked = true;
                parentCheckbox.indeterminate = false;
            } else {
                parentCheckbox.checked = false;
                parentCheckbox.indeterminate = true;
            }
            
            updateSelectAllState();
        });
    });
    
    // Category checkbox event listeners
    var categoryCheckboxes = document.querySelectorAll(".category-checkbox");
    categoryCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener("change", function() {
            updateSelectAllState();
        });
    });
    
    // Function to update Select All checkbox state
    function updateSelectAllState() {
        var allCheckboxes = document.querySelectorAll(".category-checkbox, .parent-checkbox, .subcategory-checkbox");
        var checkedCheckboxes = document.querySelectorAll(".category-checkbox:checked, .parent-checkbox:checked, .subcategory-checkbox:checked");
        
        if (checkedCheckboxes.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCheckboxes.length === allCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
});
