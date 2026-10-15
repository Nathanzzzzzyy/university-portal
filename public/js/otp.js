(() => {
    const form = document.getElementById('otp-form');
    if (!form) return;
    const inputs = [...form.querySelectorAll('.otp-digit')];
    const error = document.getElementById('otp-error');
    const clearError = () => { error.textContent = ''; inputs.forEach(input => input.setAttribute('aria-invalid', 'false')); };
    const focus = index => { inputs[index].focus(); inputs[index].select(); };
    function distribute(value, start) {
        const digits = value.replace(/[^0-9]/g, '').slice(0, 4);
        if (!digits) return;
        if (digits.length === 4) start = 0;
        clearError();
        [...digits].forEach((digit, offset) => { if (inputs[start + offset]) inputs[start + offset].value = digit; });
        focus(Math.min(3, start + digits.length));
    }
    inputs.forEach((input, index) => {
        input.addEventListener('focus', () => input.select());
        input.addEventListener('paste', event => { event.preventDefault(); distribute(event.clipboardData.getData('text'), index); });
        input.addEventListener('beforeinput', event => {
            if (event.data && event.data.length > 1) { event.preventDefault(); distribute(event.data, index); }
            else if (event.data && /[^0-9]/.test(event.data)) event.preventDefault();
        });
        input.addEventListener('input', () => {
            const value = input.value.replace(/[^0-9]/g, '');
            if (value.length > 1) { distribute(value, index); return; }
            input.value = value;
            clearError();
            if (value && index < 3) focus(index + 1);
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'Backspace' && !input.value && index > 0) { event.preventDefault(); inputs[index - 1].value = ''; focus(index - 1); clearError(); }
            if (event.key === 'ArrowLeft' && index > 0) { event.preventDefault(); focus(index - 1); }
            if (event.key === 'ArrowRight' && index < 3) { event.preventDefault(); focus(index + 1); }
        });
        input.addEventListener('invalid', () => { input.setAttribute('aria-invalid', 'true'); error.textContent = 'Please enter one number in each of the four boxes.'; });
    });
    const resend = document.getElementById('otp-resend');
    const status = document.getElementById('resend-status');
    const deadline = Date.now() + Number(resend.dataset.seconds || 0) * 1000;
    const tick = () => { const seconds = Math.max(0, Math.ceil((deadline - Date.now()) / 1000)); resend.disabled = seconds > 0; resend.textContent = seconds ? 'Resend in ' + seconds + 's' : 'Resend code'; return seconds; };
    if (tick()) { const timer = setInterval(() => { if (!tick()) { clearInterval(timer); status.textContent = 'You can request a new code now.'; } }, 1000); }
})();
