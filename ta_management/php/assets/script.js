// Form validation functions
function validateCourseForm() {
    const courseCode = document.getElementById('course_code').value.trim();
    const courseName = document.getElementById('course_name').value.trim();
    const studentsEnrolled = document.getElementById('students_enrolled').value.trim();
    const instructorId = document.getElementById('instructor_id').value;

    if (!courseCode) {
        showError('Course code is required');
        return false;
    }

    if (!courseName) {
        showError('Course name is required');
        return false;
    }

    if (!studentsEnrolled || isNaN(studentsEnrolled) || studentsEnrolled <= 0) {
        showError('Number of students must be a positive number');
        return false;
    }

    if (!instructorId) {
        showError('Please select an instructor');
        return false;
    }

    return true;
}

function validateTAAssignmentForm() {
    const courseId = document.getElementById('course_id').value;
    const taId = document.getElementById('ta_id').value;
    const totalHours = document.getElementById('total_hours').value.trim();

    if (!courseId) {
        showError('Please select a course');
        return false;
    }

    if (!taId) {
        showError('Please select a TA');
        return false;
    }

    if (!totalHours || isNaN(totalHours) || totalHours <= 0) {
        showError('Total hours must be a positive number');
        return false;
    }

    return true;
}

function validateTaskDistributionForm(totalAllocatedHours) {
    const markingHours = parseInt(document.getElementById('marking_hours').value) || 0;
    const proctoringHours = parseInt(document.getElementById('proctoring_hours').value) || 0;
    const labHours = parseInt(document.getElementById('lab_supervision_hours').value) || 0;
    
    if (markingHours < 0 || proctoringHours < 0 || labHours < 0) {
        showError('Hours cannot be negative');
        return false;
    }
    
    const totalDistributedHours = markingHours + proctoringHours + labHours;
    
    if (totalDistributedHours > totalAllocatedHours) {
        showError(`Total distributed hours (${totalDistributedHours}) exceeds allocated hours (${totalAllocatedHours})`);
        return false;
    }
    
    return true;
}

// Utility functions
function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const form = document.querySelector('form');
    form.parentNode.insertBefore(alertDiv, form);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}

// Dynamic task distribution form
function updateRemainingHours() {
    const totalHoursElement = document.getElementById('total_hours');
    if (!totalHoursElement) return;
    
    const totalHours = parseInt(totalHoursElement.dataset.total);
    const markingHours = parseInt(document.getElementById('marking_hours').value) || 0;
    const proctoringHours = parseInt(document.getElementById('proctoring_hours').value) || 0;
    const labHours = parseInt(document.getElementById('lab_supervision_hours').value) || 0;
    
    const totalDistributed = markingHours + proctoringHours + labHours;
    const remaining = totalHours - totalDistributed;
    
    const remainingElement = document.getElementById('remaining_hours');
    if (remainingElement) {
        remainingElement.textContent = remaining;
        
        if (remaining < 0) {
            remainingElement.classList.add('text-danger');
            remainingElement.classList.remove('text-success');
        } else {
            remainingElement.classList.add('text-success');
            remainingElement.classList.remove('text-danger');
        }
    }
}

// Add event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // For task distribution form
    const hoursInputs = document.querySelectorAll('#marking_hours, #proctoring_hours, #lab_supervision_hours');
    hoursInputs.forEach(input => {
        input.addEventListener('input', updateRemainingHours);
    });
    
    // Initialize remaining hours if form exists
    updateRemainingHours();
});
