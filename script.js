const form = document.getElementById("order-form");

form.addEventListener("submit", function(e) {

    const name = document.getElementById("student-name").value.trim();
    const admission = document.getElementById("admission-no").value.trim();
    const dept = document.getElementById("department").value.trim();
    const error = document.getElementById("form-error");

    error.textContent = "";

    if (!name) {
        e.preventDefault();
        error.textContent = "Enter your name";
        return;
    }

    if (!admission) {
        e.preventDefault();
        error.textContent = "Enter admission number";
        return;
    }

    if (!dept) {
        e.preventDefault();
        error.textContent = "Enter department";
        return;
    }

    let hasItem = false;

    document.querySelectorAll('input[type="number"]').forEach(input => {
        if (parseInt(input.value) > 0) {
            hasItem = true;
        }
    });

    if (!hasItem) {
        e.preventDefault();
        error.textContent = "Select at least one item";
    }

});