document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('verifyForm');
    const shareInput = document.getElementById('share_account_last4');
    const middleName = document.getElementById('middle_name');
    const birthDate = document.getElementById('birth_date');
    const securityError = document.getElementById('security-error');

    // Allow only digits
    shareInput.addEventListener('input', () => {
        shareInput.value = shareInput.value.replace(/\D/g, '');
    });

    form.addEventListener('submit', (e) => {
        let valid = true;

        // Validate 4 digits
        if (shareInput.value.length !== 4) {
            shareInput.classList.add('input-error');
            valid = false;
        }

        // Validate at least one security question
        if (!middleName.value.trim() && !birthDate.value) {
            securityError.classList.remove('hidden');
            valid = false;
        } else {
            securityError.classList.add('hidden');
        }

        if (!valid) e.preventDefault();
    });
});
