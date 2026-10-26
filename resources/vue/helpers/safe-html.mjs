import DOMPurify from 'dompurify';

// Sanitize only when displaying HTML; stored business content stays unchanged.
export function sanitizeHtml(value, purifier = DOMPurify) {
    return purifier.sanitize(value == null ? '' : String(value), { USE_PROFILES: { html: true } });
}

function render(element, binding) {
    element.innerHTML = sanitizeHtml(binding.value);
}

export default { bind: render, update: render };
