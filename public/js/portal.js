(() => {
const toggle = document.getElementById('menu-toggle');
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('nav-backdrop');
function setMenu(open) { sidebar.classList.toggle('open', open); toggle.setAttribute('aria-expanded', String(open)); toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation'); backdrop.hidden = !open; }
toggle?.addEventListener('click', () => setMenu(!sidebar.classList.contains('open')));
backdrop?.addEventListener('click', () => { setMenu(false); toggle.focus(); });
document.addEventListener('keydown', e => { if(e.key === 'Escape' && sidebar.classList.contains('open')) { setMenu(false); toggle.focus(); } });
window.matchMedia('(min-width: 801px)').addEventListener('change', e => { if(e.matches) setMenu(false); });
const search = document.getElementById('subject-search');
const status = document.getElementById('subject-status');
if (search && status) {
const cards = [...document.querySelectorAll('[data-subject]')];
function filter() { const query = search.value.trim().toLowerCase(); let count = 0; cards.forEach(card => { const show = card.dataset.search.includes(query) && (status.value === 'all' || status.value === card.dataset.status); card.hidden = !show; if(show) count++; }); document.getElementById('subject-result-count').textContent = count + (count === 1 ? ' subject' : ' subjects'); document.getElementById('subject-no-results').hidden = count > 0 || cards.length === 0; }
search.addEventListener('input', filter); status.addEventListener('change', filter);
document.getElementById('reset-subject-filters')?.addEventListener('click', () => { search.value = ''; status.value = 'all'; filter(); search.focus(); });
}
})();

(() => {
const root = document.documentElement;
const appearance = document.getElementById('appearance-form');
const systemTheme = matchMedia('(prefers-color-scheme: dark)');
function resolveTheme() { root.dataset.resolvedTheme = root.dataset.theme === 'system' ? (systemTheme.matches ? 'dark' : 'light') : root.dataset.theme; }
resolveTheme(); systemTheme.addEventListener('change', resolveTheme);
if (appearance) {
const saved = { theme: root.dataset.theme, density: root.dataset.density, text_size: root.dataset.textSize, reduce_motion: root.dataset.reduceMotion === 'true' };
function preview() { root.dataset.theme = appearance.querySelector('[name="theme"]:checked').value; root.dataset.density = appearance.elements.density.value; root.dataset.textSize = appearance.elements.text_size.value; root.dataset.reduceMotion = String(document.getElementById('reduce-motion').checked); resolveTheme(); document.getElementById('preferences-preview-status').textContent = 'Preview active. Save preferences to keep your changes.'; }
appearance.addEventListener('change', preview);
document.getElementById('reset-appearance').addEventListener('click', () => { appearance.querySelector('[name="theme"][value="' + saved.theme + '"]').checked = true; appearance.elements.density.value = saved.density; appearance.elements.text_size.value = saved.text_size; document.getElementById('reduce-motion').checked = saved.reduce_motion; preview(); document.getElementById('preferences-preview-status').textContent = 'Preview restored to your saved preferences.'; });
}
document.querySelectorAll('[data-toggle-password]').forEach(button => { button.addEventListener('click', () => { const input = document.getElementById(button.dataset.togglePassword); const visible = input.type === 'password'; input.type = visible ? 'text' : 'password'; button.textContent = visible ? 'Hide' : 'Show'; button.setAttribute('aria-pressed', String(visible)); button.setAttribute('aria-label', (visible ? 'Hide ' : 'Show ') + input.labels[0].textContent.toLowerCase()); }); });
const photo = document.getElementById('photo');
if (photo) {
const preview = document.getElementById('photo-preview'); const original = preview.innerHTML; let url;
photo.addEventListener('change', () => { if(url) URL.revokeObjectURL(url); const file = photo.files[0]; const feedback = document.getElementById('photo-feedback'); photo.setCustomValidity(''); if(!file) { preview.innerHTML = original; feedback.textContent = ''; return; } if(file.size > 2 * 1024 * 1024 || !['image/jpeg','image/png','image/webp'].includes(file.type)) { photo.setCustomValidity('Choose a JPG, PNG or WebP image up to 2 MB.'); feedback.textContent = photo.validationMessage; preview.innerHTML = original; photo.reportValidity(); return; } url = URL.createObjectURL(file); const img = document.createElement('img'); img.className = 'avatar-photo'; img.alt = 'New profile photo preview'; img.src = url; preview.replaceChildren(img); feedback.textContent = 'New photo selected. Save your profile to upload it.'; const remove = document.querySelector('[name="remove_photo"]'); if(remove) remove.checked = false; });
const remove = document.querySelector('[name="remove_photo"]'); remove?.addEventListener('change', () => { if(remove.checked) { photo.value = ''; photo.setCustomValidity(''); preview.textContent = '?'; document.getElementById('photo-feedback').textContent = 'Your photo will be removed when you save.'; } else { preview.innerHTML = original; document.getElementById('photo-feedback').textContent = ''; } });
}
})();

(() => {
    document.querySelectorAll('.subject-cover-uploader').forEach(uploader => {
        const input = uploader.querySelector('[data-cover-input]');
        const preview = uploader.querySelector('[data-cover-preview]');
        const status = uploader.querySelector('[data-cover-status]');
        const remove = uploader.querySelector('[data-remove-cover]');
        const original = preview.innerHTML;
        let url;
        input.addEventListener('change', () => {
            if (url) URL.revokeObjectURL(url);
            input.setCustomValidity('');
            const file = input.files[0];
            if (!file) { preview.innerHTML = original; preview.hidden = !original.trim(); status.textContent = ''; return; }
            if (file.size > 5 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                input.setCustomValidity('Choose a JPG, PNG or WebP image up to 5 MB.');
                status.textContent = input.validationMessage; input.reportValidity(); return;
            }
            url = URL.createObjectURL(file);
            const image = document.createElement('img'); image.src = url; image.alt = 'New subject cover preview';
            preview.replaceChildren(image); preview.hidden = false;
            if (remove) remove.checked = false;
            status.textContent = 'Preview ready. Save to make this cover visible to your students.';
        });
        remove?.addEventListener('change', () => {
            if (remove.checked) { input.value = ''; input.setCustomValidity(''); preview.hidden = true; status.textContent = 'The current cover will be removed when you save.'; }
            else { preview.innerHTML = original; preview.hidden = !original.trim(); status.textContent = ''; }
        });
    });
})();
