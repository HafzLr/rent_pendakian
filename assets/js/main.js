// assets/js/main.js - Interactive client-side features for RentGear

document.addEventListener('DOMContentLoaded', () => {
    // 1. Back to Top Button
    const backToTopBtn = document.getElementById('backToTopBtn');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // 2. Quantity Stepper
    document.querySelectorAll('.lk-qty-stepper').forEach(stepper => {
        const minusBtn = stepper.querySelector('.btn-minus');
        const plusBtn = stepper.querySelector('.btn-plus');
        const input = stepper.querySelector('input');

        if (minusBtn && plusBtn && input) {
            minusBtn.addEventListener('click', () => {
                let val = parseInt(input.value) || 1;
                const min = parseInt(input.min) || 1;
                if (val > min) {
                    input.value = val - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });

            plusBtn.addEventListener('click', () => {
                let val = parseInt(input.value) || 1;
                const max = parseInt(input.max) || 999;
                if (val < max) {
                    input.value = val + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }
    });

    // 3. Image preview helper for file inputs
    document.querySelectorAll('.image-preview-input').forEach(input => {
        const previewTargetId = input.dataset.previewTarget;
        const previewImg = document.getElementById(previewTargetId);

        if (previewImg) {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        previewImg.src = event.target.result;
                        previewImg.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });

    // 4. Guest Intercept Trigger
    window.triggerGuestIntercept = function(msg) {
        const modalEl = document.getElementById('guestInterceptModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            if (msg) {
                const msgEl = modalEl.querySelector('.intercept-message');
                if (msgEl) msgEl.textContent = msg;
            }
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            alert('Wajib login terlebih dahulu untuk melanjutkan penyewaan.');
            window.location.href = baseUrl + '/auth/login.php';
        }
    };
});
