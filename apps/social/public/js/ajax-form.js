/**
 * Sinapp Social - Generic AJAX Form Handler with SweetAlert2
 * 
 * Automatically handles any form submission, sends it via AJAX,
 * and displays SweetAlert2 notifications based on the JSON response.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Helper to check if SweetAlert2 is loaded
    const isSwalLoaded = () => typeof Swal !== 'undefined';

    const handleFormSubmit = async (event) => {
        const form = event.target;

        // Skip if the form intentionally wants default behavior (e.g., file downloads usually need standard submit)
        if (form.hasAttribute('data-no-ajax')) return;

        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }

        const formData = new FormData(form);
        const url = form.action;
        const method = form.method;

        try {
            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            // Handle non-JSON responses (e.g., 500 server errors that return HTML)
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned a non-JSON response.');
            }

            const data = await response.json();

            if (isSwalLoaded()) {
                if (data.success || response.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Operation successful!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || data.message || 'An unknown error occurred.',
                        confirmButtonText: 'OK'
                    });
                }
            } else {
                // Fallback if generic alert if Swal not loaded
                alert(data.message || data.error);
                if ((data.success || response.ok) && data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            }

        } catch (error) {
            console.error('Form submission error:', error);
            if (isSwalLoaded()) {
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Something went wrong. Please try again later.',
                });
            } else {
                alert('System Error: Something went wrong.');
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    };

    // Attach listener to all forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });
});
