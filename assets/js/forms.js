/* Forms: AJAX Submission Handling */
(function initAjaxForms() {
    document.querySelectorAll('[data-ajax-form]').forEach(form => {
        form.removeAttribute('onsubmit');
        form.noValidate = true;
        const resp = form.querySelector('.form-response');
        const btn = form.querySelector('.btn-submit');

        function requiredFieldMessage(input) {
            if (!input) return 'Please fill in all required fields.';
            const name = input.getAttribute('name') || '';
            if (name === 'name' || name === 'customer_name') return 'Please enter your full name.';
            if (name === 'email' || name === 'customer_email') return 'Please enter a valid email address.';
            if (name === 'message') return 'Please enter your message.';
            const label = form.querySelector(`label[for="${input.id}"]`)?.textContent?.trim();
            return label ? `Please enter ${label.toLowerCase()}.` : 'Please fill in all required fields.';
        }

        function showFieldError(input) {
            if (!resp) return;
            resp.textContent = requiredFieldMessage(input);
            resp.className = 'form-response visible error-response';
        }

        form.addEventListener('invalid', (event) => {
            event.preventDefault();
            const firstMissing = form.querySelector(':invalid') || event.target;
            if (firstMissing instanceof HTMLElement) {
                showFieldError(firstMissing);
                firstMissing.focus();
            }
        }, true);

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitLabel = form.dataset.submitLabel || btn?.textContent || 'Submit';
            const requiredFields = (form.dataset.requiredFields || '')
                .split(',')
                .map(field => field.trim())
                .filter(Boolean);

            if (!resp || !btn) return;

            const firstMissing = requiredFields
                .map(field => form.querySelector(`[name="${field}"]`))
                .find(input => !input || !input.value.trim());

            const missing = Boolean(firstMissing);

            if (missing) {
                if (firstMissing instanceof HTMLElement) {
                    showFieldError(firstMissing);
                    firstMissing.focus();
                } else {
                    resp.textContent = 'Please fill in all required fields.';
                    resp.className = 'form-response visible error-response';
                }
                return;
            }

            const invalidField = requiredFields
                .map(field => form.querySelector(`[name="${field}"]`))
                .find(input => input && !input.checkValidity());

            if (invalidField instanceof HTMLElement) {
                showFieldError(invalidField);
                invalidField.focus();
                return;
            }

            const hasMissingRequiredField = requiredFields.some(field => {
                const input = form.querySelector(`[name="${field}"]`);
                return !input || !input.value.trim();
            });

            if (hasMissingRequiredField) {
                resp.textContent = 'Please fill in all required fields.';
                resp.className = 'form-response visible error-response';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const fd = new FormData(form);
                const endpoint = form.getAttribute('action');
                const r = await fetch(endpoint, { method: 'POST', body: fd });
                const data = await r.json();
                resp.textContent = data.message || (data.success ? 'Submitted.' : 'Something went wrong.');
                resp.className = 'form-response visible' + (data.success ? '' : ' error-response');
                if (data.success) form.reset();
            } catch {
                resp.textContent = 'Network error. Please try again.';
                resp.className = 'form-response visible error-response';
            }

            btn.disabled = false;
            btn.textContent = submitLabel;
        });
    });
})();

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        const target = document.getElementById(btn.dataset.passwordToggle);
        if (!target) return;

        btn.addEventListener('click', () => {
            const isVisible = target.type === 'text';
            target.type = isVisible ? 'password' : 'text';
            btn.textContent = isVisible ? 'Show' : 'Hide';
            btn.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
        });
    });
});
